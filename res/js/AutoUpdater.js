/**
 * Show what's changed on a page, via classnames.
 *
 * @param Map options set of options to tweak the updater:
 *
 *   - initialInterval : millis after loading to first check
 *   - checkInterval   : millis in between subsequent checks
 *   - steps : number of ages to cycle through (1 to steps)
 *   - classname: name to use for changed elements.
 *                The age is attached as "-<age>".
 *   - ignoreIds: list of object IDs to never check/replace.
 *   - ignoreTagNames: list of lowercase tag names to ignore.
 *
 *   - debugLevel: report (to the console) for debugging.
 * 
 * @author Dayan Paez, OpenWeb Solutions, LLC
 * @version 2015-11-07
 */
function AutoUpdater(options) {
    this.options = {
        initialInterval : 15000,
        checkInterval : 30000,
        steps : 10,
        classname : "AutoUpdated",
        ignoreIds : [],
        ignoreTagNames : [],
        debugLevel : 0
    };
    for (var option in options) {
        if (option in this.options) {
            this.options[option] = options[option];
        }
    }
    this.classnameRegexp = new RegExp(
        "^" + this.options.classname + "-([0-9]+)$"
    );

    this.kickoffTimer(this.options.initialInterval);
}

AutoUpdater.prototype.kickoffTimer = function(delay) {
    if (!delay) {
        delay = this.options.checkInterval;
    }
    var myObj = this;
    this.timer = window.setTimeout(
        function() { myObj.runUpdate(); },
        delay
    );
};

AutoUpdater.prototype.runUpdate = function() {
    var myObj = this;
    var request = new XMLHttpRequest();
    request.responseType = "document";
    request.open("GET", window.location, true);
    request.onreadystatechange = function(e) {
        if (e.target.readyState == XMLHttpRequest.DONE) {
            if (e.target.status == 200) {
                myObj.diffPage(e.target.responseXML);
            }
            myObj.kickoffTimer();
        }
    };
    request.send();
};

AutoUpdater.prototype.diffPage = function(newPage) {
    this.diffSubtrees(document.firstElementChild, newPage.firstElementChild);
};

/**
 * Are the attributes the same for the given nodes?
 *
 * @param HTMLElement myNode the reference node
 * @param HTMLElement theirNode the second node to compare
 * @return boolean true IF all their attributes match.
 */
AutoUpdater.prototype.haveSameAttributes = function(myNode, theirNode) {
    var myAttrs = {};
    for (var i = 0; i < myNode.attributes.length; i++) {
        var myAttrName = myNode.attributes[i].name;
        if (myAttrName != "class") {
            myAttrs[myAttrName] = myNode.attributes[i].value;
        }
    }
    for (i = 0; i < theirNode.attributes.length; i++) {
        var attr = theirNode.attributes[i].name;
        if (attr != "class") {
            if (!(attr in myAttrs) || myAttrs[attr] != theirNode.attributes[i].value) {
                this.debug("Attribute " + attr + " is different for " + myNode.nodeName);
                return false;
            }
        }
    }

    // Compare classes
    var myClasses = this.getClassnames(myNode);
    var theirClasses = this.getClassnames(theirNode);
    if (myClasses != theirClasses) {
        this.debug("Nodes have different classnames.", 2);
        this.debug(myClasses, 3);
        this.debug(theirClasses, 3);
        return false;
    }

    this.debug("Nodes have similar attributes.", 2);
    return true;
};

AutoUpdater.prototype.getClassnames = function(node) {
    var classnames = [];
    for (var i = 0; i < node.classList.length; i++) {
        var classname = node.classList.item(i);
        if (classname.indexOf(this.options.classname) != 0) {
            classnames.push(classname);
        }
    }
    return classnames.sort().join(" ");
};

AutoUpdater.prototype.diffSubtrees = function(myNode, theirNode) {
    var myType = myNode.nodeType;
    if (myType != theirNode.nodeType) {
        this.debug("My nodeType (" + myType + ") differs for " + myNode.nodeName);
        this.replaceNode(myNode, theirNode);
        return;
    }

    // Text
    if (myType == Node.TEXT_NODE) {
        if (myNode.data != theirNode.data) {
            this.debug("Text nodes differ.");
            this.debug("Text nodes differ: [" + myNode.data + "] vs [" + theirNode.data + "].", 3);
            this.replaceNode(myNode, theirNode);
        }
        return;
    }

    // Ignore non-elements
    if (myType != Node.ELEMENT_NODE) {
        this.debug("Ignoring nodeType=" + myType);
        return;
    }

    // Ignore tagnames
    if (this.options.ignoreTagNames.indexOf(myNode.nodeName.toLowerCase()) >= 0) {
        this.debug("Ignoring TAG=" + myNode.nodeName);
        return;
    }

    // Ignore IDs
    if (this.options.ignoreIds.indexOf(myNode.id) >= 0) {
        this.debug("Ignoring ID=" + myNode.id);
        return;
    }

    this.debug("Comparing element " + myNode.nodeName, 2);
    // Compare attributes
    if (!this.haveSameAttributes(myNode, theirNode)) {
        this.replaceNode(myNode, theirNode);
        return;
    }

    // Recurse
    var myChildren = myNode.childNodes;
    var theirChildren = theirNode.childNodes;
    for (var i = 0, j = 0; i < myChildren.length && j < theirChildren.length; i++, j++) {
        this.diffSubtrees(myChildren[i], theirChildren[j]);
    }
    // Remove extra ones
    while (i < myChildren.length) {
        if (this.options.ignoreIds.indexOf(myChildren[i].id) >= 0) {
            this.debug("Ignoring ID=" + myChildren[i].id);
            i++;
        } else {
            this.debug("Removing extra child: " + myChildren[i], 1);
            myNode.removeChild(myChildren[i]);
        }
    }
    // Add extra ones
    while (j < theirChildren.length) {
        this.debug("Adding extra child: " + theirChildren[j], 1);
        myNode.addChild(document.adoptNode(theirChildren[j]));
    }
};

AutoUpdater.prototype.replaceNode = function(myNode, theirNode) {
    var age = this.getElementAge(this.getFirstParentElement(myNode));

    var localNode = document.adoptNode(theirNode);
    myNode.parentNode.replaceChild(localNode, myNode);

    this.increaseElementAge(this.getFirstParentElement(localNode), age);
};

AutoUpdater.prototype.getFirstParentElement = function(node) {
    var element = node;
    while (element.nodeType != Node.ELEMENT_NODE) {
        element = element.parentNode;
    }
    return element;
};

AutoUpdater.prototype.getElementAge = function(node) {
    var match = null;
    for (var i = 0; i < node.classList.length; i++) {
        var classname = node.classList.item(i);
        match = classname.match(this.classnameRegexp);
        if (match != null) {
            break;
        }
    }
    if (match == null) {
        return 0;
    }
    return Number(match[1]);
};

AutoUpdater.prototype.increaseElementAge = function(node, currentAge) {
    var nextAge = (currentAge - 1) % this.options.steps + 1;
    node.classList.remove(this.options.classname + "-" + currentAge);
    node.classList.add(this.options.classname);
    node.classList.add(this.options.classname + "-" + nextAge);
};

AutoUpdater.prototype.debug = function(message, priority) {
    if (!priority) {
        priority = 1;
    }
    if (priority > this.options.debugLevel) {
        return;
    }
    if (window.console) {
        window.console.log(message);
    }
};
