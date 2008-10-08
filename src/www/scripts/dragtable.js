/*
  dragtable v1.0
  June 26, 2008
  Dan Vanderkam, http://danvk.org/dragtable/
                 http://code.google.com/p/dragtable/

  Instructions:
    - Download this file
    - Add <script src="dragtable.js"></script> to your HTML.
    - Add class="draggable" to any table you might like to reorder.
    - Drag the headers around to reorder them.

  This is code was based on:
    - Stuart Langridge's SortTable (kryogenix.org/code/browser/sorttable)
    - Mike Hall's draggable class (http://www.brainjar.com/dhtml/drag/)
    - A discussion of permuting table columns on comp.lang.javascript

  Licensed under the MIT license.
 */

// Here's the notice from Mike Hall's draggable script:
//*****************************************************************************
// Do not remove this notice.
//
// Copyright 2001 by Mike Hall.
// See http://www.brainjar.com for terms of use.
//*****************************************************************************
dragtable = {
  // How far should the mouse move before it's considered a drag, not a click?
  dragRadius2: 100,
  setMinDragDistance: function(x) {
    dragtable.dragRadius2 = x * x;
  },

  // Determine browser and version.
  // TODO: eliminate browser sniffing except where it's really necessary.
  Browser: function() {
    var ua, s, i;

    this.isIE    = false;
    this.isNS    = false;
    this.version = null;
    ua = navigator.userAgent;

    s = "MSIE";
    if ((i = ua.indexOf(s)) >= 0) {
      this.isIE = true;
      this.version = parseFloat(ua.substr(i + s.length));
      return;
    }

    s = "Netscape6/";
    if ((i = ua.indexOf(s)) >= 0) {
      this.isNS = true;
      this.version = parseFloat(ua.substr(i + s.length));
      return;
    }

    // Treat any other "Gecko" browser as NS 6.1.
    s = "Gecko";
    if ((i = ua.indexOf(s)) >= 0) {
      this.isNS = true;
      this.version = 6.1;
      return;
    }
  },
  browser: null,

  // Detect all draggable tables and attach handlers to their headers.
  init: function() {
    // Don't initialize twice
    if (arguments.callee.done) return;
    arguments.callee.done = true;
    if (_dgtimer) clearInterval(_dgtimer);
    if (!document.createElement || !document.getElementsByTagName) return;

    dragtable.dragObj.zIndex = 0;
    dragtable.browser = new dragtable.Browser();
    forEach(document.getElementsByTagName('table'), function(table) {
      if (table.className.search(/\bdraggable\b/) != -1) {
        dragtable.makeDraggable(table);
      }
    });
  },

  // The thead business is taken straight from sorttable.
  makeDraggable: function(table) {
    if (table.getElementsByTagName('thead').length == 0) {
      the = document.createElement('thead');
      the.appendChild(table.rows[0]);
      table.insertBefore(the,table.firstChild);
    }

    // Safari doesn't support table.tHead, sigh
    if (table.tHead == null) {
      table.tHead = table.getElementsByTagName('thead')[0];
    }

    var headers = table.tHead.rows[0].cells;
    for (var i = 0; i < headers.length; i++) {
      headers[i].onmousedown = dragtable.dragStart;
    }
  },

  // Global object to hold drag information.
  dragObj: new Object(),

  // Climb up the DOM until there's a tag that matches.
  findUp: function(elt, tag) {
    do {
      if (elt.nodeName && elt.nodeName.search(tag) != -1)
        return elt;
    } while (elt = elt.parentNode);
    return null;
  },

  // clone an element, copying its style and class.
  fullCopy: function(elt, deep) {
    var new_elt = elt.cloneNode(deep);
    new_elt.className = elt.className;
    forEach(elt.style, function(k) { new_elt.style[k] = elt.style[k]; });
    return new_elt;
  },

  eventPosition: function(event) {
    var x, y;
    if (dragtable.browser.isIE) {
      x = window.event.clientX + document.documentElement.scrollLeft
        + document.body.scrollLeft;
      y = window.event.clientY + document.documentElement.scrollTop
        + document.body.scrollTop;
      return {x: x, y: y};
    }
    return {x: event.pageX, y: event.pageY};
  },

 // Determine the position of this element on the page. Many thanks to Magnus
 // Kristiansen for help making this work with "position: fixed" elements.
 absolutePosition: function(elt) {
   var ex = 0, ey = 0;
   do {
     var curStyle = dragtable.browser.isIE ? elt.currentStyle
                                           : window.getComputedStyle(elt, '');
     var supportFixed = !(dragtable.browser.isIE &&
                          dragtable.browser.version < 7);
     if (supportFixed && curStyle.position == 'fixed') {
       // Get the fixed el's offset
       ex += parseInt(curStyle.left, 10);
       ey += parseInt(curStyle.top, 10);
       // Compensate for scrolling
       ex += document.body.scrollLeft;
       ey += document.body.scrollTop;
       // End the loop
       break;
     } else {
       ex += elt.offsetLeft;
       ey += elt.offsetTop;
     }
   } while (elt = elt.offsetParent);
   return {x: ex, y: ey};
 },

  // MouseDown handler -- sets up the appropriate mousemove/mouseup handlers
  // and fills in the global dragtable.dragObj object.
  dragStart: function(event, id) {
    var el;
    var x, y;
    var dragObj = dragtable.dragObj;

    var browser = dragtable.browser;
    if (browser.isIE)
      dragObj.origNode = window.event.srcElement;
    else
      dragObj.origNode = event.target;
    var pos = dragtable.eventPosition(event);

    // Drag the entire table cell, not just the element that was clicked.
    dragObj.origNode = dragtable.findUp(dragObj.origNode, /T[DH]/);

    // Since a column header can't be dragged directly, duplicate its contents
    // in a div and drag that instead.
    // TODO: I can assume a tHead...
    var table = dragtable.findUp(dragObj.origNode, "TABLE");
    dragObj.table = table;
    dragObj.startCol = dragtable.findColumn(table, pos.x);
    if (dragObj.startCol == -1) return;

    var new_elt = dragtable.fullCopy(table, false);
    new_elt.style.margin = '0';

    // Copy the entire column
    var copySectionColumn = function(sec, col) {
      var new_sec = dragtable.fullCopy(sec, false);
      forEach(sec.rows, function(row) {
        var cell = row.cells[col];
        var new_tr = dragtable.fullCopy(row, false);
        if (row.offsetHeight) new_tr.style.height = row.offsetHeight + "px";
        var new_td = dragtable.fullCopy(cell, true);
        if (cell.offsetWidth) new_td.style.width = cell.offsetWidth + "px";
        new_tr.appendChild(new_td);
        new_sec.appendChild(new_tr);
      });
      return new_sec;
    };

    // First the heading
    if (table.tHead) {
      new_elt.appendChild(copySectionColumn(table.tHead, dragObj.startCol));
    }
    forEach(table.tBodies, function(tb) {
      new_elt.appendChild(copySectionColumn(tb, dragObj.startCol));
    });
    if (table.tFoot) {
      new_elt.appendChild(copySectionColumn(table.tFoot, dragObj.startCol));
    }

    var obj_pos = dragtable.absolutePosition(dragObj.origNode);
    new_elt.style.position = "absolute";
    new_elt.style.left = obj_pos.x + "px";
    new_elt.style.top = obj_pos.y + "px";
    new_elt.style.width = dragObj.origNode.offsetWidth;
    new_elt.style.height = dragObj.origNode.offsetHeight;

    // Hold off adding the element until this is clearly a drag.
    dragObj.addedNode = false;
    dragObj.tableContainer = dragObj.table.parentNode || document.body;
    dragObj.elNode = new_elt;

    // Save starting positions of cursor and element.
    dragObj.cursorStartX = pos.x;
    dragObj.cursorStartY = pos.y;
    dragObj.elStartLeft  = parseInt(dragObj.elNode.style.left, 10);
    dragObj.elStartTop   = parseInt(dragObj.elNode.style.top,  10);

    if (isNaN(dragObj.elStartLeft)) dragObj.elStartLeft = 0;
    if (isNaN(dragObj.elStartTop))  dragObj.elStartTop  = 0;

    // Update element's z-index.
    dragObj.elNode.style.zIndex = ++dragObj.zIndex;

    // Capture mousemove and mouseup events on the page.
    if (browser.isIE) {
      document.attachEvent("onmousemove", dragtable.dragMove);
      document.attachEvent("onmouseup",   dragtable.dragEnd);
      window.event.cancelBubble = true;
      window.event.returnValue = false;
    } else {
      document.addEventListener("mousemove", dragtable.dragMove, true);
      document.addEventListener("mouseup",   dragtable.dragEnd, true);
      event.preventDefault();
    }
  },

  // Move the floating column header with the mouse
  // TODO: Reorder columns as the mouse moves for a more interactive feel.
  dragMove: function(event) {
    var x, y;
    var dragObj = dragtable.dragObj;

    // Get cursor position with respect to the page.
    var pos = dragtable.eventPosition(event);

    var dx = dragObj.cursorStartX - pos.x;
    var dy = dragObj.cursorStartY - pos.y;
    if (!dragObj.addedNode && dx * dx + dy * dy > dragtable.dragRadius2) {
      dragObj.tableContainer.insertBefore(dragObj.elNode, dragObj.table);
      dragObj.addedNode = true;
    }

    // Move drag element by the same amount the cursor has moved.
    var style = dragObj.elNode.style;
    style.left = (dragObj.elStartLeft + pos.x - dragObj.cursorStartX) + "px";
    style.top  = (dragObj.elStartTop  + pos.y - dragObj.cursorStartY) + "px";

    if (dragtable.browser.isIE) {
      window.event.cancelBubble = true;
      window.event.returnValue = false;
    } else {
      event.preventDefault();
    }
  },

  // Stop capturing mousemove and mouseup events.
  // Determine which (if any) column we're over and shuffle the table.
  dragEnd: function(event) {
    if (dragtable.browser.isIE) {
      document.detachEvent("onmousemove", dragtable.dragMove);
      document.detachEvent("onmouseup", dragtable.dragEnd);
    } else {
      document.removeEventListener("mousemove", dragtable.dragMove, true);
      document.removeEventListener("mouseup", dragtable.dragEnd, true);
    }

    // If the floating header wasn't added, the mouse didn't move far enough.
    var dragObj = dragtable.dragObj;
    if (!dragObj.addedNode) {
      return;
    }
    dragObj.tableContainer.removeChild(dragObj.elNode);

    // Determine whether the drag ended over the table, and over which column.
    var pos = dragtable.eventPosition(event);
    var table_pos = dragtable.absolutePosition(dragObj.table);
    if (pos.y < table_pos.y ||
        pos.y > table_pos.y + dragObj.table.offsetHeight) {
      return;
    }
    var targetCol = dragtable.findColumn(dragObj.table, pos.x);
    if (targetCol != -1 && targetCol != dragObj.startCol) {
      dragtable.moveColumn(dragObj.table, dragObj.startCol, targetCol);
    }
  },

  // Which column does the x value fall inside of? x should include scrollLeft.
  findColumn: function(table, x) {
    var header = table.tHead.rows[0].cells;
    for (var i = 0; i < header.length; i++) {
      //var left = header[i].offsetLeft;
      var pos = dragtable.absolutePosition(header[i]);
      //if (left <= x && x <= left + header[i].offsetWidth) {
      if (pos.x <= x && x <= pos.x + header[i].offsetWidth) {
        return i;
      }
    }
    return -1;
  },

  // Move a column of table from start index to finish index.
  // Based on the "Swapping table columns" discussion on comp.lang.javascript.
  // Assumes there are columns at sIdx and fIdx
  moveColumn: function(table, sIdx, fIdx) {
    var row, cA;
    var i=table.rows.length;
    while (i--){
      row = table.rows[i]
      var x = row.removeChild(row.cells[sIdx]);
      if (fIdx < row.cells.length) {
        row.insertBefore(x, row.cells[fIdx]);
      } else {
        row.appendChild(x);
      }
    }

    // For whatever reason, sorttable tracks column indices this way.
    // Without a manual update, clicking one column will sort on another.
    var headrow = table.tHead.rows[0].cells;
    for (var i=0; i<headrow.length; i++) {
      headrow[i].sorttable_columnindex = i;
    }
  }
}

