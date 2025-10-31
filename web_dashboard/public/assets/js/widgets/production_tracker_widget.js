// Production Tracker: show machine counts/status/run-hours from tele.
(function(){
  function ProductionWidget(cfg){
    const title = cfg.title || 'Production';
    const keys = cfg.keys || ['running','run_hours','units','faults'];
    const root = document.createElement('div'); root.className='widget widget-production card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">factory</span></div>
      <div class="body" style="display:flex;gap:8px;flex-wrap:wrap"></div>
      <div class="meta">${cfg.topic_tele||''}</div>
    `;
    const body=root.querySelector('.body'); const chips={};
    keys.forEach(k=>{ const c=document.createElement('div'); c.className='badge'; c.textContent=`${k}: --`; body.appendChild(c); chips[k]=c; });
    let unsub=null;
    if (cfg.topic_tele){
      unsub = MQTTClient.subscribe(cfg.topic_tele, (_t,p)=>{
        try{
          const j = JSON.parse(p);
          keys.forEach(k=>{ if (j[k]!==undefined) chips[k].textContent = `${k}: ${j[k]}`; });
        }catch(_){}
      });
    }
    return { el:root, update(n){ Object.assign(cfg,n||{}); }, destroy(){ if (unsub) unsub(); } };
  }
  WidgetRegistry.register('production_tracker', ProductionWidget);
})();

