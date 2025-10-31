// Gauge: shows a single numeric value from a telemetry topic (e.g., voltage, temp)
(function(){
  function GaugeWidget(cfg){
    const title = cfg.title || 'Gauge';
    const metricKey = cfg.metric || 'value';
    const fmt = cfg.format || (v => String(v));
    const root = document.createElement('div'); root.className='widget widget-gauge card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">${metricKey}</span></div>
      <div class="body"><div class="value">--</div></div>
      <div class="meta">${cfg.topic_tele || ''}</div>
    `;
    const valueEl = root.querySelector('.value');
    let unsub = null;

    if (cfg.topic_tele){
      unsub = MQTTClient.subscribe(cfg.topic_tele, (_t, p)=>{
        try{
          const j = JSON.parse(p);
          const v = j[metricKey] ?? j.value;
          if (v !== undefined && v !== null) valueEl.textContent = fmt(v);
        }catch(_){ /* ignore */ }
      });
    }

    return {
      el: root,
      update(n){ Object.assign(cfg, n||{}); },
      destroy(){ if (unsub) unsub(); }
    };
  }
  WidgetRegistry.register('gauge', GaugeWidget);
})();

