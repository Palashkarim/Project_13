// Minimal widget system. Each widget is a small module with render() & update().

window.WidgetRegistry = (function(){
  const types = {};

  // SWITCH widget (publishes cmd to toggle relay)
  types['switch'] = function(cfg){
    const root = document.createElement('div'); root.className='widget widget-switch card';
    root.innerHTML = `
      <div class="title"><h4>${cfg.title||'Switch'}</h4><span class="badge">${cfg.device_id||''}</span></div>
      <div class="body"><button class="btn primary" data-on="0">Turn ON</button></div>
      <div class="meta">${cfg.topic_state||''}</div>
    `;
    const btn = root.querySelector('button');
    btn.addEventListener('click', ()=>{
      const isOn = btn.getAttribute('data-on') === '1';
      const next = isOn ? 0 : 1;
      MQTTClient.publish(cfg.topic_cmd, `relay:${next}`);
      btn.textContent = next ? 'Turn OFF' : 'Turn ON';
      btn.setAttribute('data-on', next ? '1':'0');
    });
    // subscribe to state
    if (cfg.topic_state) {
      MQTTClient.subscribe(cfg.topic_state, (_t, payload)=>{
        try{
          const j = JSON.parse(payload);
          const on = !!j.relay;
          btn.textContent = on ? 'Turn OFF' : 'Turn ON';
          btn.setAttribute('data-on', on ? '1':'0');
        }catch(_){}
      });
    }
    return {el:root, update:_=>{}};
  };

  // GAUGE widget (shows numeric value)
  types['gauge'] = function(cfg){
    const root = document.createElement('div'); root.className='widget widget-gauge card';
    root.innerHTML = `
      <div class="title"><h4>${cfg.title||'Gauge'}</h4><span class="badge">${cfg.metric||''}</span></div>
      <div class="body"><div class="value">--</div></div>
      <div class="meta">${cfg.topic_tele||''}</div>
    `;
    const val = root.querySelector('.value');
    if(cfg.topic_tele){
      MQTTClient.subscribe(cfg.topic_tele, (_t, payload)=>{
        try{
          const j = JSON.parse(payload);
          const v = j[cfg.metric] ?? j.value ?? null;
          if(v !== null) val.textContent = v;
        }catch(_){}
      });
    }
    return {el:root, update:_=>{}};
  };

  // Register more types as you build out …
  function create(type, cfg){ const fn = types[type]; if(!fn) throw new Error('Unknown widget: '+type); return fn(cfg); }

  return { create, types };
})();

