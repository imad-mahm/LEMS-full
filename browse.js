function toggleDropdown() {
  const menu = document.getElementById("dropdown-menu");
  menu.style.display = menu.style.display === "block" ? "none" : "block";
}

window.onclick = function (e) {
  if (!e.target.matches(".profile-icon")) {
    const menu = document.getElementById("dropdown-menu");
    if (menu && menu.style.display === "block") {
      menu.style.display = "none";
    }
  }
};
