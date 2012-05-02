/**
* Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
*
* Originally written by Manuel VACELET, 2009
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
*/

var codendi = codendi || { };

/**
 *
 */
codendi.PluginForumml = Class.create({
    initialize: function(element) {
        // Toggle mail class name when click on the right button
        element.observe('click', function (event) {
            var link = Event.element(event);
            if (link) {
                var msgId = link.id.replace('plugin_forumml_toogle_msg_', '');
                var content = $('plugin_forumml_message_content_'+msgId);
                if (content) {
                    if (content.className == 'plugin_forumml_message_content_pre') {
                        content.className = 'plugin_forumml_message_content_std';
                    } else {
                        content.className = 'plugin_forumml_message_content_pre';
                    }
                }
            }
            event.stop();
        });
    },
});

document.observe('dom:loaded', function() {
    $$('.plugin_forumml_toggle_font').each(function (elmt) {
        new codendi.PluginForumml(elmt);
    });
});