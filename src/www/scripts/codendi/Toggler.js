/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2011. All rights reserved
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

codendi.Toggler = {
    init: function (element, force_display, force_ajax) {
        $(element).select('.toggler', '.toggler-hide', '.toggler-noajax', '.toggler-hide-noajax').each(function (toggler) {
            codendi.Toggler.load(toggler, force_display, force_ajax);
        });
    },
    load: function (toggler, force_display, force_ajax) {
        if (force_display) {
            var was_noajax = toggler.hasClassName('toggler-hide-noajax') || toggler.hasClassName('toggler-noajax');
            toggler.removeClassName('toggler-hide');
            toggler.removeClassName('toggler-hide-noajax');
            toggler.removeClassName('toggler');
            toggler.removeClassName('toggler-noajax');
            if (force_display == 'show') {
                if (was_noajax) {
                    toggler.addClassName('toggler');
                } else {
                    toggler.addClassName('toggler-noajax');
                }
            } else {
                if (was_noajax) {
                    toggler.addClassName('toggler-hide');
                } else {
                    toggler.addClassName('toggler-hide-noajax');
                }
            }
        }
        
        if (force_ajax) {
            var was_hide = toggler.hasClassName('toggler-hide') || toggler.hasClassName('toggler-hide-noajax');
            toggler.removeClassName('toggler-hide');
            toggler.removeClassName('toggler-hide-noajax');
            toggler.removeClassName('toggler');
            toggler.removeClassName('toggler-noajax');
            if (force_ajax == 'ajax') {
                if (was_hide) {
                    toggler.addClassName('toggler-hide');
                } else {
                    toggler.addClassName('toggler');
                }
            } else {
                if (was_hide) {
                    toggler.addClassName('toggler-hide-noajax');
                } else {
                    toggler.addClassName('toggler-noajax');
                }
            }
        }
        
        //prehide or preshow depending on the initial state of the toggler
        toggler.nextSiblings().invoke(toggler.hasClassName('toggler-hide') || toggler.hasClassName('toggler-hide-noajax')  ? 'hide' : 'show');
        
        toggler.observe('click', function () {
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
                    var req = new Ajax.Request(
                        '/toggler.php', 
                        {
                            parameters: {
                                id: toggler.id
                            }
                        }
                    );
                }
            }
        });
    }
};

document.observe('dom:loaded', function () {
    //search togglers
    codendi.Toggler.init(document.body);
});
