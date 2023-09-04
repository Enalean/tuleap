/**
 * Copyright (c) Enalean, 2013 - Present. All rights reserved
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

import { initMentions } from "@tuleap/mention";
import CKEDITOR from "ckeditor4";
import TurndownService from "turndown";
import marked from "marked";

/* global Prototype:readonly Class:readonly $:readonly */

var codendi = window.codendi || {};

const turndown_service = new TurndownService({ emDelimiter: "*" });

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
                linkShowTargetTab: false,
            },
            options || {},
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
        const locale = document.body.dataset.userLocale;
        var replace_options = {
            resize_enabled: true,
            language: locale,
            disableNativeSpellChecker: false,
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
                ["Bold", "Italic"],
                ["NumberedList", "BulletedList", "-", "Blockquote", "Format"],
                ["Link", "Unlink", "Anchor", "Image"],
            ];
        } else if (this.options.toolbar === "tuleap") {
            toolbar = [
                ["Bold", "Italic"],
                ["NumberedList", "BulletedList", "-", "Blockquote", "Styles", "Format"],
                ["Link", "Unlink", "Anchor", "Image"],
                ["Source"],
            ];
            replace_options.stylesSet = [
                { name: "Bold", element: "strong", overrides: "b" },
                { name: "Italic", element: "em", overrides: "i" },
                { name: "Code", element: "code" },
                { name: "Subscript", element: "sub" },
                { name: "Superscript", element: "sup" },
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

        this.rte = CKEDITOR.replace(this.element.id, replace_options);

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
            initMentions(this.document.getBody().$);
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
            }.bind(this),
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
            }.bind(this),
        );
    },
    toggle: function (evt, option) {
        if (option == "html" && !this.rte) {
            const text = this.element.value;
            this.init_rte();
            this.rte.setData(marked(text));
        } else {
            const text = turndown_service.turndown(this.getContent());
            this.rte.destroy();
            this.rte = null;
            this.element.value = text;
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
