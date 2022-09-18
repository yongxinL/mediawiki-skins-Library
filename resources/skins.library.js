!(function () {
    const b = document.getElementsByTagName("body")[0];
    const h = document.getElementsByTagName("mw-top-app-bar")[0];
    
    // show page loading until the page has finished
    // the load event is fired when the whole page has loaded, including
    // all dependent resources such as stylesheets and images.
    function fade(element) {
        var op = 1;  // initial opacity
        var timer = setInterval(function () {
            if (op <= 0.1){
                clearInterval(timer);
                element.style.display = 'none';
            }
            element.style.opacity = op;
            element.style.filter = 'alpha(opacity=' + op * 100 + ")";
            op -= op * 0.1;
        }, 30);
    }
    function unfade(element) {
        var op = 0.1;  // initial opacity
        element.style.display = 'flex';
        var timer = setInterval(function () {
            if (op >= 1){
                clearInterval(timer);
            }
            element.style.opacity = op;
            element.style.filter = 'alpha(opacity=' + op * 100 + ")";
            op += op * 0.1;
        }, 10);
    }
    document.addEventListener("DOMContentLoaded", function () {
        fade(document.getElementsByTagName("mw-page-preloader")[0]);
        unfade(document.getElementsByTagName("mw-root")[0]);
    })
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
    document.addEventListener("DOMContentLoaded", function () {
        var pos = 0;
        var t = document.getElementsByClassName("hamburger");
        if (typeof h != "undefined" && h != null) {
            // show (hide) masthead when window scrollUp & Down
            window.addEventListener(
                "scroll",
                debounce(function () {
                    if (window.scrollY < pos) {
                        h.classList.remove("sticky");
                    } else {
                        h.classList.add("sticky");
                    }
                    pos = window.scrollY;
                }, 10)
            );
        }

        // trigger navigation drawer
        for (var i = 0; i < t.length; i++) {
            t[i].addEventListener("click", function () {
                b.classList.toggle('drawer-open')
            });
        }
    });

    // Calculate the estimated reading time of an article
    function readingTime() {
        const text = document.getElementById("mw-content-text").innerText;
        const wpm = 225;
        const words = text.trim().split(/\s+/).length;
        const time = Math.ceil(words / wpm);
        const rtime = document.getElementById("rtime")
        if (typeof rtime != "undefined" && rtime != null) {
            rtime.innerText = time;
        }
    }
    readingTime();
    // enable tooltips (Bootstrap 5)
    var tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
})();
