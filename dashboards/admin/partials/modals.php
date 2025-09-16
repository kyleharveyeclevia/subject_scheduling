<?php /* Shared centered Notice and Confirm modals for Admin pages */ ?>
<!-- Centered Notification Modal -->
<div id="notificationModal" class="modal" style="display:none;">
  <div class="notice-box" role="dialog" aria-modal="true" aria-live="polite">
    <div class="notice-header">
      <i id="notificationIcon" class="fas fa-info-circle info" aria-hidden="true"></i>
      <span id="notificationText"></span>
    </div>
    <div class="notice-actions">
      <button class="btn btn-primary" onclick="uiCloseNotice()" style="min-width: 80px; padding: 12px 24px; cursor: pointer; font-weight: 500;">OK</button>
    </div>
  </div>
</div>
<!-- Centered Confirm Modal -->
<div id="confirmModal" class="modal" style="display:none;">
  <div class="notice-box" role="dialog" aria-modal="true" aria-live="assertive">
    <div class="notice-header">
      <i id="confirmIcon" class="fas fa-exclamation-triangle" style="color:#dc2626" aria-hidden="true"></i>
      <span id="confirmText"></span>
    </div>
    <div class="notice-actions" style="gap:.5rem; justify-content:center;">
      <button id="confirmYes" class="btn btn-danger">Confirm</button>
      <button id="confirmNo" class="btn btn-secondary">Cancel</button>
    </div>
  </div>
</div>
<style>
  /* Shared modal styling (matches Teacher Dashboard style) */
  #notificationModal, #confirmModal {
    position: fixed; inset: 0; display: none; align-items: center; justify-content: center;
    z-index: 3000; background: rgba(17,24,39,.45); backdrop-filter: blur(1px); padding: 1rem;
  }
  #confirmModal { z-index: 3100; }
  #notificationModal .notice-box, #confirmModal .notice-box {
    background: #fff; width: min(520px, 92vw); border-radius: 14px;
    border: 1px solid rgba(229,231,235,.9); box-shadow: 0 20px 60px rgba(0,0,0,.25);
    padding: 1rem 1.25rem; text-align: center;
  }
  #confirmModal .notice-box { width: min(480px, 92vw); }
  .notice-header { display: flex; align-items: center; justify-content: center; gap: .5rem; margin-bottom: .5rem; }
  #notificationIcon.info { color: #2563eb; }
  #notificationIcon.success { color: #16a34a; }
  #notificationIcon.error { color: #dc2626; }
  #notificationIcon.warning { color: #ca8a04; }
  .notice-actions { margin-top: .75rem; display: flex; justify-content: center; }
</style>
<script>
  // Show notice modal with type: info | success | error | warning
  // Namespaced to avoid collisions with page-specific helpers
  function uiShowNotice(message, type) {
    if (!message) { return; }
    const modal = document.getElementById('notificationModal');
    const icon = document.getElementById('notificationIcon');
    const text = document.getElementById('notificationText');
    const cls = ['info','success','error','warning'];
    cls.forEach(c => icon.classList.remove(c));
    const t = (type || 'info').toLowerCase();
    icon.classList.add(cls.includes(t) ? t : 'info');
    icon.className = 'fas ' + (t==='success' ? 'fa-check-circle' : t==='error' ? 'fa-exclamation-circle' : t==='warning' ? 'fa-exclamation-triangle' : 'fa-info-circle') + ' ' + (cls.includes(t)? t : 'info');
    text.textContent = String(message || '');
    modal.style.display = 'flex';
    clearTimeout(window.__noticeTimer);
    window.__noticeTimer = setTimeout(() => { try { uiCloseNotice(); } catch(e){} }, 5000);
  }

  function uiCloseNotice() {
    const modal = document.getElementById('notificationModal');
    if (modal) {
      modal.style.display = 'none';
      // Clear any pending auto-close timer
      clearTimeout(window.__noticeTimer);
    }
  }

  // Show confirm modal with options: { confirmText, cancelText, tone }
  // Returns Promise<boolean>
  function uiShowConfirm(message, options = {}) {
    return new Promise((resolve) => {
      const modal = document.getElementById('confirmModal');
      const text = document.getElementById('confirmText');
      const yes = document.getElementById('confirmYes');
      const no = document.getElementById('confirmNo');
      const icon = document.getElementById('confirmIcon');

      text.textContent = String(message || 'Are you sure?');
      yes.textContent = options.confirmText || 'Confirm';
      no.textContent = options.cancelText || 'Cancel';
      const tone = (options.tone || 'danger').toLowerCase();
      // style confirm button per tone
      yes.className = 'btn ' + (tone === 'danger' ? 'btn-danger' : 'btn-primary');
      // icon color
      icon.style.color = tone === 'danger' ? '#dc2626' : '#2563eb';

      modal.style.display = 'flex';

      const cleanup = () => {
        yes.removeEventListener('click', onYes);
        no.removeEventListener('click', onNo);
        document.removeEventListener('keydown', onKey);
        modal.removeEventListener('click', onBackdrop);
      };
      const onYes = () => { cleanup(); modal.style.display = 'none'; resolve(true); };
      const onNo = () => { cleanup(); modal.style.display = 'none'; resolve(false); };
      const onKey = (e) => { if (e.key === 'Escape') onNo(); if (e.key === 'Enter') onYes(); };
      const onBackdrop = (e) => { if (e.target === modal) onNo(); };

      yes.addEventListener('click', onYes);
      no.addEventListener('click', onNo);
      document.addEventListener('keydown', onKey);
      modal.addEventListener('click', onBackdrop);
    });
  }
</script>
