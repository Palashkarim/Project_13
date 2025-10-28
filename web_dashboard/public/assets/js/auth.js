// Auth utilities: store/retrieve JWT, simple API wrapper
window.Auth = (function(){
  const KEY = 'iot_jwt';
  function token(){ return localStorage.getItem(KEY) || ''; }
  function setToken(t){ t ? localStorage.setItem(KEY, t) : localStorage.removeItem(KEY); }

  async function login(email, password){
    // Call your backend API
    const res = await fetch('/api/auth/login', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({email, password})
    });
    if(!res.ok) throw new Error('Invalid credentials');
    const data = await res.json();
    setToken(data.token);
    return data;
  }

  function logout(){ setToken(''); }

  async function api(path, opts={}){
    const headers = Object.assign({'Content-Type':'application/json'}, opts.headers||{});
    const t = token();
    if(t) headers.Authorization = `Bearer ${t}`;
    const res = await fetch(path, Object.assign({}, opts, {headers}));
    if(res.status === 401){ logout(); location.reload(); return; }
    const ct = res.headers.get('content-type') || '';
    return ct.includes('application/json') ? res.json() : res.text();
  }

  return { token, setToken, login, logout, api };
})();
