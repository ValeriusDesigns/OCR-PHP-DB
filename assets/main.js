function copyToClipboard(elementId) {
   var element = document.getElementById(elementId);
   var range = document.createRange();
   range.selectNode(element);
   window.getSelection().addRange(range);
   document.execCommand("copy");
   window.getSelection().removeAllRanges();
   alert("URL wurde in die Zwischenablage kopiert.");
}

const navbarMenu = document.getElementById("menu");
const burgerMenu = document.getElementById("burger");

// Open Close Navbar Menu on Click Burger
if (burgerMenu && navbarMenu) {
   burgerMenu.addEventListener("click", () => {
      burgerMenu.classList.toggle("is-active");
      navbarMenu.classList.toggle("is-active");
   });
}

// Close Navbar Menu on Click Menu Links
document.querySelectorAll(".menu-link").forEach((link) => {
   link.addEventListener("click", () => {
      burgerMenu.classList.remove("is-active");
      navbarMenu.classList.remove("is-active");
   });
});

