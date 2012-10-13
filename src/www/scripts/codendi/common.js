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

function help_window(helpurl) {
    var HelpWin = window.open(helpurl, 'HelpWindow', 'scrollbars=yes,resizable=yes,toolbar=no,height=740,width=1000');
    HelpWin.focus();
}

//http://mir.aculo.us/2009/1/7/using-input-values-as-hints-the-easy-way
(function(){
    var methods = {
        defaultValueActsAsHint: function (element) {
            element = $(element);
            element._default = element.value;
            
            return element.observe('focus', function (){
                if (element._default !== element.value) { 
                    return; 
                }
                element.removeClassName('hint').value = '';
            }).observe('blur', function (){
                if (element.value.strip() !== '') { 
                    return; 
                }
                element.addClassName('hint').value = element._default;
            }).addClassName('hint');
        }
    };
   
  $w('input textarea').each(function(tag){ Element.addMethods(tag, methods); });
})();

var codendi = codendi || { };

codendi.imgroot = codendi.imgroot || '/themes/common/images/';

codendi.locales = codendi.locales || { };

codendi.getText = function(key1, key2) {
    var str = codendi.locales[key1][key2];
    str = $A(arguments).slice(2).inject(str, function (str, value, index) {
        return str.gsub('$' + (index + 1), value);
    });
    return str
};

document.observe('dom:loaded', function () {
    
    $$('td.matrix_cell').each(function (cell) {
        var idx = cell.previousSiblings().length;
        var col = cell.up('table').down('tbody').childElements().collect(function (tr) {
            return tr.childElements()[idx];
        });
        cell.observe('mouseover', function (evt) {
            col.invoke('addClassName', 'matrix_highlight_col');
        }).observe('mouseout', function (evt) {
            col.invoke('removeClassName', 'matrix_highlight_col');
        });
    });
    
    //load protocheck, if needed
    new ProtoCheck();
});

window.CKEDITOR_BASEPATH = '/scripts/ckeditor/';

/**
 * Ajax.Request.abort
 * extend the prototype.js Ajax.Request object so that it supports an abort method
 *
 * @see http://blog.pothoven.net/2007/12/aborting-ajax-requests-for-prototypejs.html
 */
Ajax.Request.prototype.abort = function() {
    // prevent and state change callbacks from being issued
    this.transport.onreadystatechange = Prototype.emptyFunction;
    // abort the XHR
    this.transport.abort();
    // update the request counter
    Ajax.activeRequestCount--;
};
