/**
 * Copyright (c) STMicroelectronics 2011. All rights reserved
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

var RTE_Tracker_FollowUp = Class.create(codendi.RTE, {
    initialize: function ($super, element, options) {
        this.options = Object.extend({htmlFormat : false, id : 0}, options || { });
        $super(element, options);
        // This div contains comment format selection buttons
        var div = Builder.node('div', {'class' : 'rte_format'});
        var bold = document.createElement("b");
        bold.appendChild(document.createTextNode("Comment format : "));
        div.appendChild(bold);

        // Add a radio button that tells that the content format is text
        // The value is defined in Artifact class.
        var text_button = Builder.node('input', {'name'     : 'comment_format'+this.options.id,
                                                 'type'     : 'radio',
                                                 'value'    : '0',
                                                 'checked'  : 'checked',
                                                 'id'       : 'comment_format_text'+this.options.id});
        div.appendChild(text_button);
        div.appendChild(document.createTextNode('Text'));

        // Add a radio button that tells that the content format is HTML
        // The value is defined in Artifact class.
        var html_button = Builder.node('input', {'name' : 'comment_format'+this.options.id,
                                                 'type' : 'radio',
                                                 'value': '1',
                                                 'id'   : 'comment_format_html'+this.options.id});
        div.appendChild(html_button);
        div.appendChild(document.createTextNode('HTML'));

        Element.insert(this.element, {before: div});

        // This div is used to clear the CSS of the pervious div
        var div_clear = Builder.node('div', {'class' : 'rte_clear'});
        Element.insert(this.element, {before: div_clear});

        if (options.htmlFormat == true) {
            this.switchButtonToHtml();
        } else {
            $('comment_format_text'+this.options.id).checked = true;
        }
    },

    toggle: function ($super, event) {
        this.switchButtonToHtml();
        $super(event);
    },

    /**
     * Disable the radio button that tells that the content is text
     * Check the radio button that tells that the content is HTML
     */
    switchButtonToHtml: function () {
        $('comment_format_text'+this.options.id).disabled = true;
        $('comment_format_html'+this.options.id).checked  = true;
    }
});

document.observe('dom:loaded', function () {
    var newFollowup = $('tracker_followup_comment_new');
    if (newFollowup) {
        new RTE_Tracker_FollowUp(newFollowup, {toggle: true, default_in_html: false, id : 'new'});
    }
    var massChangeFollowup = $('tracker_followup_comment_mass_change');
    if (massChangeFollowup) {
        new RTE_Tracker_FollowUp(massChangeFollowup, {toggle: true, default_in_html: false, id: 'mass_change'});
    }
});