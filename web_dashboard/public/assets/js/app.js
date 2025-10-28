// App bootstrap: renders Login or Dashboard.
// Theme toggle, simple routing, load boards + widgets.

(function(){

  const state = {
    user: null,
    boards: [],  // [{id,name,color,widgets:[{type, cfg...}]}]
  };

  // Theme toggle (persist in localStorage)
  function applyTheme(theme){
    const light = document.getElementById('theme-light');
    const dark  = document.getElementById('theme-dark');
    if(theme==='dark'){ dark.disabled=false; light.disabled=true; }
    else { light.disabled=false; dark.disabled=true; }
    localStorage.setItem('theme', theme);
  }
  applyTheme(localStorage.getItem('theme')||'dark');

  function renderLogin(){
    document.body.className = '';
    const el = document.getElementById('app');
    el.innerHTML = `
      <div class="login-wrap">
        <div class="login-card">
          <h1>Welcome</h1>
          <p>Sign in with your admin-created account.</p>
          <input class="input" id="email" type="email" placeholder="Email">
          <input class="input" id="pass" type="password" placeholder="Password">
          <div style="display:flex;gap:8px;margin-top:12px">
            <button class="btn primary" id="login">Sign In</button>
            <button class="btn ghost" id="theme">Toggle Theme</button>
          </div>
          <div id="err" style="color:var(--err);margin-top:10px;min-height:20px"></div>
        </div>
      </div>
    `;
    el.querySelector('#theme').onclick = () => applyTheme(localStorage.getItem('theme')==='dark'?'light':'dark');
    el.querySelector('#login').onclick = async () => {
      const email = el.querySelector('#email').value.trim();
      const pass  = el.querySelector('#pass').value;
      const err   = el.querySelector('#err');
      err.textContent = '';
      try{
        await Auth.login(email, pass);
        await bootDashboard();
      }catch(e){
        err.textContent = e.message || 'Login failed';
      }
    };
  }

  function topbar(){
    return `
      <div class="topbar">
        <div class="brand">IoT Platform</div>
        <div class="right">
          <span class="badge" id="userName">...</span>
          <button class="btn ghost" id="toggleTheme">Theme</button>
          <button class="btn" id="logout">Logout</button>
        </div>
      </div>
    `;
  }

  function sidebar(){
    return `
      <div class="sidebar">
        <div class="section"><h4>Navigation</h4>
          <a class="link active" href="#/boards">Boards</a>
          <a class="link" href="#/devices">Devices</a>
          <a class="link" href="#/exports">Exports</a>
        </div>
        <div class="section"><h4>Status</h4>
          <div class="badge" id="mqttStatus">MQTT: offline</div>
        </div>
      </div>
    `;
  }

  function renderDashboard(){
    document.body.className = 'app-shell';
    const el = document.getElementById('app');
    el.innerHTML = `
      ${topbar()}
      <div class="container">
        ${sidebar()}
        <div class="content">
          <div class="toolbar">
            <h2>Boards</h2>
            <div class="row">
              <button class="btn" id="refreshBoards">Refresh</button>
            </div>
          </div>
          <div class="boards-grid" id="boardsGrid"></div>
        </div>
      </div>
    `;

    document.getElementById('toggleTheme').onclick = () =>
      applyTheme(localStorage.getItem('theme')==='dark'?'light':'dark');
    document.getElementById('logout').onclick = () => { Auth.logout(); location.reload(); };

    // Fill user name
    document.getElementById('userName').textContent = state.user?.display_name || state.user?.email || 'User';

    // MQTT status indicator
    const mqttBadge = document.getElementById('mqttStatus');
    const updateBadge = (ok) => { mqttBadge.textContent = 'MQTT: ' + (ok?'online':'offline'); mqttBadge.style.color = ok?'var(--ok)':'var(--err)'; };

    // Connect MQTT with per-tenant creds if your API exposes them
    MQTTClient.connect({
      host: window.MQTT_WS_HOST || location.hostname,
      port: window.MQTT_WS_PORT || 9001,
      username: state.user?.mqtt_username || null,
      password: state.user?.mqtt_password || null
    });
    // crude hook
    setInterval(()=> updateBadge(true), 3000); // optimistic; replace with client events if needed

    // Render boards
    const grid = document.getElementById('boardsGrid');
    grid.innerHTML = '';
    for (const b of state.boards) {
      const card = document.createElement('div');
      card.className = 'card';
      card.innerHTML = `
        <h3>${b.name}</h3>
        <div class="row" style="margin-bottom:8px">
          <span class="badge">#${b.id}</span>
          <span class="badge">widgets: ${b.widgets?.length || 0}</span>
        </div>
        <div class="boards-grid" id="board_${b.id}"></div>
      `;
      grid.appendChild(card);

      // Render widgets inside the board
      const tgt = card.querySelector(`#board_${b.id}`);
      (b.widgets||[]).forEach(w=>{
        const inst = WidgetRegistry.create(w.type, w.cfg || {});
        tgt.appendChild(inst.el);
      });
    }

    document.getElementById('refreshBoards').onclick = loadBoards;
  }

  async function loadUser(){
    // Replace with your API endpoint; expects {id,email,display_name, mqtt_username?, mqtt_password?}
    state.user = await Auth.api('/api/me');
  }

  async function loadBoards(){
    // Replace with your API endpoint; expected structure shown below
    // Example fallback:
    try{
      state.boards = await Auth.api('/api/boards');
    }catch(_){
      // Fallback mock (shows one board with two widgets wired to tenant topics)
      const uid = state.user?.id || 1;
      const dev = 'demo01';
      const base = `ten/${uid}/dev/${dev}`;
      state.boards = [{
        id: 1, name: 'Room 1 – Power', color:'#4f8cff',
        widgets: [
          { type:'switch', cfg:{ title:'Light', device_id:dev, topic_cmd:`${base}/cmd`, topic_state:`${base}/state` } },
          { type:'gauge',  cfg:{ title:'RSSI',  device_id:dev, topic_tele:`${base}/tele`, metric:'rssi' } }
        ]
      }];
    }
    renderDashboard(); // re-render
  }

  async function bootDashboard(){
    try{
      await loadUser();
      await OfflineCache.flush();
      await loadBoards();
    }catch(e){
      console.error(e);
      Auth.logout();
      renderLogin();
    }
  }

  // Entry
  if (Auth.token()) bootDashboard();
  else renderLogin();

})();
