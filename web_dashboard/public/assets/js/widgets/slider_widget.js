/**
 * SLIDER widget
 * - Adjust intensity/speed/brightness (0..100)
 * - Publishes: cmd topic with "level:<0-100>" (or "pwm:<value>" if cfg.mode='pwm')
 * - Subscribes: state topic to reflect the true level if device reports {"level":N}
 *
 * Required cfg:
 *   title, device_id, topic_cmd
 * Optional:
 *   topic_state, min (0), max (100), step (1), mode ('level'|'pwm')
 */
(function(){
  function SliderWidget(cfg){
    cfg.min = typeof cfg.min === 'number' ? cfg.min : 0;
    cfg.max = typeof cfg.max === 'number' ? cfg.max : 100;
    cfg.step = typeof cfg.step === 'number' ? cfg.step : 1;
    const mode = (cfg.mode || 'level').toLowerCase(); // 'level' or 'pwm'

    const root = document.createElement('div'); root.className='widget widget-slider card';
    root.innerHTML = `
      <div class="title"><h4>${cfg.title||'Slider'}</h4><span class="badge">${cfg.device_id||''}</span></div>
      <div class="body" style="flex-direction:column;gap:8px">
        <input type="range" min="${cfg.min}" max="${cfg.max}" step="${cfg.step}" value="${cfg.value ?? cfg.min}" style="width:100%" />
        <div class="meta"><span id="val">${cfg.value ?? cfg.min}</span></div>
      </div>
      <div class="meta">${cfg.topic_state||''}</div>
    `;
    const slider = root.querySelector('input');
    const valEl  = root.querySelector('#val');

    const publish = (v)=>{
      const key = mode === 'pwm' ? 'pwm' : 'level';
      if (cfg.topic_cmd) MQTTClient.publish(cfg.topic_cmd, `${key}:${v}`);
    };

    slider.addEventListener('input', ()=>{
      valEl.textContent = slider.value;
    });
    slider.addEventListener('change', ()=>{
      publish(slider.value);
    });

    // Reflect actual device level from state
    let unsub = null;
    if (cfg.topic_state) {
      unsub = MQTTClient.subscribe(cfg.topic_state, (_t, payload)=>{
        try{
          const j = JSON.parse(payload);
          const k = mode === 'pwm' ? 'pwm' : 'level';
          if (typeof j[k] !== 'undefined') {
            slider.value = j[k];
            valEl.textContent = j[k];
          }
        }catch(_){}
      });
    }

    return {
      el: root,
      update(newCfg){
        Object.assign(cfg, newCfg||{});
        if (typeof cfg.value === 'number') {
          slider.value = cfg.value;
          valEl.textContent = cfg.value;
        }
      },
      destroy(){ if (unsub) unsub(); }
    };
  }

  WidgetRegistry.register('slider', SliderWidget);
})();
