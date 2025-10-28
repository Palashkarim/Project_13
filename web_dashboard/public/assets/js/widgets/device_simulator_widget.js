// Device Simulator: publish fake telemetry at interval (for demos/training)
(function(){
  function SimulatorWidget(cfg){
    const title = cfg.title || 'Simulator';
    const topic = cfg.topic_tele;
    const intervalMs = cfg.interval_ms || 5000;
    let timer = null;

    const root = document.createElement('div'); root.className='widget widget-sim card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">virtual</span></div>
      <div class="body" style="flex-direction:column;gap:8px">
        <div>Publishes random telemetry every ${Math.round(intervalMs/1000)}s</div>
        <div class="row"><button class="btn" id="start">Start</button><button class="btn" id="stop">Stop</button></div>
      </div>
      <div class="meta">${topic || 'no tele topic'}</div>
    `;

    function tick(){
      if (!topic) return;
      const payload = JSON.stringify({ rssi: -40 - Math.floor(Math.random()*20), heap: 200000 - Math.floor(Math.random()*5000), temp: 20 + Math.random()*10 });
      MQTTClient.publish(topic, payload);
    }

    root.querySelector('#start').onclick = ()=> { if (timer) return; tick(); timer = setInterval(tick, intervalMs); };
    root.querySelector('#stop').onclick = ()=> { if (timer) clearInterval(timer); timer=null; };

    return { el:root, update(n){ Object.assign(cfg,n||{}); }, destroy(){ if (timer) clearInterval(timer); } };
  }
  WidgetRegistry.register('device_simulator', SimulatorWidget);
})();
