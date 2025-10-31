// Device Health: shows last seen, rssi, heap from tele/state topics.
(function(){
  function HealthWidget(cfg){
    const title = cfg.title || 'Device Health';
    const root = document.createElement('div'); root.className='widget widget-health card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">${cfg.device_id||''}</span></div>
      <div class="body" style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px">
        <div class="badge" id="seen">last: --</div>
        <div class="badge" id="rssi">rssi: --</div>
        <div class="badge" id="heap">heap: --</div>
      </div>
      <div class="meta">${(cfg.topic_tele||'')} ${(cfg.topic_lwt||'')}</div>
    `;
    const seen=root.querySelector('#seen'), rssi=root.querySelector('#rssi'), heap=root.querySelector('#heap');
    let u1=null, u2=null;
    if (cfg.topic_tele){
      u1 = MQTTClient.subscribe(cfg.topic_tele, (_t,p)=>{
        try{ const j=JSON.parse(p); if (j.rssi!==undefined) rssi.textContent=`rssi: ${j.rssi}`; if (j.heap!==undefined) heap.textContent=`heap: ${j.heap}`; seen.textContent='last: now'; }catch(_){}
      });
    }
    if (cfg.topic_lwt){
      u2 = MQTTClient.subscribe(cfg.topic_lwt, (_t,p)=>{
        const msg = (p||'').toLowerCase();
        if (msg.includes('online')) seen.textContent='status: online';
        else if (msg.includes('offline')) seen.textContent='status: offline';
      });
    }
    return { el:root, update(n){ Object.assign(cfg,n||{}); }, destroy(){ if(u1)u1(); if(u2)u2(); } };
  }
  WidgetRegistry.register('device_health', HealthWidget);
})();
