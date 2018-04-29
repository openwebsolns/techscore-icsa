/*
 * Narrow down team's display based on URL
 */

window.addEventListener('load', function(e) {

    var TEAM_ROW_MAP = {};
    var TEAM_PORT_MAP = {}; // map of ports to "hide"
    var IS_SHOWING = false;

    function extractTeamID(row) {
        for (var i = 0; i < row.classList.length; i++) {
            if (/team-[0-9]+/.test(row.classList.item(i)))
                return row.classList.item(i);
        }
        return null;
    }

    function filterTeamName(name) {
        var row;
        for (var i = 0; i < TEAM_ROW_MAP[name].length; i++) {
            row = TEAM_ROW_MAP[name][i];
            var j = 0;
            if (row.parentNode.parentNode.classList.contains("teamscores"))
                j = 1;
            for (; j < row.parentNode.childNodes.length; j++) {
                if (row.parentNode.childNodes[j] != row)
                    row.parentNode.childNodes[j].style.display = "none";
            }
        }
        for (var i = 0; i < TEAM_PORT_MAP[name].length; i++) {
            TEAM_PORT_MAP[name][i].style.display = "none";
        }
        IS_SHOWING = true;
    }

    function showAll() {
        for (var name in TEAM_ROW_MAP) {
            var row;
            for (var i = 0; i < TEAM_ROW_MAP[name].length; i++) {
                row = TEAM_ROW_MAP[name][i];
                for (var j = 0; j < row.parentNode.childNodes.length; j++) {
                    row.parentNode.childNodes[j].style.display = "table-row";
                }
            }

            for (var i = 0; i < TEAM_PORT_MAP[name].length; i++) {
                TEAM_PORT_MAP[name][i].style.display = "table";
            }
        }
        window.location.hash = "#_";
        IS_SHOWING = false;
    }

    function initFullSelect() {
        var linkRoot = "";
        var menu = document.getElementById("menu");
        if (menu) {
            var hrefs = menu.getElementsByTagName("a");
            for (var i = 0; i < hrefs.length; i++) {
                if (hrefs[i].getAttribute("itemprop") == "url") {
                    linkRoot = hrefs[i].getAttribute("href") + "full-scores/";
                    break;
                }
            }
        }

        var tables = document.getElementsByTagName("table");
        for (var t = 0; t < tables.length; t++) {
            var r, n, c, href;
            var table = tables[t];

            var funcFactory = function(n) {
                return function() {
                    if (!IS_SHOWING) {
                        filterTeamName(n);
                        return true;
                    }
                    showAll();
                    return false;
                };
            };

            if (table.classList.contains("teamranking")) {
                // Add message before
                var p = table.previousSibling;
                if (!p || p.nodeName.toLowerCase() != "p") {
                    p = document.createElement("p");
                    table.parentNode.insertBefore(p, table);
                }
                var st = document.createElement("em");
                p.appendChild(st);
                st.appendChild(document.createTextNode(" Tip: click on team's record to toggle filter."));

                var rows = table.childNodes[1].childNodes;
                for (r = 0; r < rows.length; r++) {

                    n = extractTeamID(rows[r]);
                    if (n) {
                        TEAM_ROW_MAP[n] = [rows[r]];
                        TEAM_PORT_MAP[n] = [];

                        c = rows[r].childNodes[5];
                        href = document.createElement("a");
                        href.setAttribute("href", linkRoot + "#" + n);
                        href.setAttribute("title", "Click to filter list");
                        href.onclick = funcFactory(n);
                        while (c.childNodes.length > 0)
                            href.appendChild(c.childNodes[0]);
                        c.appendChild(href);
                    }
                }

            }
            else if (table.classList.contains("teamscores")) {
                var represented = {};

                for (r = 1; r < table.childNodes[0].childNodes.length; r++) {
                    var row = table.childNodes[0].childNodes[r];
                    n = extractTeamID(row);
                    if (n in TEAM_ROW_MAP) {
                        TEAM_ROW_MAP[n].push(row);
                        represented[n] = 1;

                        c = row.childNodes[1];
                        href = document.createElement("a");
                        href.setAttribute("href", linkRoot + "#" + n);
                        href.setAttribute("title", "Click to filter list");
                        href.onclick = funcFactory(n);
                        while (c.childNodes.length > 0)
                            href.appendChild(c.childNodes[0]);
                        c.appendChild(href);
                    }
                }

                for (n in TEAM_PORT_MAP) {
                    if (!(n in represented))
                        TEAM_PORT_MAP[n].push(table.parentNode);
                }
            }
        }

        // Parse based on location
        var hash = window.location.hash;
        if (hash.length > 1) {
            hash = decodeURIComponent(hash.substring(1)).replace(/\+/g, " ");
            if (hash in TEAM_ROW_MAP) {
                filterTeamName(hash);
            }
        }
    }


    initFullSelect();
}, false);