/* ******************************************************************
   Supporting functions: bundled here to avoid depending on a library
   ****************************************************************** */

// Dean Edwards/Matthias Miller/John Resig
// has a hook for dragtable.init already been added? (see below)
var dgListenOnLoad = false;

/* for Mozilla/Opera9 */
if (document.addEventListener) {
  dgListenOnLoad = true;
  document.addEventListener("DOMContentLoaded", dragtable.init, false);
}

/* for Internet Explorer */
/*@cc_on @*/
/*@if (@_win32)
  dgListenOnLoad = true;
  document.write("<script id=__dt_onload defer src=javascript:void(0)><\/script>");
  var script = document.getElementById("__dt_onload");
  script.onreadystatechange = function() {
    if (this.readyState == "complete") {
      dragtable.init(); // call the onload handler
    }
  };
/*@end @*/

/* for Safari */
if (/WebKit/i.test(navigator.userAgent)) { // sniff
  dgListenOnLoad = true;
  var _dgtimer = setInterval(function() {
    if (/loaded|complete/.test(document.readyState)) {
      dragtable.init(); // call the onload handler
    }
  }, 10);
}

/* for other browsers */
/* Avoid this unless it's absolutely necessary (it breaks sorttable) */
if (!dgListenOnLoad) {
  window.onload = dragtable.init;
}

