/*!
 * maplib.js — ultra-light map helper for your IoT dashboard
 * ----------------------------------------------------------
 * Goals:
 *  - Zero external dependencies
 *  - Optional tile rendering (self-host tiles/proxy)
 *  - Safe default: placeholder with lat/lon line (no network)
 *
 * Usage:
 *   const map = MapLib.attach(containerEl, { lat:23.78, lon:90.41, zoom:13 });
 *   map.setView(23.78, 90.41, 13);
 *   map.setMarker(23.7801, 90.4123, "Bus-12");
 *
 * To enable tiles, set before attach():
 *   window.MAPLIB_TILE_URL_TEMPLATE = "https://your-tile-server/{z}/{x}/{y}.png";
 *   // Recommended: self-host or reverse-proxy tile service to respect provider TOS.
 *
 * License: MIT
 */
(function(global){
  // Basic helpers
  function clamp(v, a, b){ return Math.max(a, Math.min(b, v)); }
  function lng2x(lon, z){ var n = Math.pow(2, z); return ((lon + 180) / 360) * n; }
  function lat2y(lat, z){
    var latRad = lat * Math.PI / 180;
    var n = Math.pow(2, z);
    return (1 - Math.log(Math.tan(latRad) + 1/Math.cos(latRad)) / Math.PI) / 2 * n;
  }
  function x2lng(x, z){ var n = Math.pow(2, z); return x / n * 360 - 180; }
  function y2lat(y, z){
    var n = Math.pow(2, z);
    var a = Math.PI - 2 * Math.PI * y / n;
    return 180 / Math.PI * Math.atan(0.5*(Math.exp(a) - Math.exp(-a)));
  }

  // DOM builder
  function el(tag, cls, css){ var e=document.createElement(tag); if(cls) e.className=cls; if(css) Object.assign(e.style, css); return e; }

  var TILE = global.MAPLIB_TILE_URL_TEMPLATE || null; // e.g., "http://tiles.local/{z}/{x}/{y}.png"

  function MapView(container, opts){
    opts = opts || {};
    var lat = typeof opts.lat === 'number' ? opts.lat : 0;
    var lon = typeof opts.lon === 'number' ? opts.lon : 0;
    var zoom = clamp((opts.zoom||2)|0, 1, 19);

    // Root
    var wrap = el('div','maplib-wrap',{position:'relative',width:'100%',height:'100%',overflow:'hidden',borderRadius:'10px',background:'#0e1117',color:'#98a1b2',userSelect:'none'});
    container.appendChild(wrap);

    // Layers
    var tilesLayer = el('div','maplib-tiles',{position:'absolute',inset:'0',overflow:'hidden'});
    var overlay = el('div','maplib-overlay',{position:'absolute',inset:'0',pointerEvents:'none'});
    var hud = el('div','maplib-hud',{position:'absolute',left:'8px',bottom:'8px',padding:'4px 8px',fontSize:'12px',background:'rgba(0,0,0,.35)',borderRadius:'8px',backdropFilter:'blur(4px)'});
    wrap.appendChild(tilesLayer);
    wrap.appendChild(overlay);
    wrap.appendChild(hud);

    // Controls
    var controls = el('div','maplib-ctrl',{position:'absolute',right:'8px',top:'8px',display:'grid',gap:'6px'});
    var zoomIn = el('button','btn',{padding:'6px 8px',border:'1px solid var(--border, #2a2d36)',background:'var(--bg-2, #16171b)',color:'var(--fg,#e6eaf2)',borderRadius:'10px'}); zoomIn.textContent='+';
    var zoomOut= el('button','btn',{padding:'6px 8px',border:'1px solid var(--border, #2a2d36)',background:'var(--bg-2, #16171b)',color:'var(--fg,#e6eaf2)',borderRadius:'10px'}); zoomOut.textContent='–';
    controls.appendChild(zoomIn); controls.appendChild(zoomOut);
    wrap.appendChild(controls);

    // Marker
    var marker = el('div','maplib-marker',{position:'absolute',transform:'translate(-50%,-100%)',pointerEvents:'auto'});
    marker.innerHTML = '<div style="background:#4f8cff;border-radius:50%;width:12px;height:12px;box-shadow:0 0 0 4px rgba(79,140,255,.25)"></div><div class="lbl" style="margin-top:4px;text-shadow:0 1px 2px rgba(0,0,0,.5);font-size:12px"></div>';
    overlay.appendChild(marker);
    marker.style.display = 'none';

    // State
    var dragging = false, dragStart = null, viewX=0, viewY=0;

    function updateHUD(){ hud.textContent = 'Lat: '+lat.toFixed(5)+'  Lon: '+lon.toFixed(5)+'  Z:'+zoom; }

    // Render tiles (if TILE template provided), else show a nice placeholder grid
    function renderTiles(){
      tilesLayer.innerHTML = '';
      var w = wrap.clientWidth, h = wrap.clientHeight;
      if (!TILE){
        // Placeholder grid & crosshair
        var grid = el('canvas',null,{width:String(w),height:String(h)});
        var ctx = grid.getContext('2d');
        ctx.fillStyle = '#11131a'; ctx.fillRect(0,0,w,h);
        ctx.strokeStyle = '#1b1f2a'; ctx.lineWidth = 1;
        for(var x=0; x<w; x+=40){ ctx.beginPath(); ctx.moveTo(x,0); ctx.lineTo(x,h); ctx.stroke(); }
        for(var y=0; y<h; y+=40){ ctx.beginPath(); ctx.moveTo(0,y); ctx.lineTo(w,y); ctx.stroke(); }
        ctx.strokeStyle = '#2b3242'; ctx.lineWidth = 2; ctx.beginPath(); ctx.moveTo(w/2,0); ctx.lineTo(w/2,h); ctx.moveTo(0,h/2); ctx.lineTo(w,h/2); ctx.stroke();
        tilesLayer.appendChild(grid);
        return;
      }

      // Calculate tile indices to cover view
      var xt = lng2x(lon, zoom), yt = lat2y(lat, zoom);
      var tileSize = 256;
      var cx = Math.floor(xt), cy = Math.floor(yt);
      var n = Math.pow(2, zoom);

      // How many tiles horizontally/vertically fit?
      var cols = Math.ceil(w / tileSize) + 2;
      var rows = Math.ceil(h / tileSize) + 2;

      // Offset for center alignment
      var dx = (xt - cx) * tileSize;
      var dy = (yt - cy) * tileSize;

      for (var r=-Math.floor(rows/2); r<=Math.floor(rows/2); r++){
        for (var c=-Math.floor(cols/2); c<=Math.floor(cols/2); c++){
          var tx = (cx + c) % n; if (tx<0) tx+=n;
          var ty = (cy + r); ty = clamp(ty, 0, n-1);
          var img = new Image();
          img.referrerPolicy = 'no-referrer';
          img.decoding = 'async';
          img.loading = 'lazy';
          img.style.position='absolute';
          img.style.width = tileSize+'px';
          img.style.height= tileSize+'px';
          img.style.left = Math.round(w/2 + c*tileSize - dx)+'px';
          img.style.top  = Math.round(h/2 + r*tileSize - dy)+'px';
          img.src = TILE.replace('{z}', zoom).replace('{x}', tx).replace('{y}', ty);
          tilesLayer.appendChild(img);
        }
      }
    }

    function renderMarker(){
      if (marker.style.display === 'none') return;
      var w = wrap.clientWidth, h = wrap.clientHeight;
      var xt = lng2x(lon, zoom), yt = lat2y(lat, zoom);
      var x2 = lng2x(_marker.latlng.lon, zoom);
      var y2 = lat2y(_marker.latlng.lat, zoom);
      var tileSize = 256;
      var dx = (x2 - xt) * tileSize;
      var dy = (y2 - yt) * tileSize;
      marker.style.left = (w/2 + dx) + 'px';
      marker.style.top  = (h/2 + dy) + 'px';
      marker.querySelector('.lbl').textContent = _marker.text || '';
    }

    var _marker = {latlng:null, text:''};

    function setView(nlat, nlon, nzoom){
      lat = clamp(Number(nlat)||0, -85, 85);
      lon = ((Number(nlon)||0)+540)%360 - 180; // wrap
      if (typeof nzoom !== 'undefined') zoom = clamp(nzoom|0, 1, 19);
      renderTiles(); renderMarker(); updateHUD();
    }

    function setMarker(mlat, mlon, text){
      _marker.latlng = {lat:Number(mlat)||0, lon:Number(mlon)||0};
      _marker.text = text || '';
      marker.style.display = 'block';
      renderMarker(); updateHUD();
    }

    // Pan with mouse drag
    wrap.addEventListener('mousedown', function(e){ dragging=true; dragStart={x:e.clientX,y:e.clientY,startLat:lat,startLon:lon}; });
    window.addEventListener('mouseup',   function(){ dragging=false; });
    window.addEventListener('mousemove', function(e){
      if(!dragging) return;
      var w = wrap.clientWidth, h = wrap.clientHeight, tileSize=256;
      var dx = e.clientX - dragStart.x, dy = e.clientY - dragStart.y;
      var xt = lng2x(dragStart.startLon, zoom), yt = lat2y(dragStart.startLat, zoom);
      var nx = xt - dx / tileSize, ny = yt - dy / tileSize;
      setView(y2lat(ny, zoom), x2lng(nx, zoom)); // keeps zoom
    });

    // Zoom buttons
    zoomIn.addEventListener('click', function(){ setView(lat, lon, clamp(zoom+1,1,19)); });
    zoomOut.addEventListener('click',function(){ setView(lat, lon, clamp(zoom-1,1,19)); });

    // Resize handling
    var ro = new ResizeObserver(function(){ renderTiles(); renderMarker(); });
    ro.observe(wrap);

    // initial render
    renderTiles(); updateHUD();

    // Public API
    this.setView   = setView;
    this.setMarker = setMarker;
    this.destroy = function(){ ro.disconnect(); container.removeChild(wrap); };
  }

  // Global namespace
  global.MapLib = {
    attach: function(container, opts){ return new MapView(container, opts||{}); }
  };

})(window);
