console.log("hello world");

const btnNavEL = document.querySelector(".btn-mobile-nav");
const headerEl = document.querySelector("header");

btnNavEL.addEventListener("click", function () {
  headerEl.classList.toggle("nav-open");
});

console.log("JavaScript is linked correctly.");


function checkFlexGap() {
  var flex = document.createElement("div");
  flex.style.display = "flex";
  flex.style.flexDirection = "column";
  flex.style.rowGap = "1px";

  flex.appendChild(document.createElement("div"));
  flex.appendChild(document.createElement("div"));

  document.body.appendChild(flex);
  var isSupported = flex.scrollHeight === 1;
  flex.parentNode.removeChild(flex);
  console.log(isSupported);

  if (!isSupported) document.body.classList.add("no-flexbox-gap");
}
checkFlexGap();

