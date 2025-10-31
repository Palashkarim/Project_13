// Billing Widget: show plan, expiry, usage caps (read-only)
(function(){
  function BillingWidget(cfg){
    const title = cfg.title || 'Billing';
    const root = document.createElement('div'); root.className='widget widget-billing card';
    root.innerHTML = `
      <div class="title"><h4>${title}</h4><span class="badge">subscription</span></div>
      <div class="body" style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px">
        <div class="badge" id="plan">plan: --</div>
        <div class="badge" id="expiry">expires: --</div>
        <div class="badge" id="boards">boards: --</div>
        <div class="badge" id="widgets">widgets/board: --</div>
      </div>
      <div class="meta">Plan info</div>
    `;
    const els = {
      plan: root.querySelector('#plan'),
      expiry: root.querySelector('#expiry'),
      boards: root.querySelector('#boards'),
      widgets: root.querySelector('#widgets'),
    };

    async function load(){
      try{
        const j = await Auth.api('/api/billing/me');
        els.plan.textContent   = 'plan: ' + (j.plan_key||'--');
        els.expiry.textContent = 'expires: ' + (j.expires_at||'--');
        els.boards.textContent = 'boards: ' + (j.limits?.max_boards ?? '--');
        els.widgets.textContent= 'widgets/board: ' + (j.limits?.max_widgets ?? '--');
      }catch(_){}
    }
    load();

    return { el:root, update(n){ Object.assign(cfg,n||{}); }, destroy(){} };
  }
  WidgetRegistry.register('billing', BillingWidget);
})();
