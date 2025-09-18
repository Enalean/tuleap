/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/* global ProtoCheck:readonly Prototype:readonly Ajax:readonly */

var codendi = codendi || {};

codendi.imgroot = codendi.imgroot || "/themes/common/images/";

codendi.locales = codendi.locales || {};

codendi.getText = function (key1, key2) {
    return codendi.locales[key1][key2];
};

document.observe("dom:loaded", function () {
    //load protocheck, if needed
    new ProtoCheck();
});

/**
 * Ajax.Request.abort
 * extend the prototype.js Ajax.Request object so that it supports an abort method
 *
 * @see http://blog.pothoven.net/2007/12/aborting-ajax-requests-for-prototypejs.html
 */
Ajax.Request.prototype.abort = function () {
    // prevent and state change callbacks from being issued
    this.transport.onreadystatechange = Prototype.emptyFunction;
    // abort the XHR
    this.transport.abort();
    // update the request counter
    Ajax.activeRequestCount--;
};
