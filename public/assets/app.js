const input = document.querySelector("[data-search]");
const rows = Array.from(document.querySelectorAll("tbody tr"));

if (input) {
  input.addEventListener("input", () => {
    const q = input.value.trim().toLowerCase();
    rows.forEach((row) => {
      const text = row.innerText.toLowerCase();
      row.style.display = text.includes(q) ? "" : "none";
    });
  });
}

const autofillBtn = document.querySelector("[data-autofill]");
if (autofillBtn) {
  const statusEl = document.querySelector("[data-autofill-status]");
  const registrarEl = document.querySelector("[data-field-registrar]");
  const expiresEl = document.querySelector("[data-field-expires]");
  const domainEl = document.querySelector("#domain");
  const drawerDomainEl = document.querySelector("#d-domain");

  autofillBtn.addEventListener("click", async () => {
    const domain = drawerDomainEl ? drawerDomainEl.value.trim() : (domainEl ? domainEl.value.trim() : "");
    const i18n = window.I18N || {};
    if (!domain) {
      if (statusEl) statusEl.textContent = i18n.autofill_enter_domain || "Enter a domain first.";
      return;
    }
    autofillBtn.disabled = true;
    if (statusEl) statusEl.textContent = i18n.autofill_lookup || "Looking up RDAP...";
    try {
      const res = await fetch(`rdap.php?domain=${encodeURIComponent(domain)}`);
      const data = await res.json();
      if (data.ok) {
        if (registrarEl && data.registrar) registrarEl.value = data.registrar;
        if (expiresEl && data.expiration) expiresEl.value = data.expiration;
        if (statusEl) statusEl.textContent = i18n.autofill_done || "Auto-fill done.";
      } else {
        if (statusEl) statusEl.textContent = data.error || i18n.autofill_failed || "Auto-fill failed.";
      }
    } catch (e) {
      if (statusEl) statusEl.textContent = i18n.autofill_failed || "Auto-fill failed.";
    } finally {
      autofillBtn.disabled = false;
    }
  });
}

const drawer = document.querySelector("[data-drawer]");
const drawerBackdrop = document.querySelector("[data-drawer-backdrop]");
const settingsDrawer = document.querySelector("[data-settings-drawer]");
const drawerTitle = document.querySelector("[data-drawer-title]");
const drawerClose = document.querySelector("[data-drawer-close]");
const form = drawer ? drawer.querySelector("form") : null;

const openDrawer = (mode, data = {}) => {
  if (!drawer || !drawerBackdrop || !form) return;
  const deleteForm = drawer.querySelector(".drawer-footer");
  drawer.classList.add("open");
  drawerBackdrop.classList.add("open");
  if (drawerTitle) drawerTitle.textContent = mode === "edit" ? "Edit domain" : "Add domain";
  form.querySelector("input[name='original_domain']").value = data.domain || "";
  form.querySelector("#d-domain").value = data.domain || "";
  form.querySelector("#d-project").value = data.project || "";
  form.querySelector("#d-registrar").value = data.registrar || "";
  form.querySelector("#d-expires").value = data.expires || "";
  form.querySelector("#d-status").value = data.status || "Active";
  form.querySelector("#d-email").value = data.email || "";
  if (deleteForm) {
    deleteForm.style.display = mode === "edit" ? "block" : "none";
    const delInput = deleteForm.querySelector("input[name='domain']");
    if (delInput) delInput.value = data.domain || "";
  }
};

const closeDrawer = () => {
  if (!drawer || !drawerBackdrop) return;
  drawer.classList.remove("open");
  drawerBackdrop.classList.remove("open");
};

const openSettings = () => {
  if (!settingsDrawer || !drawerBackdrop) return;
  settingsDrawer.classList.add("open");
  drawerBackdrop.classList.add("open");
};

const closeSettings = () => {
  if (!settingsDrawer || !drawerBackdrop) return;
  settingsDrawer.classList.remove("open");
  drawerBackdrop.classList.remove("open");
};

document.querySelectorAll("[data-drawer-open]").forEach((btn) => {
  btn.addEventListener("click", () => {
    const mode = btn.getAttribute("data-drawer-open");
    if (mode === "edit") {
      openDrawer("edit", {
        domain: btn.getAttribute("data-domain") || "",
        project: btn.getAttribute("data-project") || "",
        registrar: btn.getAttribute("data-registrar") || "",
        expires: btn.getAttribute("data-expires") || "",
        status: btn.getAttribute("data-status") || "",
        email: btn.getAttribute("data-email") || "",
      });
    } else {
      openDrawer("add");
    }
  });
});

if (drawerClose) {
  drawerClose.addEventListener("click", closeDrawer);
}
if (drawerBackdrop) {
  drawerBackdrop.addEventListener("click", () => {
    closeDrawer();
    closeSettings();
  });
}

const settingsOpen = document.querySelector("[data-settings-open]");
const settingsClose = document.querySelector("[data-settings-close]");
if (settingsOpen) settingsOpen.addEventListener("click", openSettings);
if (settingsClose) settingsClose.addEventListener("click", closeSettings);
