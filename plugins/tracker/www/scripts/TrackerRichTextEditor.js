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
tuleap.trackers.textarea     = tuleap.trackers.textarea || {};

tuleap.trackers.textarea.RTE = Class.create(codendi.RTE, {
    initialize: function ($super, element, options) {
        options = Object.extend({toolbar: 'tuleap'}, options || { });
        this.options = Object.extend({htmlFormat : false, id : 0}, options || { });
        $super(element, options);
        // This div contains comment format selection buttons
        var div = Builder.node('div');
        var select_container = Builder.node('div', {'class' : 'rte_format'});
        select_container.appendChild(document.createTextNode("Format : "));
        div.appendChild(select_container);

        var div_clear = Builder.node('div', {'class' : 'rte_clear'});
        div.appendChild(div_clear)

        if (undefined == this.options.name) {
            this.options.name = 'comment_format'+this.options.id;
        }

        var selectbox = Builder.node('select', {'id' : 'rte_format_selectbox'+this.options.id, 'name' : this.options.name, 'class' : 'input-small'});
        select_container.appendChild(selectbox);

        // Add an option that tells that the content format is text
        // The value is defined in Artifact class.
        var text_option = Builder.node(
            'option',
            {'value' : 'text', 'id' : 'comment_format_text'+this.options.id},
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

        div.appendChild(this.element);

        if (options.htmlFormat == true) {
            selectbox.selectedIndex = 1;
        } else {
            selectbox.selectedIndex = 0;
        }

        if ($('comment_format_html'+this.options.id).selected == true) {
            this.init_rte();
        }

        if (this.options.toggle) {
           selectbox.observe('change', this.toggle.bindAsEventListener(this, selectbox));
        }
    },

    toggle: function ($super, event, selectbox) {
        var option = selectbox.options[selectbox.selectedIndex].value,
            id     = this.element.id;

            if ($(id).hasAttribute("data-required") && option == 'text' && this.rte) {
                $(id).removeAttribute("data-required");
                $(id).writeAttribute("required", true);
            };

        $super(event, option);
    },

    init_rte : function($super) {
        var id = this.element.id;

        $super();
        (function recordRequiredAttribute() {
            if ($(id).hasAttribute("required")) {
                $(id).removeAttribute("required");
                $(id).writeAttribute("data-required", true);
            }
        })();
    }
});

document.observe('dom:loaded', function () {
    var newFollowup = $('tracker_followup_comment_new');
    if (newFollowup) {
        new tuleap.trackers.textarea.RTE(newFollowup, {toggle: true, default_in_html: false, id : 'new'});
    }
    var massChangeFollowup = $('artifact_masschange_followup_comment');
    if (massChangeFollowup) {
        new tuleap.trackers.textarea.RTE(massChangeFollowup, {toggle: true, default_in_html: false, id: 'mass_change'});
    }
});
