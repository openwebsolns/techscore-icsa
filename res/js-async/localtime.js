(function (w,d) {
    w.addEventListener('load', function(e) {
        e = d.getElementById("last_updated");
        if (!e)
            return;
        var c = e.textContent.split("@");
        var t = new Date(c[0] + " " + c[1]);
        var m = t.getMinutes();
        if (m < 10)
            m = "0" + m;
        var s = t.getSeconds();
        if (s < 10)
            s = "0" + s;
        var n = (["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"])[t.getMonth()] + " " + t.getDate() + ", " + t.getFullYear() + " @ " + t.getHours() + ":" + m + ":" + s;
        while (e.childNodes.length > 0)
            e.removeChild(e.childNodes[0]);
        e.appendChild(d.createTextNode(n));
    }, false);
})(window,document);
