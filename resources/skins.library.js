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
        a.classList.add('d-lg-block');

        toc.classList.add('card', 'sticky-top');
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
        var spyElem = document.getElementsByTagName("body")[0]
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
        if (v >= 1400) {
            b.classList.add("sideshow");
            document
                .querySelector(".sidebar-container")
                .classList.remove("drawer");
            document
                .querySelector(".minibar-container")
                .classList.add("drawer");
        } else if (v < 1400 && v >= 992) {
            b.classList.remove("sideshow");
            b.classList.add("minishow");
            document
                .querySelector(".sidebar-container")
                .classList.add("drawer");
            document
                .querySelector(".minibar-container")
                .classList.remove("drawer");
        } else {
            b.classList.remove("minishow");
            document
                .querySelector(".sidebar-container")
                .classList.add("drawer");
            document
                .querySelector(".minibar-container")
                .classList.add("drawer");
        }
    };
    // initial document by current viewport
    setViewport();
    // on resize events
    window.addEventListener("resize", function () {
        setViewport();
    });

    // update classList when clicking toggler button
    // by Vanilla JS
    document.addEventListener("DOMContentLoaded", function () {
        // triggered when window scrollUp & Down
        var pos = 0;
        var c = document.getElementById("commander").offsetTop;
        window.addEventListener(
            "scroll",
            debounce(function () {
                // var y = window.scrollY;
                if (window.scrollY < pos) {
                    document.getElementById("masthead").classList.add("show");
                } else {
                    document
                        .getElementById("masthead")
                        .classList.remove("show");
                }
                pos = window.scrollY;
                if (window.scrollY > c) {
                    document
                        .getElementById("commander")
                        .classList.add("sticky-top");
                } else {
                    document
                        .getElementById("commander")
                        .classList.remove("sticky-top");
                }
            }, 10)
        );

        // open searchForm when clicking
        document
            .getElementById("search")
            .addEventListener("click", function () {
                document.getElementById("masthead").classList.add("searchshow");
            });

        // close searchForm when clicking
        document
            .getElementById("searchclose")
            .addEventListener("click", function () {
                document
                    .getElementById("masthead")
                    .classList.remove("searchshow");
            });

        // open / close sidebar when clicking
        var t = document.getElementsByClassName("sidebar-toggler");
        var b = document.getElementsByTagName("body")[0];
        var v = window.innerWidth || document.documentElement.clientWidth;

        for (var i = 0; i < t.length; i++) {
            t[i].addEventListener("click", function () {
                if (b.classList.contains("sideshow")) {
                    b.classList.remove("sideshow");
                } else {
                    b.classList.add("sideshow");
                }
            });

            /* 					
								
                s.classList.contains('d-none')) {
                s.classList.remove('d-none', 'animate__bounceInLeft');
                s.classList.add('animate__bounceOutRight');
                if (v > 768) {
                    document.querySelector(".minibar").classList.remove('d-none');
                }
            } else {
                s.classList.remove('animate__bounceOutRight');
                s.classList.add('d-none', 'animate__bounceInLeft');
                if (v > 768) {
                    document.querySelector(".minibar").classList.add('d-none');
                }
            }
        }); */
        }
    });
})();
