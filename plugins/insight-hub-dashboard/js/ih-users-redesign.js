(function () {
  "use strict";
  const root = document.getElementById("ihUsersRedesign");
  if (!root) return;

  const table = root.querySelector("#usersTable");
  const drawer = document.getElementById("ihUserDrawer");
  const scrim = document.getElementById("ihUserScrim");
  const drawerBody = drawer ? drawer.querySelector(".body") : null;
  const searchInput = root.querySelector("#userSearch");
  const tabButtons = root.querySelectorAll("#userTabGroup .ih-tab");
  const columnButton = root.querySelector("#ihUsersColumnButton");
  const columnPanel = root.querySelector("#ihUsersColumnPanel");
  const exportBtn = document.getElementById("ihUsersExportCsv");
  const footerMeta = document.getElementById("ihUsersFooterMeta");
  const mobileResultsCount = document.getElementById("ihUsersMobileResultsCount");
  const config = window.ihUsersRedesign || {};
  const ajaxUrl = config.ajaxUrl || (window.ihAjax && window.ihAjax.url) || "";
  const nonce = config.nonce || (window.ihAjax && window.ihAjax.nonce) || "";
  const state = { tab: "All", query: "", selectedUid: null, lastFocus: null };
  let floatingMenu = null;
  let focusTrapHandler = null;

  function rows() { return Array.from(table.querySelectorAll("tbody tr[data-user-row]")); }
  function mobileCards() { return Array.from(root.querySelectorAll(".ih-users-mobile-card[data-user-row]")); }

  function setSelected(uid) {
    const id = uid == null ? null : String(uid);
    rows().forEach(r => r.classList.toggle("is-selected", id !== null && r.dataset.userRow === id));
    mobileCards().forEach(c => c.classList.toggle("is-selected", id !== null && c.dataset.userRow === id));
  }

  function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" }[c]));
  }

  function focusableIn(el) {
    if (!el) return [];
    return Array.from(el.querySelectorAll(
      'a[href], button:not([disabled]), textarea, input:not([type="hidden"]), select, [tabindex]:not([tabindex="-1"])'
    )).filter(node => node.offsetParent !== null || node === drawerBody);
  }

  function trapFocus(e) {
    if (!drawer || !drawer.classList.contains("open")) return;
    const nodes = focusableIn(drawer);
    if (!nodes.length) return;
    const first = nodes[0];
    const last = nodes[nodes.length - 1];
    if (e.key === "Tab") {
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
  }

  function enableFocusTrap() {
    if (focusTrapHandler) return;
    focusTrapHandler = trapFocus;
    document.addEventListener("keydown", focusTrapHandler);
  }

  function disableFocusTrap() {
    if (!focusTrapHandler) return;
    document.removeEventListener("keydown", focusTrapHandler);
    focusTrapHandler = null;
  }

  window.ihOpenUser = function (uid) {
    if (!drawer || !drawerBody || !uid) return;
    const trigger = document.activeElement;
    if (trigger && root.contains(trigger)) state.lastFocus = trigger;
    const fd = new FormData();
    fd.append("action", "ih_user_record");
    fd.append("uid", uid);
    fd.append("nonce", nonce);
    drawerBody.innerHTML = '<p class="ih-u-loading">Loading record…</p>';
    drawer.classList.add("open");
    if (scrim) {
      scrim.classList.add("open");
      scrim.setAttribute("aria-hidden", "false");
    }
    drawer.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
    setSelected(uid);
    state.selectedUid = uid;
    drawerBody.focus();
    enableFocusTrap();
    fetch(ajaxUrl, { method: "POST", body: fd })
      .then(r => r.text())
      .then(html => {
        drawerBody.innerHTML = html;
        const first = focusableIn(drawer)[0];
        if (first) first.focus();
      })
      .catch(() => { drawerBody.innerHTML = '<p class="ih-u-empty">Could not load user record.</p>'; });
  };

  window.ihCloseUser = function () {
    if (!drawer || !drawer.classList.contains("open")) return;
    drawer.classList.remove("open");
    if (scrim) {
      scrim.classList.remove("open");
      scrim.setAttribute("aria-hidden", "true");
    }
    drawer.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
    setSelected(null);
    state.selectedUid = null;
    disableFocusTrap();
    if (state.lastFocus && typeof state.lastFocus.focus === "function") {
      state.lastFocus.focus();
      state.lastFocus = null;
    }
  };

  function closeMenus() {
    document.querySelectorAll(".ih-redesign-dropdown").forEach(m => {
      m.classList.add("hidden");
      m.classList.remove("is-floating");
      m.style.cssText = "";
      if (m.__originalParent && m.parentNode !== m.__originalParent) {
        m.__originalParent.appendChild(m);
      }
    });
    root.querySelectorAll(".ih-users-action-btn").forEach(b => b.classList.remove("is-active"));
    rows().forEach(r => r.classList.remove("ih-menu-open"));
    floatingMenu = null;
  }

  function positionMenu(menu, button) {
    if (!menu || !button) return;
    if (!menu.__originalParent) menu.__originalParent = menu.parentNode;
    document.body.appendChild(menu);
    menu.classList.remove("hidden");
    menu.classList.add("is-floating");
    menu.style.cssText = "position:fixed;visibility:hidden;display:block;left:-9999px;top:-9999px;";
    void menu.offsetHeight;
    const btnRect = button.getBoundingClientRect();
    const menuW = menu.offsetWidth || 230;
    const menuH = menu.offsetHeight || 200;
    let left = Math.max(8, btnRect.right - menuW);
    let top = btnRect.bottom + 6;
    if (top + menuH > window.innerHeight - 8) top = Math.max(8, btnRect.top - menuH - 6);
    if (left + menuW > window.innerWidth - 8) left = Math.max(8, window.innerWidth - menuW - 8);
    menu.style.left = left + "px";
    menu.style.top = top + "px";
    menu.style.visibility = "";
    floatingMenu = menu;
  }

  function toggleMenu(id, button) {
    const scope = button ? (button.closest(".ih-row-menu") || root) : root;
    const menu = scope.querySelector('.ih-redesign-dropdown[data-menu-for="' + id + '"]');
    const open = menu && !menu.classList.contains("hidden");
    closeMenus();
    if (!open && menu) {
      const row = button.closest("tr[data-user-row]");
      if (row) row.classList.add("ih-menu-open");
      button.classList.add("is-active");
      positionMenu(menu, button);
    }
  }

  function itemVisible(item, q) {
    let tabOk = true;
    if (state.tab === "Blocked") tabOk = item.dataset.blocked === "true";
    else if (state.tab === "Active") tabOk = item.dataset.blocked === "false";
    else if (state.tab === "New") tabOk = item.dataset.new === "true";
    const searchOk = (item.dataset.search || "").includes(q);
    return tabOk && searchOk;
  }

  function updateCounts(visible) {
    const label = visible + " user" + (visible === 1 ? "" : "s");
    if (mobileResultsCount) mobileResultsCount.textContent = label;
    if (footerMeta) footerMeta.textContent = "USERS · " + visible + " TOTAL";
  }

  function applyFilters() {
    const q = state.query.toLowerCase();
    const seen = new Set();
    let visible = 0;
    [...rows(), ...mobileCards()].forEach(item => {
      const show = itemVisible(item, q);
      item.style.display = show ? "" : "none";
      const id = item.dataset.userRow;
      if (show && id && !seen.has(id)) {
        seen.add(id);
        visible++;
      }
    });
    updateCounts(visible);
  }

  function blockUser(button) {
    const uid = button.dataset.uid;
    const blocked = button.dataset.blocked === "1";
    if (!confirm((blocked ? "Unblock" : "Block") + " this user?")) return;
    const fd = new FormData();
    fd.append("action", "ih_block_user");
    fd.append("nonce", nonce);
    fd.append("user_id", uid);
    fd.append("block", blocked ? "0" : "1");
    fetch(ajaxUrl, { method: "POST", body: fd })
      .then(r => r.json())
      .then(d => { if (d && d.success) location.reload(); });
  }

  function buildColumnControls() {
    if (!columnPanel) return;
    const cols = Array.from(table.querySelectorAll("thead th[data-col]")).filter(th => th.dataset.col !== "actions" && th.dataset.col !== "select");
    columnPanel.innerHTML = '<div class="ih-column-panel-head"><span>Adjust columns</span></div><div class="ih-column-controls">' +
      cols.map(th => {
        const col = th.dataset.col;
        return '<div class="ih-column-control"><label><input type="checkbox" checked data-column-visible="' + col + '"> ' + escapeHtml(th.textContent.trim() || col) + '</label></div>';
      }).join("") + '</div>';
  }

  function setColumnVisible(col, visible) {
    table.querySelectorAll('[data-col="' + col + '"]').forEach(cell => cell.classList.toggle("ih-column-hidden", !visible));
  }

  function exportCsv() {
    const headers = ["Name", "Platform ID", "Unique ID", "Email", "Company", "Role", "Location", "Listings", "Requests", "Completion", "Status"];
    const lines = [headers.join(",")];
    rows().forEach(row => {
      if (row.style.display === "none") return;
      let user = {};
      try { user = JSON.parse(row.dataset.user || "{}"); } catch (e) {}
      const vals = [
        user.name, user.platformRef, user.uniqueId, user.email, user.companyName, user.businessRole,
        user.townCity, (user.machines || 0) + (user.tools || 0), user.requests, user.completion, user.status
      ].map(v => '"' + String(v ?? "").replace(/"/g, '""') + '"');
      lines.push(vals.join(","));
    });
    const blob = new Blob([lines.join("\n")], { type: "text/csv" });
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = "ih-users-export.csv";
    a.click();
  }

  root.addEventListener("click", e => {
    const block = e.target.closest(".ih-block-user-btn");
    const viewRec = e.target.closest(".ih-view-record, .ih-users-mobile-view-btn");
    const rowMenu = e.target.closest("[data-row-menu]");
    const row = e.target.closest("tr[data-user-row]");
    if (block) { e.preventDefault(); e.stopPropagation(); blockUser(block); return; }
    if (viewRec && viewRec.dataset.userRow) { e.preventDefault(); ihOpenUser(viewRec.dataset.userRow); closeMenus(); return; }
    if (rowMenu) { e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); toggleMenu(rowMenu.dataset.rowMenu, rowMenu); return; }
    if (e.target.closest(".ih-redesign-dropdown, input, button, a, label")) return;
    if (row && !e.target.closest('input[type="checkbox"]')) {
      ihOpenUser(row.dataset.userRow);
      closeMenus();
    } else if (!e.target.closest(".ih-redesign-dropdown")) {
      closeMenus();
    }
  });

  document.addEventListener("click", e => {
    if (e.target.closest(".ih-u-drawer .ih-block-user-btn")) {
      e.preventDefault();
      e.stopPropagation();
      blockUser(e.target.closest(".ih-block-user-btn"));
      return;
    }
    if (e.target.closest(".ih-redesign-dropdown .ih-block-user-btn")) {
      e.preventDefault();
      e.stopPropagation();
      blockUser(e.target.closest(".ih-block-user-btn"));
      return;
    }
    if (e.target.closest(".ih-redesign-dropdown")) return;
    if (floatingMenu && !root.contains(e.target)) closeMenus();
  }, true);

  if (scrim) scrim.addEventListener("click", ihCloseUser);
  const closeBtn = document.getElementById("ihUserDrawerClose");
  if (closeBtn) closeBtn.addEventListener("click", ihCloseUser);
  document.addEventListener("keydown", e => {
    if (e.key === "Escape") {
      if (drawer && drawer.classList.contains("open")) {
        e.preventDefault();
        ihCloseUser();
      } else {
        closeMenus();
      }
    }
  });

  tabButtons.forEach(btn => btn.addEventListener("click", () => {
    tabButtons.forEach(b => {
      b.classList.remove("active", "is-active");
      b.setAttribute("aria-selected", "false");
    });
    btn.classList.add("active", "is-active");
    btn.setAttribute("aria-selected", "true");
    state.tab = btn.dataset.tab;
    applyFilters();
  }));

  if (searchInput) searchInput.addEventListener("input", () => { state.query = searchInput.value; applyFilters(); });
  if (exportBtn) exportBtn.addEventListener("click", e => { e.preventDefault(); exportCsv(); });

  if (columnButton && columnPanel) {
    buildColumnControls();
    columnButton.addEventListener("click", () => {
      const open = columnPanel.classList.toggle("hidden") === false;
      columnPanel.setAttribute("aria-hidden", open ? "false" : "true");
      columnButton.setAttribute("aria-expanded", open ? "true" : "false");
    });
    columnPanel.addEventListener("change", e => {
      const visible = e.target.closest("[data-column-visible]");
      if (visible) setColumnVisible(visible.dataset.columnVisible, visible.checked);
    });
  }

  window.addEventListener("resize", closeMenus);
  window.addEventListener("scroll", closeMenus, true);
  applyFilters();
})();
