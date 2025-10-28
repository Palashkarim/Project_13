/**
 * SWITCH widget
 * - Toggle ON/OFF a relay (or any binary actuator)
 * - Publishes: cmd topic with "relay:1" / "relay:0"
 * - Subscribes: state topic with {"relay":0|1}
 *
 * Required cfg:
 *   title, device_id, topic_cmd, topic_state
 */
(function(){
  function SwitchWidget(cfg){
    const root = document.createElement('div'); root.className='widget widget-switch card';
    root.innerHTML = `
      <div class="title"><h4>${cfg.title||'Switch'}</h4><span class="badge">${cfg.device_id||''}</span></div>
      <div class="body"><button class="btn primary" data-on="0">Turn ON</button></div>
      <div class="meta">${cfg.topic_state||''}</div>
    `;
    const btn = root.querySelector('button');

    function setUi(on){
      btn.textContent = on ? 'Turn OFF' : 'Turn ON';
      btn.setAttribute('data-on', on ? '1':'0');
      btn.classList.toggle('primary', !on);
    }

    btn.addEventListener('click', ()=>{
      const isOn = btn.getAttribute('data-on') === '1';
      const next = isOn ? 0 : 1;
      if (cfg.topic_cmd) MQTTClient.publish(cfg.topic_cmd, `relay:${next}`);
      setUi(next === 1);
    });

    // Live state sync from device
    let unsub = null;
    if (cfg.topic_state) {
      unsub = MQTTClient.subscribe(cfg.topic_state, (_t, payload)=>{
        try{
          const j = JSON.parse(payload);
          if (typeof j.relay !== 'undefined') setUi(!!j.relay);
        }catch(_){}
      });
    }

    return {
      el: root,
      update(newCfg){ Object.assign(cfg, newCfg||{}); },
      destroy(){ if (unsub) unsub(); }
    };
  }

  // Register under the short type key used by your catalogs
  WidgetRegistry.register('switch', SwitchWidget);
})();

