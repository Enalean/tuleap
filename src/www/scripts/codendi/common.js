/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
*
* Originally written by Nicolas Terray, 2008
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
* along with Codendi; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*
* 
*/

function help_window(helpurl) {
    var HelpWin = window.open(helpurl,'HelpWindow','scrollbars=yes,resizable=yes,toolbar=no,height=740,width=1000');
    HelpWin.focus();
}

//http://mir.aculo.us/2009/1/7/using-input-values-as-hints-the-easy-way
(function(){
  var methods = {
    defaultValueActsAsHint: function(element){
      element = $(element);
      element._default = element.value;
      
      return element.observe('focus', function(){
        if(element._default != element.value) return;
        element.removeClassName('hint').value = '';
      }).observe('blur', function(){
        if(element.value.strip() != '') return;
        element.addClassName('hint').value = element._default;
      }).addClassName('hint');
    }
  };
   
  $w('input textarea').each(function(tag){ Element.addMethods(tag, methods) });
})();

var codendi = {
    locales: {},
    getText: function(key1, key2) {
        return codendi.locales[key1][key2];
    }
}



document.observe('dom:loaded', function() {
    //search togglers
    $$('.toggler', '.toggler-hide', '.toggler-noajax', '.toggler-hide-noajax').each(function (toggler) {
        //prehide or preshow depending on the initial state of the toggler
        toggler.nextSiblings().invoke(toggler.hasClassName('toggler-hide') || toggler.hasClassName('toggler-hide-noajax')  ? 'hide' : 'show');
        
        toggler.observe('click', function() {
            //toggle next siblings
            toggler.nextSiblings().invoke(toggler.hasClassName('toggler') || toggler.hasClassName('toggler-noajax') ? 'hide' : 'show');
            
            //toggle the state
            if (toggler.hasClassName('toggler-noajax') || toggler.hasClassName('toggler-hide-noajax')) {
                toggler.toggleClassName('toggler-noajax')
                       .toggleClassName('toggler-hide-noajax');
            } else {
                toggler.toggleClassName('toggler')
                       .toggleClassName('toggler-hide');
                //save the state with ajax only if the toggler has an id
                if (toggler.id) {
                    new Ajax.Request('/toggler.php', {
                            parameters: {
                                id: toggler.id
                            }
                    });
                }
            }
        });
    });
});
