/**
 * Copyright (c) Enalean SAS, 2013 - 2018. All rights reserved
 * Copyright (c) STMicroelectronics, 2010. All rights reserved
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

import {
    getUploadImageOptions,
    initiateUploadImage,
} from "../tuleap/ckeditor/get-upload-image-options.js";
import CKEDITOR from "ckeditor";
import tuleap from "tuleap";

/* global Prototype:readonly Class:readonly $:readonly */

var codendi = window.codendi || {};

codendi.RTE = Class.create({
    initialize: function (element, options) {
        this.element = $(element);
        this.options = Object.extend(
            {
                toolbar: "tuleap", //basic | full | minimal | tuleap
                onLoad: Prototype.emptyFunction,
                toggle: false,
                default_in_html: true,
                autoresize_when_ready: true,
            },
            options || {}
        );

        this.rte = false;

        if (!this.options.toggle || this.options.default_in_html) {
            this.init_rte();
        }
    },

    can_be_resized: function () {
        var resize_enabled = this.options.resize_enabled;
        return typeof resize_enabled === "undefined" || resize_enabled;
    },

    init_rte: function () {
        var replace_options = {
            resize_enabled: true,
            language: document.body.dataset.userLocale,
        };

        if (CKEDITOR.instances && CKEDITOR.instances[this.element.id]) {
            CKEDITOR.instances[this.element.id].destroy(true);
        }

        let toolbar = "full";

        if (this.options.toolbar === "basic") {
            toolbar = [
                ["Styles", "Format", "Font", "FontSize"],
                ["Bold", "Italic", "Underline", "Strike", "-", "Subscript", "Superscript"],
                "/",
                ["TextColor", "BGColor"],
                ["NumberedList", "BulletedList", "-", "Outdent", "Indent", "Blockquote"],
                ["JustifyLeft", "JustifyCenter", "JustifyRight", "JustifyBlock"],
                ["Link", "Unlink", "Anchor", "Image"],
            ];
        } else if (this.options.toolbar === "minimal") {
            toolbar = [
                ["Bold", "Italic", "Underline"],
                ["NumberedList", "BulletedList", "-", "Blockquote", "Format"],
                ["Link", "Unlink", "Anchor", "Image"],
            ];
        } else if (this.options.toolbar === "tuleap") {
            toolbar = [
                ["Bold", "Italic", "Underline"],
                ["NumberedList", "BulletedList", "-", "Blockquote", "Format"],
                ["Link", "Unlink", "Anchor", "Image"],
                ["Source"],
            ];
        } else if (this.options.toolbar === "advanced") {
            toolbar = [
                [
                    "Cut",
                    "Copy",
                    "Paste",
                    "PasteText",
                    "PasteFromWord",
                    "-",
                    "Undo",
                    "Redo",
                    "Link",
                    "Unlink",
                    "Anchor",
                    "Image",
                    "Table",
                    "HorizontalRule",
                    "SpecialChar",
                    "-",
                    "Source",
                ],
                "/",
                [
                    "Bold",
                    "Italic",
                    "Underline",
                    "Strike",
                    "Subscript",
                    "Superscript",
                    "-",
                    "RemoveFormat",
                    "NumberedList",
                    "BulletedList",
                    "Format",
                ],
            ];
        }

        /*
             This is done for IE
             If we load the page and the RTE is displayed, IE will not
             catch the instanceCreated event on load (it will catch it later if we change
             the format between Text and HTML). So we have to set this option when loading
             */

        replace_options.toolbar = toolbar;
        if (!this.can_be_resized()) {
            replace_options.resize_enabled = false;
        }

        replace_options = Object.assign(replace_options, {
            ...getUploadImageOptions(this.element),
        });
        this.rte = CKEDITOR.replace(this.element.id, replace_options);
        initiateUploadImage(this.rte, replace_options, this.element);

        /*CKEDITOR filters HTML tags
              So, if your default text is like <blabla>, this will not be displayed.
              To "fix" this, we escape the textarea content.
              However, we don't need to espace this for non default values.
            */

        if (this.element.readAttribute("data-field-default-value") !== null) {
            var escaped_value = tuleap.escaper.html(this.element.value);
            this.rte.setData(escaped_value);
        }

        CKEDITOR.on("dialogDefinition", function (ev) {
            var tab,
                dialog = ev.data.name,
                definition = ev.data.definition;

            if (dialog === "link") {
                definition.removeContents("target");
            }

            if (dialog === "image") {
                tab = definition.getContents("Link");
                tab.remove("cmbTarget");
            }
        });

        this.rte.on("instanceReady", function () {
            this.document.getBody().$.contentEditable = true;
            tuleap.mention.init(this.document.getBody().$);
        });

        CKEDITOR.on(
            "instanceCreated",
            function (evt) {
                if (evt.editor === this.rte) {
                    this.options.onLoad();
                }

                if (!this.can_be_resized()) {
                    evt.editor.config.resize_enabled = false;
                }
            }.bind(this)
        );

        CKEDITOR.on(
            "instanceReady",
            function (evt) {
                if (evt.editor !== this.rte) {
                    return;
                }

                if (!this.options.autoresize_when_ready) {
                    return;
                }

                if (undefined != this.options.full_width && this.options.full_width) {
                    evt.editor.resize("100%", this.element.getHeight(), true);
                } else if (
                    this.element.getWidth() > 0 &&
                    (typeof this.options.no_resize === "undefined" || !this.options.no_resize)
                ) {
                    evt.editor.resize(this.element.getWidth(), this.element.getHeight(), true);
                }
            }.bind(this)
        );
    },
    toggle: function (evt, option) {
        if (option == "html" && !this.rte) {
            this.init_rte();
        } else {
            this.rte.destroy();
            this.rte = null;
        }
        Event.stop(evt);
        return false;
    },
    destroy: function () {
        try {
            this.rte.destroy(false);
        } catch (e) {
            // ignore
        }
        this.rte = null;
    },
    getContent: function () {
        return this.rte.getData();
    },
    isInstantiated: function () {
        return typeof this.rte === "object";
    },
});
