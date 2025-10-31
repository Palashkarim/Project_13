// Power Dashboard: voltage/current/power/energy & source status (Main/Solar/Gen).
(function(){
  function PowerWidget(cfg){
    const title = cfg.title || 'Power';
    const root = document.createElement('div'); root.className='widget widget-power card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">grid</span></div>
      <div class="body" style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px">
        ${['voltage(V)','current(A)','pf','power(W)','energy(kWh)','source'].map(k=>`<div class="badge" data-k="${k}">${k.split('(')[0]}: --</div>`).join('')}
      </div>
      <div class="meta">${cfg.topic_tele || ''}</div>
    `;
    const map = {};
    root.querySelectorAll('[data-k]').forEach(d=> { map[d.getAttribute('data-k')] = d; });
    let unsub=null;
    if (cfg.topic_tele){
      unsub = MQTTClient.subscribe(cfg.topic_tele, (_t,p)=>{
        try{
          const j = JSON.parse(p);
          if (j.voltage !== undefined) map['voltage(V)'].textContent = `voltage: ${j.voltage} V`;
          if (j.current !== undefined)  map['current(A)'].textContent = `current: ${j.current} A`;
          if (j.pf !== undefined)       map['pf'].textContent        = `pf: ${j.pf}`;
          if (j.power !== undefined)    map['power(W)'].textContent  = `power: ${j.power} W`;
          if (j.energy !== undefined)   map['energy(kWh)'].textContent=`energy: ${j.energy} kWh`;
          if (j.source)                 map['source'].textContent    = `source: ${j.source}`;
        }catch(_){}
      });
    }
    return { el: root, update(n){ Object.assign(cfg,n||{}); }, destroy(){ if (unsub) unsub(); } };
  }
  WidgetRegistry.register('power_dashboard', PowerWidget);
})();
