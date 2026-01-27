const input = document.querySelector("[data-search]");
const rows = Array.from(document.querySelectorAll("tbody tr"));
const daysFilter = document.querySelector("[data-days-filter]");
const toast = document.querySelector("[data-expiring-toast]");
const sortButtons = Array.from(document.querySelectorAll(".sort-btn"));
const shortcuts = document.querySelector("[data-shortcuts]");
const shortcutsClose = document.querySelector("[data-shortcuts-close]");

document.querySelectorAll("img.registrar-logo").forEach((img) => {
  img.addEventListener("error", () => {
    const alt = img.getAttribute("data-alt-src");
    if (alt && img.src !== alt) {
      img.src = alt;
      return;
    }
    img.style.display = "none";
  });
});

if (input) {
  input.addEventListener("input", () => {
    const q = input.value.trim().toLowerCase();
    rows.forEach((row) => {
      const text = row.innerText.toLowerCase();
      row.dataset.searchMatch = text.includes(q) ? "1" : "0";
      row.style.display = text.includes(q) ? "" : "none";
    });
  });
}

const getDaysValue = (row) => {
  const cell = row.querySelector("td:nth-child(4)");
  const val = cell ? parseInt(cell.textContent.trim(), 10) : NaN;
  return isNaN(val) ? null : val;
};

const updateToast = () => {
  if (!toast || !daysFilter) return;
  const threshold = parseInt(daysFilter.value, 10);
  const count = rows.filter((row) => {
    const days = getDaysValue(row);
    return days !== null && days < threshold;
  }).length;
  const i18n = window.I18N || {};
  const template = i18n.expiring_badge || "{count} expiring ≤ {threshold}d";
  toast.textContent = template
    .replace("{count}", String(count))
    .replace("{threshold}", String(threshold));
  toast.classList.remove("t180", "t90", "t60", "t30");
  if (threshold <= 30) toast.classList.add("t30");
  else if (threshold <= 60) toast.classList.add("t60");
  else if (threshold <= 90) toast.classList.add("t90");
  else toast.classList.add("t180");
};

const applyFilters = () => {
  const q = input ? input.value.trim().toLowerCase() : "";
  const maxDays = daysFilter && daysFilter.value !== "all" ? parseInt(daysFilter.value, 10) : null;
  rows.forEach((row) => {
    const text = row.innerText.toLowerCase();
    const matchesSearch = q === "" || text.includes(q);
    const daysVal = getDaysValue(row);
    const matchesDays = maxDays === null || (daysVal !== null && daysVal < maxDays);
    row.style.display = matchesSearch && matchesDays ? "" : "none";
  });
};

if (input) input.addEventListener("input", applyFilters);
if (daysFilter) daysFilter.addEventListener("change", () => {
  applyFilters();
  updateToast();
});
updateToast();

const refreshThumbs = () => {
  const rowsMissing = Array.from(document.querySelectorAll("[data-thumb-missing='1']"));
  rowsMissing.forEach((placeholder) => {
    const row = placeholder.closest("tr");
    const domain = row ? row.getAttribute("data-domain") : null;
    if (!domain) return;
    const key = "thumb_attempt_" + domain;
    const last = Number(localStorage.getItem(key) || "0");
    const now = Date.now();
    if (now - last < 24 * 60 * 60 * 1000) return;
    localStorage.setItem(key, String(now));
    const base = window.BASE_URL || "";
    fetch(`${base}/public/thumb.php?domain=${encodeURIComponent(domain)}`)
      .then((r) => r.json())
      .then((data) => {
        if (data && data.ok && data.url) {
          const img = document.createElement("img");
          img.className = "thumb";
          img.src = data.url;
          img.setAttribute("data-thumb-loaded", "1");
          placeholder.replaceWith(img);
        }
      })
      .catch(() => {});
  });
};
refreshThumbs();

