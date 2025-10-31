// Camera: show an MJPEG/RTSP (via gateway) or snapshot image. No auto-credentials.
(function(){
  function CameraWidget(cfg){
    const title = cfg.title || 'Camera';
    const src = cfg.src || ''; // e.g., http(s) mjpeg stream URL or snapshot endpoint
    const root = document.createElement('div'); root.className='widget widget-camera card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">live</span></div>
      <div class="body" style="min-height:140px"><img style="max-width:100%;max-height:220px;border-radius:10px" alt="camera"/></div>
      <div class="meta">${src ? src : 'No source configured'}</div>
    `;
    const img = root.querySelector('img');
    if (src) img.src = src;

    return { el: root, update(n){ Object.assign(cfg,n||{}); if (cfg.src) img.src=cfg.src; }, destroy(){} };
  }
  WidgetRegistry.register('camera', CameraWidget);
})();
