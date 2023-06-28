/**
 * Copyright (c) Enalean, 2013 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

/* global Ajax:readonly Class:readonly jQuery:readonly Field:readonly */

/**
 * Adapted from https://groups.google.com/forum/?fromgroups=#!topic/prototype-scriptaculous/HcCxMmdAyjk
 */
Ajax.InPlaceMultiCollectionEditor = Class.create(Ajax.InPlaceCollectionEditor, {
    createEditField: function () {
        var list = new Element("select");
        list.name = this.options.paramName;
        list.size = 1;

        if (this.options.multiple) {
            list.writeAttribute("multiple");
            list.size = 2;
        }

        this._controls.editor = list;
        this._collection = this.options.collection || [];

        this.checkForExternalText();

        this._form.appendChild(this._controls.editor);

        if (jQuery && typeof jQuery(list).select2 === "function") {
            jQuery(list).select2({ width: "250px" });
        }
    },

    buildOptionList: function () {
        this._form.removeClassName(this.options.loadingClassName);
        this._controls.editor.update("");

        this.getSelectedUsers();
        this._collection.each(
            function (option) {
                var option_element = new Element("option"),
                    option_key = option[0],
                    option_val = option[1];

                option_element.value = option_key;
                option_element.selected = option_key in this.options.selected ? true : false;
                option_element.appendChild(document.createTextNode(option_val));

                this._controls.editor.appendChild(option_element);
            }.bind(this)
        );

        this._controls.editor.disabled = false;
        Field.scrollFreeActivate(this._controls.editor);
    },

    getSelectedUsers: function () {
        this.options.selected = {};
    },
});
