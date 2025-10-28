/**
 * THERMOSTAT widget
 * - Displays current temperature/humidity (from tele topic)
 * - Lets user set a target (setpoint) and mode (HEAT/COOL/AUTO/OFF)
 * - Publishes: cmd topic with "setpoint:<n>" and "mode:<str>"
 * - Subscribes: tele topic for {"temp":N,"humidity":N}, state topic for {"setpoint":N,"mode":"AUTO","hvac":"HEATING|COOLING|IDLE"}
 *
 * Required cfg:
 *   title, device_id, topic_cmd, topic_tele
 * Optional:
 *   topic_state, min (10), max (30), step(0.5), temp_metric_key('temp'), humidity_key('humidity')
 */
(function(){
  function ThermostatWidget(cfg){
    cfg.min = typeof cfg.min === 'number' ? cfg.min : 10;
    cfg.max = typeof cfg.max === 'number' ? cfg.max : 30;
    cfg.step = typeof cfg.step === 'number' ? cfg.step : 0.5;
    const TEMP_KEY = cfg.temp_metric_key || 'temp';
    const HUM_KEY  = cfg.humidity_key    || 'humidity';

    let currentTemp = '--', currentHum = '--', setpoint = cfg.setpoint || 24.0, mode = (cfg.mode || 'AUTO').toUpperCase(), hvac = 'IDLE';

    const root = document.createElement('div'); root.className='widget widget-thermostat card';
    root.innerHTML = `
      <div class="title"><h4>${cfg.title||'Thermostat'}</h4><span class="badge">${cfg.device_id||''}</span></div>
      <div class="body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:center">
        <div style="text-align:center">
          <div style="font-size:40px;font-weight:800" id="temp">${currentTemp}°C</div>
          <div class="meta">Humidity: <span id="hum">${currentHum}%</span></div>
          <div class="badge" id="hvac">${hvac}</div>
        </div>
        <div>
          <label style="font-size:12px;color:var(--muted)">Setpoint</label>
          <div class="row" style="gap:8px;align-items:center">
            <input id="sp" type="range" min="${cfg.min}" max="${cfg.max}" step="${cfg.step}" value="${setpoint}" style="width:100%">
            <div style="min-width:56px;text-align:right"><span id="spv">${setpoint.toFixed(1)}</span>°C</div>
          </div>
          <div style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap">
            ${['OFF','HEAT','COOL','AUTO'].map(m => `<button class="btn ${m===mode?'primary':''}" data-mode="${m}">${m}</button>`).join('')}
          </div>
        </div>
      </div>
      <div class="meta">${(cfg.topic_tele||'')} ${(cfg.topic_state||'')}</div>
    `;

    const tempEl = root.querySelector('#temp');
    const humEl  = root.querySelector('#hum');
    const hvacEl = root.querySelector('#hvac');
    const sp     = root.querySelector('#sp');
    const spv    = root.querySelector('#spv');
    const modeBtns = Array.from(root.querySelectorAll('button[data-mode]'));

    function publishSetpoint(v){
      if (cfg.topic_cmd) MQTTClient.publish(cfg.topic_cmd, `setpoint:${v}`);
    }
    function publishMode(m){
      if (cfg.topic_cmd) MQTTClient.publish(cfg.topic_cmd, `mode:${m}`);
    }
    function setModeUi(m){
      modeBtns.forEach(b => b.classList.toggle('primary', b.getAttribute('data-mode')===m));
    }
    function setHvacUi(state){
      hvacEl.textContent = state || 'IDLE';
      hvacEl.style.color = state==='HEATING' ? 'var(--warn)' : (state==='COOLING' ? 'var(--brand)' : 'var(--muted)';
    }

    sp.addEventListener('input', ()=>{
      spv.textContent = parseFloat(sp.value).toFixed(1);
    });
    sp.addEventListener('change', ()=>{
      setpoint = parseFloat(sp.value);
      publishSetpoint(setpoint.toFixed(1));
    });

    modeBtns.forEach(b=>{
      b.addEventListener('click', ()=>{
        const m = b.getAttribute('data-mode');
        mode = m;
        setModeUi(m);
        publishMode(m);
      });
    });

    // Telemetry (current readings)
    let unsubTele = null, unsubState = null;
    if (cfg.topic_tele) {
      unsubTele = MQTTClient.subscribe(cfg.topic_tele, (_t, payload)=>{
        try{
          const j = JSON.parse(payload);
          if (typeof j[TEMP_KEY] !== 'undefined'){
            currentTemp = Number(j[TEMP_KEY]).toFixed(1);
            tempEl.textContent = `${currentTemp}°C`;
          }
          if (typeof j[HUM_KEY] !== 'undefined'){
            currentHum = Number(j[HUM_KEY]).toFixed(0);
            humEl.textContent = `${currentHum}%`;
          }
        }catch(_){}
      });
    }
    // State (device controller echo)
    if (cfg.topic_state) {
      unsubState = MQTTClient.subscribe(cfg.topic_state, (_t, payload)=>{
        try{
          const j = JSON.parse(payload);
          if (typeof j.setpoint !== 'undefined'){
            setpoint = Number(j.setpoint);
            sp.value = setpoint;
            spv.textContent = setpoint.toFixed(1);
          }
          if (typeof j.mode === 'string'){
            mode = j.mode.toUpperCase();
            setModeUi(mode);
          }
          if (typeof j.hvac === 'string'){
            hvac = j.hvac.toUpperCase();
            setHvacUi(hvac);
          }
        }catch(_){}
      });
    }

    return {
      el: root,
      update(newCfg){ Object.assign(cfg, newCfg||{}); },
      destroy(){ if (unsubTele) unsubTele(); if (unsubState) unsubState(); }
    };
  }

  WidgetRegistry.register('thermostat', ThermostatWidget);
})();
