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

var tuleap                   = tuleap || {};
tuleap.trackers              = tuleap.trackers || {};
tuleap.trackers.followup     = tuleap.trackers.followup || {};

tuleap.trackers.followup.RTE = Class.create(codendi.RTE, {
    initialize: function ($super, element, options) {
        options = Object.extend({toolbar: 'tuleap'}, options || { });
        this.options = Object.extend({htmlFormat : false, id : 0}, options || { });
        $super(element, options);
        // This div contains comment format selection buttons
        var div = Builder.node('div', {'class' : 'rte_format'});
        var bold = document.createElement("b");
        bold.appendChild(document.createTextNode("Format : "));
        div.appendChild(bold);

        var selectbox = Builder.node('select', {'id' : 'rte_format_selectbox'+this.options.id, 'name' : 'comment_format'+this.options.id});
        div.appendChild(selectbox);

        // Add an option that tells that the content format is text
        // The value is defined in Artifact class.
        var text_option = Builder.node(
            'option',
            {'value' : 'text', 'id' : 'comment_format_text'+this.options.id, 'selected' : 'selected'},
            "Text"
        );
        selectbox.appendChild(text_option);

        // Add an option that tells that the content format is HTML
        // The value is defined in Artifact class.
        var html_option = Builder.node(
            'option',
            {'value': 'html', 'id' : 'comment_format_html'+this.options.id},
            "HTML"
        );
        selectbox.appendChild(html_option);

        Element.insert(this.element, {before: div});

        // This div is used to clear the CSS of the pervious div
        var div_clear = Builder.node('div', {'class' : 'rte_clear'});
        Element.insert(this.element, {before: div_clear});

        if (options.htmlFormat == true) {
            this.switchButtonToHtml();
        } else {
            $('comment_format_text'+this.options.id).selected = true;
        }

        if ($('comment_format_html'+this.options.id).selected == true) {
            this.init_rte();
        }

        if (this.options.toggle) {
           selectbox.observe('change', this.toggle.bindAsEventListener(this, selectbox));
        }
    },

    toggle: function ($super, event, selectbox) {
        var option = selectbox.options[selectbox.selectedIndex].value;
        $super(event, option);
    },

    /**
     * Select the option that tells that the content is HTML
     */
    switchButtonToHtml: function () {
        $('comment_format_html'+this.options.id).selected = true;
    }
});

document.observe('dom:loaded', function () {
    var newFollowup = $('tracker_followup_comment_new');
    if (newFollowup) {
        new tuleap.trackers.followup.RTE(newFollowup, {toggle: true, default_in_html: false, id : 'new'});
    }
    var massChangeFollowup = $('artifact_masschange_followup_comment');
    if (massChangeFollowup) {
        new tuleap.trackers.followup.RTE(massChangeFollowup, {toggle: true, default_in_html: false, id: 'mass_change'});
    }
});
