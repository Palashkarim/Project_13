// Alarm Panel: shows and arms/disarms alarm. Publishes "alarm:ARM/DISARM" to cmd.
(function(){
  function AlarmPanelWidget(cfg){
    const title = cfg.title || 'Alarm';
    const root = document.createElement('div'); root.className='widget widget-alarm card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">security</span></div>
      <div class="body" style="flex-direction:column;gap:8px">
        <div id="state" class="badge">UNKNOWN</div>
        <div class="row">
          <button class="btn" id="arm">ARM</button>
          <button class="btn" id="disarm">DISARM</button>
        </div>
      </div>
      <div class="meta">${(cfg.topic_state||'')} ${(cfg.topic_cmd||'')}</div>
    `;
    const stateEl = root.querySelector('#state');
    const armBtn  = root.querySelector('#arm');
    const disBtn  = root.querySelector('#disarm');
    let unsub = null;

    function setState(s){ stateEl.textContent = s; stateEl.style.color = (s==='ALARM'?'var(--err)':'var(--muted)'); }

    armBtn.onclick   = () => cfg.topic_cmd && MQTTClient.publish(cfg.topic_cmd, 'alarm:ARM');
    disBtn.onclick   = () => cfg.topic_cmd && MQTTClient.publish(cfg.topic_cmd, 'alarm:DISARM');

    if (cfg.topic_state){
      unsub = MQTTClient.subscribe(cfg.topic_state, (_t,p)=>{
        try{ const j = JSON.parse(p); if (j.alarm) setState(String(j.alarm).toUpperCase()); }catch(_){}
      });
    }
    return { el: root, update(n){ Object.assign(cfg,n||{}); }, destroy(){ if (unsub) unsub(); } };
  }
  WidgetRegistry.register('alarm_panel', AlarmPanelWidget);
})();

