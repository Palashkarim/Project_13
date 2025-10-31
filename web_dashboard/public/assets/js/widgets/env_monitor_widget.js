// Environment Monitor: shows temp, humidity, gas, dust, etc. from tele payload.
(function(){
  function EnvWidget(cfg){
    const title = cfg.title || 'Environment';
    const keys = cfg.keys || ['temp','humidity','co2','pm2_5'];
    const root = document.createElement('div'); root.className='widget widget-env card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">sensors</span></div>
      <div class="body" style="flex-wrap:wrap;gap:8px;justify-content:flex-start"></div>
      <div class="meta">${cfg.topic_tele||''}</div>
    `;
    const body = root.querySelector('.body');
    const labels = {};
    keys.forEach(k=>{
      const chip = document.createElement('div');
      chip.className='badge';
      chip.textContent = `${k}: --`;
      body.appendChild(chip);
      labels[k]=chip;
    });
    let unsub=null;
    if (cfg.topic_tele){
      unsub = MQTTClient.subscribe(cfg.topic_tele, (_t,p)=>{
        try{
          const j = JSON.parse(p);
          keys.forEach(k=>{
            if (j[k] !== undefined) labels[k].textContent = `${k}: ${j[k]}`;
          });
        }catch(_){}
      });
    }
    return { el: root, update(n){ Object.assign(cfg,n||{}); }, destroy(){ if (unsub) unsub(); } };
  }
  WidgetRegistry.register('env_monitor', EnvWidget);
})();