// Dean's forEach: http://dean.edwards.name/base/forEach.js
/*
  forEach, version 1.0
  Copyright 2006, Dean Edwards
  License: http://www.opensource.org/licenses/mit-license.php
*/

// array-like enumeration
if (!Array.forEach) { // mozilla already supports this
  Array.forEach = function(array, block, context) {
    for (var i = 0; i < array.length; i++) {
      block.call(context, array[i], i, array);
    }
  };
}

// generic enumeration
Function.prototype.forEach = function(object, block, context) {
  for (var key in object) {
    if (typeof this.prototype[key] == "undefined") {
      block.call(context, object[key], key, object);
    }
  }
};

// character enumeration
String.forEach = function(string, block, context) {
  Array.forEach(string.split(""), function(chr, index) {
    block.call(context, chr, index, string);
  });
};

// globally resolve forEach enumeration
var forEach = function(object, block, context) {
  if (object) {
    var resolve = Object; // default
    if (object instanceof Function) {
      // functions have a "length" property
      resolve = Function;
    } else if (object.forEach instanceof Function) {
      // the object implements a custom forEach method so use that
      object.forEach(block, context);
      return;
    } else if (typeof object == "string") {
      // the object is a string
      resolve = String;
    } else if (typeof object.length == "number") {
      // the object is array-like
      resolve = Array;
    }
    resolve.forEach(object, block, context);
  }
};

