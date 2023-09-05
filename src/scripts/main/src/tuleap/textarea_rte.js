/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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

/* global Class:readonly Builder:readonly $:readonly */
var tuleap = window.tuleap || {};
tuleap.textarea = tuleap.textarea || {};

import "../codendi/RichTextEditor.js";

tuleap.textarea.RTE = Class.create(window.codendi.RTE, {
    initialize: function ($super, element, options) {
        options = Object.extend({ toolbar: "tuleap", linkShowTargetTab: false }, options || {});
        this.options = Object.extend({ htmlFormat: false, id: 0 }, options || {});
        $super(element, options);
        // This div contains comment format selection buttons
        var div = Builder.node("div");
        var select_container = Builder.node("div", { class: "rte_format" });
        select_container.appendChild(document.createTextNode("Format : "));
        div.appendChild(select_container);

        var div_clear = Builder.node("div", { class: "rte_clear" });
        div.appendChild(div_clear);

        if (undefined == this.options.name) {
            this.options.name = "comment_format" + this.options.id;
        }

        var selectbox = Builder.node("select", {
            id: "rte_format_selectbox" + this.options.id,
            name: this.options.name,
            class: "small",
        });
        select_container.appendChild(selectbox);

        var text_value = "text";
        var html_value = "html";

        if (element.id === "tracker_artifact_comment") {
            text_value = "0";
            html_value = "1";
        }

        // Add an option that tells that the content format is text
        // The value is defined in Artifact class.
        var text_option = Builder.node(
            "option",
            { value: text_value, id: "comment_format_text" + this.options.id },
            "Text",
        );
        selectbox.appendChild(text_option);

        this.help_block = null;
        if (typeof this.element.dataset.helpId !== "undefined") {
            this.help_block = document.getElementById(this.element.dataset.helpId);
        }

        // Add an option that tells that the content format is HTML
        // The value is defined in Artifact class.
        var html_option = Builder.node(
            "option",
            { value: html_value, id: "comment_format_html" + this.options.id },
            "HTML",
        );
        selectbox.appendChild(html_option);

        Element.insert(this.element, { before: div });

        div.appendChild(this.element);

        if (this.options.htmlFormat == true) {
            selectbox.selectedIndex = 1;
            html_option.selected = true;
            text_option.selected = false;
        } else {
            selectbox.selectedIndex = 0;
            html_option.selected = false;
            text_option.selected = true;
        }

        if ($("comment_format_html" + this.options.id).selected == true) {
            if (this.help_block) {
                this.help_block.classList.add("shown");
            }
            this.init_rte();
        }

        if (this.options.toggle) {
            selectbox.observe("change", this.toggle.bindAsEventListener(this, selectbox));
        }
    },

    toggle: function ($super, event, selectbox) {
        var option = selectbox.options[selectbox.selectedIndex].value,
            id = this.element.id;

        if (option === "0") {
            option = "text";
        } else if (option === "1") {
            option = "html";
        }

        if (this.help_block) {
            this.help_block.classList.toggle("shown");
        }

        if ($(id).hasAttribute("data-required") && option == "text" && this.rte) {
            $(id).removeAttribute("data-required");
            $(id).writeAttribute("required", true);
        }

        $super(event, option);
    },

    init_rte: function ($super) {
        var id = this.element.id;

        $super();
        (function recordRequiredAttribute() {
            if ($(id).hasAttribute("required")) {
                $(id).removeAttribute("required");
                $(id).writeAttribute("data-required", true);
            }
        })();
    },
});
