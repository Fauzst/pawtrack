// Handles the user-profile popout: open/close, prefill, preview image, submit
(function(){
  function qs(sel, root=document){ return root.querySelector(sel); }

  function buildPopout() {
    // If popout already exists (script may have been loaded more than once), return it
    const existing = document.getElementById('user-popout');
    if (existing) return existing;

    const container = document.createElement('div');
    container.id = 'user-popout';
    container.className = 'user-popout hidden';
    container.innerHTML = `
      <div class="user-popout-card">
        <button class="close-popout" aria-label="Close">×</button>
        <h3>My Profile</h3>
        <form id="user-popout-form" enctype="multipart/form-data">
          <div class="popout-row">
            <label for="popout-name">Full name</label>
            <input id="popout-name" name="name" type="text" required />
          </div>
          <div class="popout-row">
            <label for="popout-email">Email</label>
            <input id="popout-email" name="email" type="email" required />
          </div>
          <div class="popout-row vet-only">
            <label for="popout-clinic-branch">Clinic Branch</label>
            <input id="popout-clinic-branch" name="clinicBranch" type="text" />
          </div>
          <div class="popout-row vet-only">
            <label for="popout-vet-spec">Specialization</label>
            <input id="popout-vet-spec" name="vetSpecialization" type="text" />
          </div>
          <div class="popout-row vet-only">
            <label for="popout-vet-license">License Number</label>
            <input id="popout-vet-license" name="vetLicenseNo" type="text" />
          </div>
          <div class="popout-row vet-only">
            <label for="popout-vet-exp">Years of Experience</label>
            <input id="popout-vet-exp" name="vetExperience" type="number" min="0" />
          </div>
          <div class="popout-row vet-only">
            <label for="popout-vet-contact">Contact Number</label>
            <input id="popout-vet-contact" name="vetContact" type="text" />
          </div>
          <div class="popout-row">
            <label for="popout-pic">Profile picture</label>
            <input id="popout-pic" name="pic" type="file" accept="image/*" />
            <img id="popout-pic-preview" class="popout-pic-preview" alt="Preview" />
          </div>
          <div class="popout-actions">
            <button type="submit" class="btn btn-primary">Save</button>
            <button type="button" class="btn btn-secondary cancel">Cancel</button>
          </div>
        </form>
        <div id="popout-msg" class="popout-msg"></div>
      </div>
    `;
    document.body.appendChild(container);
    return container;
  }

  function showPopout(container){
    container.classList.remove('hidden');
    setTimeout(()=> container.classList.add('visible'), 10);
  }
  function hidePopout(container){
    container.classList.remove('visible');
    setTimeout(()=> container.classList.add('hidden'), 200);
  }

  async function fetchSessionUser(){
    try{
      const res = await fetch('/backend/get-users.php', { method: 'GET', credentials: 'same-origin' });
      if (res.ok){
        const json = await res.json();
        if (json && json.status === 'success' && json.data){
          return json.data;
        }
      }
    }catch(e){}
    const nameEl = document.querySelector('.user-name');
    const emailEl = document.querySelector('.user-email');
    const imgEl = document.querySelector('.user-avatar');
    return {
      id: window.PAWTRACK_USER_ID || null,
      role: window.PAWTRACK_USER_ROLE || 'client',
      name: nameEl ? nameEl.textContent.trim() : '',
      email: emailEl ? emailEl.textContent.trim() : '',
      pic: imgEl ? imgEl.src : ''
    };
  }

  document.addEventListener('DOMContentLoaded', async function(){
    const userIcon = document.querySelector('.user-icon') || document.querySelector('.admin-nav-icon') || document.querySelector('.vet-nav-icons .fa-user');
    if (!userIcon) return;

    const popout = buildPopout();
    const form = popout.querySelector('#user-popout-form');
    const closeBtn = popout.querySelector('.close-popout');
    const cancelBtn = popout.querySelector('.cancel');
    const fileInput = popout.querySelector('#popout-pic');
    const preview = popout.querySelector('#popout-pic-preview');
    const msg = popout.querySelector('#popout-msg');

    // Attach event listeners only once. If the script runs multiple times, skip reattaching.
    if (popout.dataset.listenersAttached === '1') {
      // Prefill values in case session changed since last build
      // (we still update input values below)
    } else {
      popout.dataset.listenersAttached = '1';

      // attach user-icon click handlers and other listeners below (kept in-place)
    }

    const session = await fetchSessionUser();
    const userIconEl = document.querySelector('.user-icon');
    if (userIconEl) {
      session.id = userIconEl.dataset.userId || session.id;
      session.role = userIconEl.dataset.userRole || session.role;
    }

    const nameInput = popout.querySelector('#popout-name');
    const emailInput = popout.querySelector('#popout-email');
    nameInput.value = session.name || '';
    emailInput.value = session.email || '';
    if (session.pic){ preview.src = session.pic; preview.style.display = 'block'; }

    const clinicInput = popout.querySelector('#popout-clinic-branch');
    const vetSpecInput = popout.querySelector('#popout-vet-spec');
    const vetLicenseInput = popout.querySelector('#popout-vet-license');
    const vetExpInput = popout.querySelector('#popout-vet-exp');
    const vetContactInput = popout.querySelector('#popout-vet-contact');

    function readVetField(label){
      const ps = document.querySelectorAll('.vet-info-card p');
      for (let p of ps){
        if (p.textContent && p.textContent.indexOf(label) !== -1){
          const sp = p.querySelector('span');
          return sp ? sp.textContent.trim() : '';
        }
      }
      return '';
    }

    const vetNameFull = document.getElementById('vetNameFull');
    if (vetNameFull && !nameInput.value) nameInput.value = vetNameFull.textContent.trim();
    const vetEmailSpan = document.getElementById('vetEmail');
    if (vetEmailSpan && !emailInput.value) emailInput.value = vetEmailSpan.textContent.trim();
    if (clinicInput && !clinicInput.value) clinicInput.value = readVetField('Clinic Branch');
    if (vetSpecInput && !vetSpecInput.value) vetSpecInput.value = readVetField('Specialization');
    if (vetLicenseInput && !vetLicenseInput.value) vetLicenseInput.value = readVetField('License Number');
    if (vetExpInput && !vetExpInput.value) vetExpInput.value = readVetField('Years of Experience');
    if (vetContactInput && !vetContactInput.value) vetContactInput.value = readVetField('Contact Number');
    const vetImg = document.getElementById('vetProfileImage');
    if (vetImg && vetImg.src) { preview.src = vetImg.src; preview.style.display = 'block'; }

    document.querySelectorAll('.user-icon, .admin-nav-icon, .vet-nav-icons .fa-user').forEach(el => {
      // avoid attaching duplicate listeners if they already exist on the element
      // (some browsers don't expose a straightforward way to detect existing listeners)
      // but wrapping the whole attachment in popout.dataset.listenersAttached above prevents duplication
      el.addEventListener('click', (ev)=>{
        // Determine clicked id/role from data attributes, fallback to session
        // Prefer explicit data attributes, then vet page element, then session globals
        const vetIdSpan = document.getElementById('vetID');
        const vetIdFromSpan = vetIdSpan ? vetIdSpan.textContent.trim() : '';
        const clickedId = ev.currentTarget.dataset.userId || ev.currentTarget.dataset.userid || vetIdFromSpan || session.id;
        // If a vet id is present on the page, treat this as a vet context unless overridden by data attributes
        const clickedRole = ev.currentTarget.dataset.userRole || ev.currentTarget.dataset.userrole || (vetIdFromSpan ? 'vet' : session.role) ||
          (ev.currentTarget.classList.contains('admin-nav-icon') ? 'admin' : 'client');

  // Persist these on the popout so submit reads the exact origin
  popout.dataset.originalId = clickedId;
  popout.dataset.originalRole = clickedRole;
  console.debug('user-popout: opened; id source', { clickedId, vetIdFromSpan, dataset: ev.currentTarget.dataset, sessionId: session.id });

        // Update session in-memory for display prefills
        session.id = clickedId;
        session.role = clickedRole;

        const nameSpan = document.querySelector('.user-name');
        const emailSpan = document.querySelector('.user-email');
        if (nameSpan) nameInput.value = nameSpan.textContent.trim();
        if (emailSpan) emailInput.value = emailSpan.textContent.trim();

        if (clickedRole === 'vet' || ev.currentTarget.closest('.vet-nav-icons') || ev.currentTarget.matches('.vet-nav-icons .fa-user')){
          document.querySelectorAll('.vet-only').forEach(n=> n.style.display = 'flex');
          if (clinicInput && !clinicInput.value) clinicInput.value = readVetField('Clinic Branch');
          if (vetSpecInput && !vetSpecInput.value) vetSpecInput.value = readVetField('Specialization');
          if (vetLicenseInput && !vetLicenseInput.value) vetLicenseInput.value = readVetField('License Number');
          if (vetExpInput && !vetExpInput.value) vetExpInput.value = readVetField('Years of Experience');
          if (vetContactInput && !vetContactInput.value) vetContactInput.value = readVetField('Contact Number');
          const vImg = document.getElementById('vetProfileImage');
          if (vImg && vImg.src) { preview.src = vImg.src; preview.style.display='block'; }
        } else {
          document.querySelectorAll('.vet-only').forEach(n=> n.style.display = 'none');
        }

        if (ev.currentTarget.querySelector('img')) {
          preview.src = ev.currentTarget.querySelector('img').src;
          preview.style.display='block';
        }
        showPopout(popout);
      });
    });

  closeBtn.addEventListener('click', ()=> hidePopout(popout));
  cancelBtn.addEventListener('click', ()=> hidePopout(popout));

  fileInput.addEventListener('change', (ev)=>{
      const f = ev.target.files && ev.target.files[0];
      if (!f) { preview.style.display='none'; preview.src=''; return; }
      const url = URL.createObjectURL(f);
      preview.src = url; preview.style.display='block';
    });

    // =======================
    // SIMPLE CRUD SUBMIT
    // One small submit handler that POSTs to role-specific endpoint
    // =======================
  form.addEventListener('submit', async function(e){
      e.preventDefault();

      const confirmed = await Swal.fire({
        title: 'Save changes?',
        text: 'Your profile information will be updated.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, save it',
        cancelButtonText: 'Cancel'
      });
      if (!confirmed.isConfirmed) return;

      Swal.fire({ title: 'Saving...', text: 'Please wait...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

      const fd = new FormData();
      fd.append('name', nameInput.value.trim());
      fd.append('email', emailInput.value.trim());

  // prefer vet page element first (vet-session.php renders #vetID), then persisted originalId, then session/global
  const vetIdEl = document.getElementById('vetID');
  const vetPageId = vetIdEl ? vetIdEl.textContent.trim() : '';
  const originalId = popout.dataset.originalId || vetPageId || session.id || window.PAWTRACK_USER_ID || '';
  // prefer vet page presence to detect vet role
  const originalRole = popout.dataset.originalRole || (vetPageId ? 'vet' : session.role) || window.PAWTRACK_USER_ROLE || 'client';
  console.debug('user-popout: submit; id chosen', { originalId, originalRole, vetPageId, popoutDataset: popout.dataset });
      if (!originalId) {
        Swal.close();
        Swal.fire({ title: 'Error', text: 'Missing user id', icon: 'error' });
        return;
      }
      fd.append('id', originalId);

      // include vet fields only for vet
      if (originalRole === 'vet'){
        fd.append('vetSpecialization', document.querySelector('#popout-vet-spec')?.value || '');
        fd.append('vetLicenseNo', document.querySelector('#popout-vet-license')?.value || '');
        fd.append('vetExperience', document.querySelector('#popout-vet-exp')?.value || '');
        fd.append('vetContact', document.querySelector('#popout-vet-contact')?.value || '');
        fd.append('clinicBranch', document.querySelector('#popout-clinic-branch')?.value || '');
      }
      if (fileInput.files && fileInput.files[0]) fd.append('pic', fileInput.files[0]);

      // Choose endpoint
      const endpoint = '/backend/edit-' + (originalRole === 'vet' ? 'vet' : (originalRole === 'admin' ? 'admin' : 'client')) + '.php';
      // debug
      try{
        const debugEntries = {};
        for (let pair of fd.entries()) debugEntries[pair[0]] = (pair[1] instanceof File) ? pair[1].name : pair[1];
        console.log('user-popout: sending to', endpoint, debugEntries);
      } catch(e){ console.error(e); }

      try{
        const res = await fetch(endpoint, { method: 'POST', body: fd, credentials: 'same-origin' });
        const json = await res.json().catch(()=> ({ status: 'error', message: 'Invalid JSON' }));
        console.log('user-popout: response', res.status, json);
        Swal.close();
        if (res.ok && json.status === 'success'){
          Swal.fire({ title: 'Saved!', text: json.message || 'Updated', icon: 'success', timer: 700, showConfirmButton: false });
          setTimeout(()=> { hidePopout(popout); setTimeout(()=> location.reload(), 250); }, 600);
        } else {
          Swal.fire({ title: 'Error', text: json.message || 'Save failed', icon: 'error' });
        }
      } catch(err){
        console.error('user-popout: network', err);
        Swal.close();
        Swal.fire({ title: 'Network Error', text: 'Could not connect to server', icon: 'error' });
      }
    });
  });
})();
