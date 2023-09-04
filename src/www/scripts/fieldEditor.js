/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Originally written by Nicolas Terray, 2006
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

/* global Class:readonly $:readonly Insertion:readonly Ajax:readonly codendi:readonly $F:readonly */

// Define namespace to prevent clashes
if (!com) {
    var com = {};
}
if (!com.xerox) {
    com.xerox = {};
}
if (!com.xerox.codendi) {
    com.xerox.codendi = {};
}
if (!com.xerox.codendi.tracker) {
    com.xerox.codendi.tracker = {};
}

com.xerox.codendi.FieldEditor = Class.create();
Object.extend(com.xerox.codendi.FieldEditor.prototype, {
    initialize: function (element, options) {
        this.element = $(element);
        this.options = Object.extend(
            {
                edit: "edit",
                preview: "preview",
                warning: "Your modifications are not saved. Do not forget to submit the form!",
                highlightcolor: Ajax.InPlaceEditor.defaultHighlightColor,
                highlightendcolor: "#FFFFFF",
            },
            options || {},
        );
        var element_offsetHeight = this.element.offsetHeight;
        var element_offsetWidth = this.element.offsetWidth;
        Element.hide(this.element);
        new Insertion.Before(
            this.element,
            '<div><a href="" id="' +
                this.element.id +
                '_edit_or_cancel">[' +
                this.options.edit +
                "]</a></div>",
        );
        new Insertion.After(
            this.element,
            '<div style="font-family:monospace; font-size:10pt;" id="' +
                this.element.id +
                '_preview">' +
                /*$F(this.element).escapeHTML() +*/ "</div>",
        );

        this.preview = $(this.element.id + "_preview");
        this.edit_cancel = $(this.element.id + "_edit_or_cancel");
        this.is_in_edit_mode = false;
        this.warning_displayed = false;

        if (this.preview.offsetHeight > element_offsetHeight) {
            Element.setStyle(this.element, {
                height: this.preview.offsetHeight + "px",
            });
        }
        if (this.preview.offsetWidth > element_offsetWidth) {
            Element.setStyle(this.element, {
                width: this.preview.offsetWidth + "px",
            });
        }
        this.updatePreview(false);

        this.onclickListener = this.toggleEditMode.bindAsEventListener(this);
        Event.observe(this.edit_cancel, "click", this.onclickListener);
    },
    toggleEditMode: function (evt) {
        if (this.is_in_edit_mode) {
            this.updatePreview(true);
        } else {
            Element.hide(this.preview);
            Element.show(this.element);
            this.edit_cancel.innerText = "[" + this.options.preview + "]";
            this.is_in_edit_mode = true;
        }
        Event.stop(evt);
        return false;
    },
    updatePreview: function (display_warning) {
        new Ajax.Updater(this.preview, "/make_links.php?group_id=" + this.options.group_id, {
            parameters: {
                text: $F(this.element),
            },
            onComplete: function () {
                Element.show(this.preview);
                Element.hide(this.element);
                this.edit_cancel.innerText = "[" + this.options.edit + "]";
                this.is_in_edit_mode = false;
                if (display_warning && !this.warning_displayed) {
                    new Insertion.After(this.edit_cancel, " <em>" + this.options.warning + "</em>");
                    this.warning_displayed = true;
                }
                codendi.Tooltip.load(this.preview);
            }.bind(this),
        });
    },
});
