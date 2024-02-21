/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Tuleap, 2017 - 2018. All rights reserved
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

/* global codendi:readonly ProtoMultiSelect:readonly $:readonly $$:readonly Ajax:readonly Effect:readonly */

import { createColorPicker } from "@tuleap/plugin-tracker-color-picker";

document.observe("dom:loaded", function () {
    initColorPicker();
    function initTextboxlistForDefaultValues() {
        if ($("tracker_artifact_textboxlist_default")) {
            var field_id = $("field_id").value;
            codendi.tracker.textboxlist[0] = new ProtoMultiSelect(
                "tracker_field_default",
                "tracker_artifact_textboxlist_default",
                {
                    fetchFile:
                        codendi.tracker.base_url + "?formElement=" + field_id + "&func=textboxlist",
                    loadOnInit: false,
                    newValues: false,
                    newValuePrefix: "!",
                },
            );
        }
    }

    function initColorPicker() {
        $$(".tracker-admin-one-list-field").each(function (selected_element) {
            selected_element.select(".vue-colorpicker-mount-point").each(function (element) {
                createColorPicker(element);
            });
        });
    }

    function tracker_register_hide_value() {
        $$(".tracker_admin_static_value_hidden_chk").each(function (checkbox) {
            var img = checkbox.next();
            checkbox.hide();
            img.setStyle({ cursor: "pointer" }).observe("click", function () {
                if (checkbox.checked) {
                    //switch to "hidden"
                    checkbox.checked = false;
                    checkbox.up("li").addClassName("tracker_admin_static_value_hidden");
                    img.src = img.src.gsub("eye.png", "eye-half.png");
                } else {
                    //switch to "shown"
                    checkbox.checked = true;
                    checkbox.up("li").removeClassName("tracker_admin_static_value_hidden");
                    img.src = img.src.gsub("eye-half.png", "eye.png");
                }
            });
        });
    }
    tracker_register_hide_value();

    var palette = $$(".tracker-admin-palette")[0];
    if (palette) {
        var admin_field_properties = $("tracker-admin-field-properties");
        if (!admin_field_properties) {
            admin_field_properties = new Element("div", {
                id: "tracker-admin-field-properties",
                className: "widget",
            })
                .hide()
                .update(
                    '<div class="widget_titlebar"><div class="widget_titlebar_title"></div></div><div class="widget_content"></div>',
                );
            palette.insert({
                after: admin_field_properties,
            });
        }

        $$("a.button[name^=create]").each(function (button) {
            button.observe("click", function (evt) {
                // Replace button icon with spinner
                var spinnerUrl = codendi.imgroot + "/ic/spinner-16.gif";
                var buttonImg = button.down("img");
                var buttonIcon = buttonImg.src;
                buttonImg.src = spinnerUrl;

                $$(".tracker-admin-field-selected").each(function (selected_element) {
                    if (selected_element.visible()) {
                        var element = selected_element.up(".tracker-admin-field");
                        if (element) {
                            element.childElements().invoke("show");
                        }
                        selected_element.hide();
                    }
                });
                var parameters = {};
                parameters[button.name] = 1;
                //eslint-disable-next-line @typescript-eslint/no-unused-vars
                var req = new Ajax.Request(button.up("form").action, {
                    parameters: parameters,
                    onComplete: function (transport) {
                        var rtes = [];

                        //Don't use directly updater since the form is stripped
                        admin_field_properties
                            .down(".widget_content")
                            .update("")
                            .insert(new Element("div").update(transport.responseText).down());
                        admin_field_properties
                            .down(".widget_titlebar_title")
                            .update("Create an element");
                        admin_field_properties.select("input[type=submit]")[0].insert({
                            before: new Element("a", {
                                href: "#cancel",
                            })
                                .observe("click", function (evt) {
                                    rtes.each(function (rte) {
                                        rte.destroy();
                                    });
                                    rtes = [];
                                    admin_field_properties.hide();
                                    palette.show();
                                    evt.stop();
                                })
                                .update(
                                    "&laquo; " + codendi.locales.tracker_formelement_admin.cancel,
                                ),
                        });
                        admin_field_properties.select("input[type=submit]")[0].insert({
                            before: new Element("span").update(" "),
                        });
                        palette.hide();
                        admin_field_properties.show();

                        //Put here the javascript stuff you need to call once the content of the modal dialog is loaded

                        //Richtext editor
                        admin_field_properties
                            .select(".tracker-field-richtext")
                            .each(function (element) {
                                rtes.push(
                                    new codendi.RTE(element, {
                                        onLoad: function () {
                                            admin_field_properties.setStyle({
                                                width: "auto",
                                                height: "auto",
                                            });
                                            admin_field_properties.setStyle({
                                                width: "auto",
                                                height: "auto",
                                            });
                                        },
                                    }),
                                );
                            });

                        //Edit list values
                        var e = new codendi.tracker.bind.Editor(admin_field_properties); //eslint-disable-line @typescript-eslint/no-unused-vars

                        // Restore button icon
                        buttonImg.src = buttonIcon;
                    },
                });
                evt.stop();
            });
        });

        $$("a.button_disabled[name^=create]").each(function (button_disabled) {
            button_disabled.observe("click", function () {
                alert(codendi.locales.tracker_formelement_admin.unique_field); //eslint-disable-line no-alert
            });
        });

        jQuery("#tracker-admin-new-layout").modal("show"); // eslint-disable-line no-undef

        $$(".tracker-admin-field-controls a.edit-field").each(function (a) {
            var selected_element, element;
            var rtes = [];
            a.observe("click", function (evt) {
                if (!selected_element) {
                    selected_element = new Element("div")
                        .hide()
                        .addClassName("tracker-admin-field-selected")
                        .addClassName("widget")
                        .update(
                            '<div class="widget_titlebar"><div class="widget_titlebar_title"></div></div><div class="widget_content"></div>',
                        );
                    element = a.up(".tracker-admin-field");
                    if (element) {
                        element.insert(selected_element);
                    } else {
                        element = a.up(".tracker-admin-container");
                        element.down().insert({
                            after: selected_element,
                        });
                    }
                }
                $$(".tracker-admin-field-selected").each(function (selected_element) {
                    if (selected_element.visible()) {
                        var element = selected_element.up(".tracker-admin-field");
                        if (element) {
                            element.childElements().invoke("show");
                        }
                        selected_element.hide();
                    }
                });
                if (admin_field_properties && admin_field_properties.visible()) {
                    admin_field_properties.hide();
                    palette.show();
                }
                //eslint-disable-next-line @typescript-eslint/no-unused-vars
                var r = new Ajax.Request(a.href, {
                    onComplete: function (transport) {
                        //Don't use directly updater since the form is stripped
                        selected_element
                            .down(".widget_content")
                            .update("")
                            .insert(new Element("div").update(transport.responseText).down());
                        selected_element.down(".widget_titlebar_title").update("Update an element");
                        var submit_button = selected_element.select("input[type=submit]")[0];
                        submit_button.insert({
                            before: new Element("a", {
                                href: "#cancel",
                            })
                                .observe("click", function (evt) {
                                    rtes.each(function (rte) {
                                        rte.destroy();
                                    });
                                    rtes = [];
                                    element.childElements().invoke("show");
                                    selected_element.hide();
                                    if (element.viewportOffset()[1] < 0) {
                                        element.scrollTo();
                                    }
                                    //eslint-disable-next-line @typescript-eslint/no-unused-vars
                                    var e = new Effect.Highlight(element, {
                                        queue: "end",
                                    });
                                    evt.stop();
                                })
                                .update(
                                    "&laquo; " + codendi.locales.tracker_formelement_admin.cancel,
                                ),
                        });
                        selected_element.select("input[type=submit]")[0].insert({
                            before: new Element("span").update(" "),
                        });
                        if (!element.hasClassName("tracker-admin-container")) {
                            element.childElements().invoke("hide");
                        }
                        selected_element.show();

                        //Put here the javascript stuff you need to call once the content of the modal dialog is loaded

                        //Color picker
                        selected_element
                            .select(".vue-colorpicker-mount-point")
                            .each(function (element) {
                                createColorPicker(element);
                            });

                        //Richtext editor
                        selected_element.select(".tracker-field-richtext").each(function (element) {
                            rtes.push(
                                new codendi.RTE(element, {
                                    onLoad: function () {
                                        admin_field_properties.setStyle({
                                            width: "auto",
                                            height: "auto",
                                        });
                                        admin_field_properties.setStyle({
                                            width: "auto",
                                            height: "auto",
                                        });
                                    },
                                }),
                            );
                        });

                        //Edit list values
                        var e = new codendi.tracker.bind.Editor(selected_element); //eslint-disable-line @typescript-eslint/no-unused-vars

                        //register hide action
                        tracker_register_hide_value();

                        initTextboxlistForDefaultValues();
                    },
                });
                evt.stop();
            });
        });
    }
});
