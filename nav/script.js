console.log("hello world");

// Mobile Navigation Toggle
const btnNavEL = document.querySelector(".btn-mobile-nav");
const headerEl = document.querySelector("header");

btnNavEL.addEventListener("click", function () {
  headerEl.classList.toggle("nav-open");
  console.log("Mobile navigation toggled.");
});

// Dropdown Toggle Functionality
const dropdownTriggers = document.querySelectorAll(".dropdown > a");

dropdownTriggers.forEach((trigger) => {
  trigger.addEventListener("click", function (e) {
    e.preventDefault(); // Prevent the default link behavior
    const dropdown = this.closest(".dropdown");
    dropdown.classList.toggle("dropdown-active");
    console.log("Dropdown toggled for:", dropdown);
  });
});

// Smooth Scrolling
const allLinks = document.querySelectorAll("a:link");

allLinks.forEach(function (link) {
  link.addEventListener("click", function (e) {
    e.preventDefault();
    const href = link.getAttribute("href");

    // Scroll back to top
    if (href === "#") {
      window.scrollTo({
        top: 0,
        behavior: "smooth",
      });
      console.log("Scrolled to top.");
    }

    // Scroll to other links
    if (href !== "#" && href.startsWith("#")) {
      const sectionEl = document.querySelector(href);
      sectionEl.scrollIntoView({ behavior: "smooth" });
      console.log("Scrolled to section:", href);
    }

    // Close mobile navigation after clicking a link
    if (link.classList.contains("main-nav-link")) {
      headerEl.classList.remove("nav-open");
      console.log("Mobile navigation closed after clicking a link.");
    }
  });
});

//////////////////////////////////////////////
// Fixing flexbox gap property missing in some Safari versions
function checkFlexGap() {
  const flex = document.createElement("div");
  flex.style.display = "flex";
  flex.style.flexDirection = "column";
  flex.style.rowGap = "1px";

  flex.appendChild(document.createElement("div"));
  flex.appendChild(document.createElement("div"));

  document.body.appendChild(flex);
  const isSupported = flex.scrollHeight === 1;
  flex.parentNode.removeChild(flex);

  if (!isSupported) {
    document.body.classList.add("no-flexbox-gap");
    console.log("Flexbox gap is not supported. Fallback applied.");
  } else {
    console.log("Flexbox gap is supported.");
  }
}
checkFlexGap();

// Utility Logs
console.log("JavaScript is linked correctly.");
