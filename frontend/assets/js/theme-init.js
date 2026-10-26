try {
  var t = localStorage.getItem("v7-theme");
  if (t === "light" || t === "dark") document.documentElement.dataset.theme = t;
  if (localStorage.getItem("v7-sidebar") === "collapsed") document.documentElement.classList.add("sb-collapsed");
} catch (e) {}
