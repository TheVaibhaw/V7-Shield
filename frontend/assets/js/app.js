(() => {
  "use strict";

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const store = {
    get(k) {
      try {
        return localStorage.getItem(k);
      } catch {
        return null;
      }
    },
    set(k, v) {
      try {
        localStorage.setItem(k, v);
      } catch {
        /* storage unavailable */
      }
    },
  };
  const escapeHtml = (s) =>
    String(s).replace(
      /[&<>"']/g,
      (c) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        })[c],
    );

  const toastIcons = {
    success: "check-circle-fill",
    error: "x-octagon-fill",
    info: "info-circle-fill",
    warning: "exclamation-triangle-fill",
  };
  function toast(message, type = "info", timeout = 4200) {
    const box = $("#toasts");
    if (!box) return;
    const el = document.createElement("div");
    el.className = `toast ${type}`;
    el.setAttribute("role", type === "error" ? "alert" : "status");
    el.innerHTML = `<i class="bi bi-${toastIcons[type] || toastIcons.info}" aria-hidden="true"></i><span>${escapeHtml(message)}</span><button type="button" aria-label="Dismiss">&times;</button>`;
    el.querySelector("button").addEventListener("click", () => el.remove());
    box.appendChild(el);
    setTimeout(() => el.remove(), timeout);
  }

  function confirmDialog(
    text,
    { title = "Are you sure?", ok = "Confirm" } = {},
  ) {
    const modal = $("#confirm-modal");
    if (!modal) return Promise.resolve(window.confirm(text));
    $("#confirm-title", modal).textContent = title;
    $("#confirm-text", modal).textContent = text;
    $("[data-confirm-ok]", modal).textContent = ok;
    const prev = document.activeElement;
    modal.hidden = false;
    $("[data-confirm-cancel]", modal).focus();
    return new Promise((resolve) => {
      const done = (val) => {
        modal.hidden = true;
        modal.removeEventListener("click", onClick);
        document.removeEventListener("keydown", onKey);
        prev && prev.focus();
        resolve(val);
      };
      const onClick = (e) => {
        if (e.target.closest("[data-confirm-ok]")) done(true);
        else if (
          e.target.closest("[data-confirm-cancel]") ||
          e.target === modal
        )
          done(false);
      };
      const onKey = (e) => {
        if (e.key === "Escape") done(false);
      };
      modal.addEventListener("click", onClick);
      document.addEventListener("keydown", onKey);
    });
  }

  let modalReturn = null;
  function openModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    modalReturn = document.activeElement;
    m.hidden = false;
    const f = m.querySelector("input, select, textarea, button");
    f && f.focus();
  }
  function closeModal(m) {
    if (!m || m.hidden) return;
    m.hidden = true;
    modalReturn && modalReturn.focus && modalReturn.focus();
    modalReturn = null;
  }
  document.addEventListener("click", (e) => {
    const opener = e.target.closest("[data-modal-open]");
    if (opener) openModal(opener.dataset.modalOpen);
    const closer = e.target.closest("[data-modal-close]");
    if (closer) closeModal(closer.closest(".modal"));
    if (
      e.target.classList &&
      e.target.classList.contains("modal") &&
      e.target.id !== "confirm-modal"
    )
      closeModal(e.target);
  });

  function setTheme(t) {
    document.documentElement.dataset.theme = t;
    store.set("v7-theme", t);
    document.dispatchEvent(new CustomEvent("v7:theme", { detail: t }));
  }
  $$("[data-theme-toggle]").forEach((b) =>
    b.addEventListener("click", () => {
      setTheme(
        document.documentElement.dataset.theme === "light" ? "dark" : "light",
      );
    }),
  );

  const sidebar = $("#sidebar");
  const backdrop = $(".drawer-backdrop");
  const menuBtn = $("[data-drawer-open]");
  $$("[data-sidebar-toggle]").forEach((b) =>
    b.addEventListener("click", () => {
      const c = document.documentElement.classList.toggle("sb-collapsed");
      store.set("v7-sidebar", c ? "collapsed" : "open");
      b.setAttribute("aria-label", c ? "Expand sidebar" : "Collapse sidebar");
    }),
  );
  function drawer(open) {
    if (!sidebar) return;
    sidebar.classList.toggle("open", open);
    backdrop.hidden = !open;
    menuBtn && menuBtn.setAttribute("aria-expanded", String(open));
    document.body.style.overflow = open ? "hidden" : "";
    if (open) $(".sb-link.active, .sb-link", sidebar).focus();
    else menuBtn && menuBtn.focus();
  }
  menuBtn && menuBtn.addEventListener("click", () => drawer(true));
  $$("[data-drawer-close]").forEach((b) =>
    b.addEventListener("click", () => drawer(false)),
  );

  const groupState = JSON.parse(store.get("v7-groups") || "{}");
  $$(".sb-group").forEach((g) => {
    const btn = $(".sb-group-btn", g);
    const id = btn.getAttribute("aria-controls");
    if (groupState[id] && !g.classList.contains("has-active")) {
      g.classList.add("closed");
      btn.setAttribute("aria-expanded", "false");
    }
    btn.addEventListener("click", () => {
      const closed = g.classList.toggle("closed");
      btn.setAttribute("aria-expanded", String(!closed));
      groupState[id] = closed;
      store.set("v7-groups", JSON.stringify(groupState));
    });
  });

  function closeDropdowns(except) {
    $$(".dropdown.open").forEach((d) => {
      if (d !== except) {
        d.classList.remove("open");
        $("[data-dropdown]", d).setAttribute("aria-expanded", "false");
      }
    });
  }
  $$("[data-dropdown]").forEach((btn) =>
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const dd = btn.closest(".dropdown");
      closeDropdowns(dd);
      const open = dd.classList.toggle("open");
      btn.setAttribute("aria-expanded", String(open));
      if (open) {
        const first = $(".dropdown-menu a, .dropdown-menu button", dd);
        first && first.focus();
      }
    }),
  );
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".dropdown-menu")) closeDropdowns();
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeDropdowns();
      closeDetail();
      if (sidebar && sidebar.classList.contains("open")) drawer(false);
      $$(".modal:not([hidden])").forEach((m) => {
        if (m.id !== "confirm-modal") closeModal(m);
      });
    }
    // "/" focuses the page's search box, if there is one.
    const active = document.activeElement;
    if (
      e.key === "/" &&
      !(active && (/input|textarea|select/i.test(active.tagName) || active.isContentEditable))
    ) {
      const s = $('input[type="search"]');
      if (s) {
        e.preventDefault();
        s.focus();
      }
    }
  });

  $$("select[data-autosubmit]").forEach((sel) =>
    sel.addEventListener("change", () => sel.form && sel.form.submit()),
  );

  $$("[data-theme-choice]").forEach((r) => {
    r.checked = r.value === (document.documentElement.dataset.theme || "dark");
    r.addEventListener("change", () => {
      if (r.checked) setTheme(r.value);
    });
  });
  const compact = $("[data-compact-choice]");
  if (compact) {
    compact.checked = document.documentElement.classList.contains("sb-collapsed");
    compact.addEventListener("change", () => {
      document.documentElement.classList.toggle("sb-collapsed", compact.checked);
      store.set("v7-sidebar", compact.checked ? "collapsed" : "open");
    });
  }

  function initTable(table) {
    const tools = document.querySelector(`[data-table-for="${table.id}"]`);
    const pager = document.querySelector(`[data-pager-for="${table.id}"]`);
    const empty = table.parentElement.querySelector(".empty-state");
    const tbody = table.tBodies[0];
    const rows = Array.from(tbody.rows);
    const perPage = parseInt(table.dataset.perPage || "10", 10);
    const state = { q: "", filters: {}, sortIdx: -1, dir: 1, page: 1 };

    const params = new URLSearchParams(location.search);
    if (tools)
      $$("[data-table-filter]", tools).forEach((s) => {
        const v = params.get(s.dataset.tableFilter);
        if (v && $$("option", s).some((o) => o.value === v.toLowerCase())) {
          s.value = v.toLowerCase();
          state.filters[s.dataset.tableFilter] = s.value;
        }
      });

    function render() {
      let list = rows.filter((r) => {
        if (state.q && !r.textContent.toLowerCase().includes(state.q))
          return false;
        return Object.entries(state.filters).every(
          ([k, v]) => !v || r.dataset[k] === v,
        );
      });
      if (state.sortIdx >= 0) {
        list.sort((a, b) => {
          const cellValue = (row) => {
            const c = row.cells[state.sortIdx];
            return (c ? (c.dataset.value ?? c.textContent) : "").trim();
          };
          const av = cellValue(a);
          const bv = cellValue(b);
          const an = parseFloat(av.replace(/[,%]/g, ""));
          const bn = parseFloat(bv.replace(/[,%]/g, ""));
          const cmp =
            !isNaN(an) && !isNaN(bn) && /^[\d.,%\s-]+$/.test(av + bv)
              ? an - bn
              : av.localeCompare(bv, undefined, { numeric: true });
          return cmp * state.dir;
        });
      }
      const pages = Math.max(1, Math.ceil(list.length / perPage));
      state.page = Math.min(state.page, pages);
      const start = (state.page - 1) * perPage;
      rows.forEach((r) => {
        r.hidden = true;
      });
      list.forEach((r, i) => {
        tbody.appendChild(r);
        r.hidden = i < start || i >= start + perPage;
      });
      if (empty) empty.hidden = list.length > 0;
      if (pager) renderPager(list.length, pages, start);
    }

    function renderPager(total, pages, start) {
      pager.innerHTML = "";
      const info = document.createElement("span");
      info.textContent = total
        ? `Showing ${start + 1}–${Math.min(start + perPage, total)} of ${total}`
        : "No results";
      pager.appendChild(info);
      if (pages <= 1) return;
      const wrap = document.createElement("div");
      wrap.className = "pages";
      const mk = (label, page, opts = {}) => {
        const b = document.createElement("button");
        b.type = "button";
        b.innerHTML = label;
        b.disabled = !!opts.disabled;
        if (opts.current) b.setAttribute("aria-current", "true");
        b.setAttribute("aria-label", opts.aria || `Page ${page}`);
        b.addEventListener("click", () => {
          state.page = page;
          render();
        });
        wrap.appendChild(b);
      };
      mk("&lsaquo;", state.page - 1, {
        disabled: state.page === 1,
        aria: "Previous page",
      });
      for (let p = 1; p <= pages; p++)
        mk(String(p), p, { current: p === state.page });
      mk("&rsaquo;", state.page + 1, {
        disabled: state.page === pages,
        aria: "Next page",
      });
      pager.appendChild(wrap);
    }

    if (tools) {
      const s = $("[data-table-search]", tools);
      s &&
        s.addEventListener("input", () => {
          state.q = s.value.trim().toLowerCase();
          state.page = 1;
          render();
        });
      $$("[data-table-filter]", tools).forEach((sel) =>
        sel.addEventListener("change", () => {
          state.filters[sel.dataset.tableFilter] = sel.value;
          state.page = 1;
          render();
        }),
      );
    }
    $$("th[data-sort]", table).forEach((th) => {
      const sort = () => {
        const idx = th.cellIndex;
        state.dir = state.sortIdx === idx ? -state.dir : 1;
        state.sortIdx = idx;
        $$("th[data-sort]", table).forEach((h) =>
          h.setAttribute("aria-sort", "none"),
        );
        th.setAttribute(
          "aria-sort",
          state.dir === 1 ? "ascending" : "descending",
        );
        render();
      };
      th.addEventListener("click", sort);
      th.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          sort();
        }
      });
    });
    render();
    table.v7Refresh = render;
  }
  $$("table.data-table").forEach(initTable);

  const detail = $("#detail-drawer");
  const overlay = $("#overlay");
  let detailReturn = null;
  const labelize = (k) =>
    k.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
  function openDetail(data) {
    if (!detail) return;
    detailReturn = document.activeElement;
    $("#detail-title").textContent = data.title || "Details";
    const rows = Object.entries(data.fields || {})
      .filter(([, v]) => v !== null && typeof v !== "object")
      .map(
        ([k, v]) =>
          `<div><dt>${escapeHtml(labelize(k))}</dt><dd>${escapeHtml(v)}</dd></div>`,
      )
      .join("");
    $(".detail-body", detail).innerHTML =
      `<dl class="kv kv-stack">${rows}</dl>`;
    detail.hidden = false;
    overlay.hidden = false;
    $("[data-detail-close]", detail).focus();
  }
  function closeDetail() {
    if (!detail || detail.hidden) return;
    detail.hidden = true;
    overlay.hidden = true;
    detailReturn && detailReturn.focus();
  }
  document.addEventListener("click", (e) => {
    const b = e.target.closest("[data-detail]");
    if (b) {
      try {
        openDetail(JSON.parse(b.dataset.detail));
      } catch {
        toast("Could not open details.", "error");
      }
    }
    if (e.target.closest("[data-detail-close]") || e.target === overlay)
      closeDetail();
  });

  $$("[data-tabs]").forEach((tabs) => {
    const btns = $$('[role="tab"]', tabs);
    btns.forEach((b) =>
      b.addEventListener("click", () => {
        btns.forEach((x) => {
          x.setAttribute("aria-selected", "false");
          const p = document.getElementById(x.getAttribute("aria-controls"));
          p && p.classList.remove("active");
        });
        b.setAttribute("aria-selected", "true");
        const p = document.getElementById(b.getAttribute("aria-controls"));
        p && p.classList.add("active");
      }),
    );
  });

  function validateForm(form) {
    let valid = true;
    $$(".field", form).forEach((f) => f.classList.remove("invalid"));
    $$("input, select, textarea", form).forEach((input) => {
      const field = input.closest(".field");
      if (!field || input.disabled) return;
      let ok = input.checkValidity();
      if (ok && input.dataset.pattern)
        ok =
          input.value === "" ||
          new RegExp(input.dataset.pattern).test(input.value);
      if (!ok) {
        field.classList.add("invalid");
        valid = false;
      }
    });
    $$("[data-require-one]", form).forEach((g) => {
      const field = g.closest(".field");
      if (!$$("input:checked", g).length) {
        field.classList.add("invalid");
        valid = false;
      }
    });
    if (!valid) {
      const first = $(
        ".field.invalid input, .field.invalid select, .field.invalid textarea",
        form,
      );
      first && first.focus();
    }
    return valid;
  }
  $$("form[data-validate]").forEach((form) => {
    form.setAttribute("novalidate", "");
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (!validateForm(form)) {
        toast("Please fix the highlighted fields.", "error");
        return;
      }
      if (
        form.dataset.confirm &&
        !(await confirmDialog(form.dataset.confirm, {
          title: form.dataset.confirmTitle || "Please confirm",
          ok: form.dataset.confirmOk || "Confirm",
        }))
      )
        return;
      form.dispatchEvent(new CustomEvent("v7:valid", { bubbles: true }));
      if (!form.hasAttribute("data-custom-submit"))
        toast(
          form.dataset.success || "Saved.",
          "success",
        );
      const modal = form.closest(".modal");
      if (modal && !form.hasAttribute("data-custom-submit"))
        modal.hidden = true;
    });
  });

  const sNav = $(".settings-nav");
  if (sNav && "IntersectionObserver" in window) {
    const links = $$("a", sNav);
    const io = new IntersectionObserver(
      (ents) =>
        ents.forEach((en) => {
          if (en.isIntersecting)
            links.forEach((l) =>
              l.classList.toggle(
                "active",
                l.getAttribute("href") === `#${en.target.id}`,
              ),
            );
        }),
      { rootMargin: "-30% 0px -60% 0px" },
    );
    $$(".settings-sections > section[id]").forEach((s) => io.observe(s));
  }

  $$("time[data-utc]").forEach((t) => {
    const d = new Date(t.getAttribute("datetime"));
    if (!isNaN(d)) {
      t.title = t.textContent;
      t.textContent = d.toLocaleString(undefined, {
        dateStyle: "medium",
        timeStyle: "short",
      });
    }
  });

  window.V7 = {
    toast,
    confirmDialog,
    openModal,
    openDetail,
    store,
    escapeHtml,
    $,
    $$,
  };
})();
