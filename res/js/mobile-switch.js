(function(document) {
    if (window.screen.width < 800) {
        var enableMobile = function() {
            m = document.createElement("meta");
            m.setAttribute("name", "viewport");
            m.setAttribute("content", "width=device-width,initial-scale=1,maximum-scale=1");
            m.id = "ms-meta";
            s = document.getElementsByTagName("script")[0];
            s.parentNode.insertBefore(m, s);
        };
        var disableMobile = function() {
            var m = document.getElementById("ms-meta");
            m.parentNode.removeChild(m);
        };
        var actOn = function(enable) {
            if (enable) {
                enableMobile();
            }
            else {
                disableMobile();
            }
            document.cookie = "mobile=" + enable + ";path=/;max-age=3153600";
        };

        var c = document.cookie, m = null, s = null;
        var turnedOn = false;
        if (/mobile=true/.test(c)) {
            turnedOn = true;
        }
        else if (!/mobile=false/.test(c)) {
            m = document.createElement("script");
            m.src = "/inc/js/mobile-prompt.js";
            m.async = true;
            m.defer = true;
            s = document.getElementsByTagName("script")[0];
            s.parentNode.insertBefore(m, s);
        }

        // Add toggle button
        var tog = document.getElementById("ms-toggle");
        if (tog == null) {
            tog = document.createElement("div");
            tog.id = "ms-toggle";
            document.getElementById("page-footer").appendChild(tog);
        }

        var div = document.createElement("div");
        div.id = "ms-div";
        tog.appendChild(div);

        var inp = document.createElement("input");
        inp.id = "ms-button";
        inp.type = "checkbox";
        div.appendChild(inp);

        var lab = document.createElement("label");
        lab.setAttribute("for", "ms-button");
        div.appendChild(lab);

        var spn = document.createElement("span");
        spn.id = "ms-button-label";
        tog.appendChild(spn);
        spn.appendChild(document.createTextNode("Mobile"));

        inp.addEventListener(
            "change",
            function(e) {
                actOn(inp.checked);
            },
            false
        );

        inp.checked = turnedOn;
        actOn(turnedOn);
    }
})(document);