let sortState = { key: null, dir: "asc" };
const sortKeyIndex = {
  domain: 1,
  registrar: 2,
  expiration: 3,
  days: 4,
  status: 5,
  email: 6,
  project: 7,
};

const cellValue = (row, key) => {
  const idx = sortKeyIndex[key];
  const cell = row.querySelector(`td:nth-child(${idx})`);
  if (!cell) return "";
  if (key === "days") {
    const val = parseInt(cell.textContent.trim(), 10);
    return isNaN(val) ? Number.MAX_SAFE_INTEGER : val;
  }
  if (key === "expiration") {
    const t = cell.textContent.trim();
    return t === "" ? "9999-12-31" : t;
  }
  return cell.textContent.trim().toLowerCase();
};

const sortRows = (key, dir) => {
  const tbody = document.querySelector("tbody");
  if (!tbody) return;
  const list = [...rows];
  list.sort((a, b) => {
    const va = cellValue(a, key);
    const vb = cellValue(b, key);
    if (va < vb) return dir === "asc" ? -1 : 1;
    if (va > vb) return dir === "asc" ? 1 : -1;
    return 0;
  });
  list.forEach((r) => tbody.appendChild(r));
};

sortButtons.forEach((btn) => {
  btn.addEventListener("click", () => {
    const key = btn.getAttribute("data-sort");
    const dir = sortState.key === key && sortState.dir === "asc" ? "desc" : "asc";
    sortState = { key, dir };
    sortButtons.forEach((b) => b.classList.remove("asc", "desc"));
    btn.classList.add(dir);
    sortRows(key, dir);
  });
});

let selectedIndex = -1;
const visibleRows = () => rows.filter((r) => r.style.display !== "none");
const selectRow = (idx) => {
  const list = visibleRows();
  if (!list.length) return;
  const clamped = Math.max(0, Math.min(idx, list.length - 1));
  list.forEach((r) => r.classList.remove("selected"));
  const row = list[clamped];
  row.classList.add("selected");
  row.focus();
  selectedIndex = clamped;
};

const openEditForRow = (row) => {
  if (!row) return;
  openDrawer("edit", {
    domain: row.getAttribute("data-domain") || "",
    project: row.getAttribute("data-project") || "",
    registrar: row.getAttribute("data-registrar") || "",
    expires: row.getAttribute("data-expires") || "",
    status: row.getAttribute("data-status") || "",
    email: row.getAttribute("data-email") || "",
  });
};

const deleteRow = (row) => {
  if (!row) return;
  const domain = row.getAttribute("data-domain") || "";
  if (!domain) return;
  if (!confirm((window.I18N && window.I18N.delete_confirm) || "Delete this domain?")) return;
  const form = document.createElement("form");
  form.method = "post";
  const base = window.BASE_URL || "";
  form.action = `${base}/public/delete.php`;
  const input = document.createElement("input");
  input.type = "hidden";
  input.name = "domain";
  input.value = domain;
  form.appendChild(input);
  document.body.appendChild(form);
  form.submit();
};

const openShortcuts = () => {
  if (!shortcuts) return;
  shortcuts.classList.add("open");
};
const closeShortcuts = () => {
  if (!shortcuts) return;
  shortcuts.classList.remove("open");
};
if (shortcutsClose) shortcutsClose.addEventListener("click", closeShortcuts);

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
      const base = window.BASE_URL || "";
      const res = await fetch(`${base}/public/rdap.php?domain=${encodeURIComponent(domain)}`);
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
  const focusEl = form.querySelector("#d-domain");
  if (focusEl) {
    setTimeout(() => focusEl.focus(), 0);
  }
};

const formatDateValue = (raw) => {
  let v = raw.replace(/[^\d]/g, "").slice(0, 8);
  if (v.length >= 5) v = v.slice(0, 4) + "-" + v.slice(4);
  if (v.length >= 8) v = v.slice(0, 7) + "-" + v.slice(7);
  return v;
};

