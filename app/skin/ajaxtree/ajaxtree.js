/*
 * ajaxtree.js - Expand and collapse a tree menu using Ajax
 *
 * Web page:
 *   http://www.revulo.com/PukiWiki/Plugin/AjaxTree.html
 *
 * Copyright (c) 2007-2009 revulo
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */

(function() {

var baseUrl = "html/ajaxtree/";

function toggle(event) {
  var element = event ? event.target : window.event.srcElement;

  if (element.nodeName == "LI") {
    var li = element;
    var ul = li.getElementsByTagName("ul")[0];

    if (li.className == "expanded") {
      li.className = "collapsed";
      ul.style.display = "none";
    } else if (li.className == "collapsed") {
      if (ul) {
        li.className = "expanded";
        ul.style.display = "block";
      } else {
        ajax(li);
      }
    }
  }
}

function cancel() {
  return false;
}

function ajax(li) {
  var req = window.ActiveXObject ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();

  if (req) {
    var a    = li.getElementsByTagName("a")[0];
    var name = encode(a.title);
    var url  = baseUrl + name + ".html";

    req.onreadystatechange = function() {
      if (req.readyState == 4 && req.status == 200) {
        var ul = document.createElement("ul");
        ul.innerHTML = req.responseText;
        li.className = "expanded";
        li.appendChild(ul);
        req = null;
      }
    };
    req.open("GET", url, true);
    req.setRequestHeader("If-Modified-Since", "Thu, 01 Jan 1970 00:00:00 GMT");
    req.send("");
  }
}

function encode(str) {
  var tmp = "";
  for (var i = 0, n = str.length; i < n; ++i) {
    var c = str.charAt(i);
    if (c <= "\x7f") {
      tmp += c.charCodeAt(0).toString(16).toUpperCase();
    } else {
      tmp += encodeURIComponent(c).replace(/%/g, "");
    }
  }
  return tmp;
}

var tree   = document.getElementById("ajaxtree");
var isMSIE = /*@cc_on!@*/false;

tree.onclick     = toggle;
tree.onmousedown = cancel;
if (isMSIE) {
  tree.ondblclick    = toggle;
  tree.onselectstart = cancel;
}
tree = null;

})();
