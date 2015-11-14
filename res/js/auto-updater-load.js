// Loads the AutoUpdater along with settings
(function(w, d) {
    var opts = {
        initialInterval : 30000,
        checkInterval :   30000,
        ignoreIds : ["social", "ms-screen", "ms-toggle"],
        ignoreTagNames : ["head"],
        debugLevel : 1
    };
    var load = function() {
        new AutoUpdater(opts);
    };

    var script = document.createElement("script");
    script.src = "/inc/js/AutoUpdater.js";
    script.async = true;
    script.defer = true;
    script.onreadystatechange = load;
    script.onload = load;

    var s = document.getElementsByTagName("script")[0];
    s.parentNode.insertBefore(script, s);

})(window, document);