const setupDateInput = (wrap) => {
  const inputEl = wrap.querySelector(".date-input");
  const nativeEl = wrap.querySelector(".date-native");
  const btn = wrap.querySelector("[data-date-btn]");
  if (!inputEl || !nativeEl) return;

  inputEl.addEventListener("input", () => {
    inputEl.value = formatDateValue(inputEl.value);
    if (/^\d{4}-\d{2}-\d{2}$/.test(inputEl.value)) {
      nativeEl.value = inputEl.value;
    }
  });

  if (btn) {
    btn.addEventListener("click", () => {
      if (typeof nativeEl.showPicker === "function") {
        nativeEl.showPicker();
      } else {
        nativeEl.focus();
        nativeEl.click();
      }
    });
  }

  nativeEl.addEventListener("change", () => {
    if (nativeEl.value) {
      inputEl.value = nativeEl.value;
    }
  });
};

document.querySelectorAll(".date-wrap").forEach(setupDateInput);

document.querySelectorAll("form").forEach((form) => {
  form.addEventListener("submit", (e) => {
    const dateInputs = form.querySelectorAll(".date-input");
    for (const el of dateInputs) {
      if (el.value && !/^\d{4}-\d{2}-\d{2}$/.test(el.value)) {
        e.preventDefault();
        el.focus();
        el.select();
        return;
      }
    }
  });
});

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
    closeShortcuts();
  });
}

const settingsOpen = document.querySelector("[data-settings-open]");
const settingsClose = document.querySelector("[data-settings-close]");
if (settingsOpen) settingsOpen.addEventListener("click", openSettings);
if (settingsClose) settingsClose.addEventListener("click", closeSettings);

const testMailBtn = document.querySelector("[data-test-mail]");
const testMailStatus = document.querySelector("[data-test-mail-status]");
if (testMailBtn) {
  testMailBtn.addEventListener("click", async () => {
    const base = window.BASE_URL || "";
    testMailBtn.disabled = true;
    if (testMailStatus) testMailStatus.textContent = "Envoi du test…";
    try {
      const res = await fetch(`${base}/public/test_mail.php`);
      const data = await res.json();
      if (data && data.ok) {
        if (testMailStatus) testMailStatus.textContent = `Email test envoyé à ${data.to || ""}`.trim();
      } else {
        const err = data && data.error ? data.error : "Échec de l'envoi";
        if (testMailStatus) testMailStatus.textContent = err;
      }
    } catch (e) {
      if (testMailStatus) testMailStatus.textContent = "Échec de l'envoi";
    } finally {
      testMailBtn.disabled = false;
    }
  });
}

document.addEventListener("keydown", (e) => {
  const isInput = ["INPUT", "TEXTAREA", "SELECT"].includes(document.activeElement?.tagName || "");
  if (e.key === "Escape") {
    closeDrawer();
    closeSettings();
    closeShortcuts();
    return;
  }
  if (e.key === "?" && !isInput) {
    e.preventDefault();
    openShortcuts();
    return;
  }
  if (isInput) return;
  if (e.key === "a" || e.key === "A") {
    e.preventDefault();
    openDrawer("add");
    return;
  }
  if (e.key === "o" || e.key === "O") {
    e.preventDefault();
    openSettings();
    return;
  }
  if (e.key === "/") {
    e.preventDefault();
    if (input) input.focus();
    return;
  }
  if (e.key === "ArrowDown") {
    e.preventDefault();
    selectRow(selectedIndex + 1);
    return;
  }
  if (e.key === "ArrowUp") {
    e.preventDefault();
    selectRow(selectedIndex - 1);
    return;
  }
  if (e.key === "Enter") {
    const list = visibleRows();
    const row = list[selectedIndex] || list[0];
    if (row) {
      e.preventDefault();
      openEditForRow(row);
    }
    return;
  }
  if (e.key === "Delete" || e.key === "Backspace") {
    const list = visibleRows();
    const row = list[selectedIndex] || list[0];
    if (row) {
      e.preventDefault();
      deleteRow(row);
    }
  }
});
