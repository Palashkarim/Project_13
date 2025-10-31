// Scene Builder: define a scene that publishes multiple commands with one click.
(function(){
  function SceneBuilder(cfg){
    const title = cfg.title || 'Scene';
    // cfg.actions: [{topic_cmd, payload}, ...]
    const actions = Array.isArray(cfg.actions)? cfg.actions : [];
    const root = document.createElement('div'); root.className='widget widget-scene card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">1-click</span></div>
      <div class="body" style="flex-direction:column;gap:8px">
        <div>Actions: ${actions.length}</div>
        <button class="btn primary" id="run">Run</button>
      </div>
      <div class="meta">Sends multiple MQTT commands</div>
    `;
    root.querySelector('#run').onclick = ()=>{
      actions.forEach(a => a.topic_cmd && a.payload && MQTTClient.publish(a.topic_cmd, a.payload));
    };
    return { el:root, update(n){ Object.assign(cfg,n||{}); }, destroy(){} };
  }
  WidgetRegistry.register('scene_builder', SceneBuilder);
})();

