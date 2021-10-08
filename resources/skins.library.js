!(function () {
    // enable tooltips (Bootstrap 5)
    var tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // cloning #toc (table of content) from #mw-parser-output
    var toc = document.getElementById("toc");
    if (typeof toc != "undefined" && toc != null) {
        var a = document.getElementById("contentAside");
        a.classList.add("d-lg-block");

        toc.classList.add("card", "sticky-top");
        var u = document.querySelectorAll("#toc ul");
        for (var i = 0; i < u.length; i++) {
            u[i].classList.add("nav");
            u[0].classList.add("scrollspy");
        }
        var u = document.querySelectorAll("#toc ul a");
        for (var i = 0; i < u.length; i++) {
            u[i].classList.add("nav-link");
        }
        var u = document.querySelectorAll("#toc ul li");
        for (var i = 0; i < u.length; i++) {
            u[i].classList.add("nav-item");
        }
        a.appendChild(toc);

        // var spyElem = document.getElementById("mw-content-text");
        var spyElem = document.getElementsByTagName("body")[0];
        spyElem.setAttribute("data-bs-spy", "scroll");
        spyElem.setAttribute("data-bs-target", "ul.scrollspy");
        spyElem.setAttribute("data-bs-offset", "0");
        spyElem.setAttribute("tabindex", "0");
    }

    // returns a function, that, as long as it continues to be invoked, will not
    // be triggered. The function wil lbe called after it stops being called for
    // N milliseconds. If 'immediate' is passed, trigger the function on the
    // leading edge, instead of the trailing.
    function debounce(func, wait, immediate) {
        var timeout;
        return function () {
            var context = this,
                args = arguments;
            var later = function () {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }

    // update document by viewport width
    // Extra Large (>=1400) = sideshow
    // Medium (>=768px) = sideopen
    // by Vanilla
    var setViewport = function () {
        v = window.innerWidth || document.documentElement.clientWidth;
        b = document.getElementsByTagName("body")[0];
        s = document.querySelector(".sidebar-container");
        m = document.querySelector(".minibar-container");
        if (v >= 1400 && b.classList.contains("page-Main_Page")) {
            b.classList.add("sideshow");
            s.classList.remove("drawer");
           // m.classList.add("drawer");
        } else if (v < 1400 && v >= 992) {
            b.classList.remove("sideshow");
            b.classList.add("minishow");
            s.classList.add("drawer");
            // m.classList.remove("drawer");
        } else {
            b.classList.remove("sideshow");
            s.classList.add("drawer");
        }
    };
    // initial document by current viewport
    setViewport();
    // on resize events
    window.addEventListener("resize", function () {
        setViewport();
    });

    document.addEventListener("DOMContentLoaded", function () {
        var pos = 0;
        var m = document.getElementById("masthead");
        if (typeof m != "undefined" && m != null) {
            // show (hide) masthead when window scrollUp & Down
            window.addEventListener(
                "scroll",
                debounce(function () {
                    if (window.scrollY < pos) {
                        m.classList.add("show");
                    } else {
                        m.classList.remove("show");
                    }
                    pos = window.scrollY;
                }, 10)
            );
            // open (close) searchForm when clicking
            document
                .getElementById("search")
                .addEventListener("click", function () {
                    m.classList.add("searchshow");
                });
            document
                .getElementById("searchclose")
                .addEventListener("click", function () {
                    m.classList.remove("searchshow");
                });
        }

        // open / close sidebar when clicking
        var t = document.getElementsByClassName("sidebar-toggler");
        var v = window.innerWidth || document.documentElement.clientWidth;
        var b = document.getElementsByTagName("body")[0];

        for (var i = 0; i < t.length; i++) {
            t[i].addEventListener("click", function () {
                if (b.classList.contains("sideshow")) {
                    b.classList.add("minishow");
                    b.classList.remove("sideshow");
                } else {
                    b.classList.remove("minishow");
                    b.classList.add("sideshow");
                }
            });
        }
    });
})();
