/*
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/* global $$:readonly Ajax:readonly Class:readonly $:readonly $H:readonly Selector:readonly */

var codendi = codendi || {};
codendi.tracker = codendi.tracker || {};
codendi.tracker.artifact = codendi.tracker.artifact || {};

codendi.tracker.artifact.artifactLink = {
    overlay_window: null,

    strike: function (td, checkbox) {
        td.up()
            .childElements()
            .invoke("setStyle", {
                textDecoration: checkbox.checked ? "line-through" : "none",
            });
    },
    enable_mass_unlink: function () {
        var checkboxes = $$(".tracker-artifact-link-mass-unlink");

        checkboxes.each(function (checkbox) {
            var tracker_panel = checkbox.up(".tracker-form-element-artifactlink-trackerpanel");

            checkbox.disabled = false;
            checkbox.stopObserving("click");
            checkbox.observe("click", function () {
                var tds = tracker_panel.select("td.tracker_report_table_unlink");
                var force_unlink = checkbox.checked;

                tds.each(function (td) {
                    var img = td.down("img");
                    var checkbox_line = td.down('input[type="checkbox"]');

                    if (!checkbox_line) {
                        return;
                    }

                    codendi.tracker.artifact.artifactLink.toggle_unlink(
                        checkbox_line,
                        img,
                        td,
                        force_unlink,
                    );
                });
            });
        });
    },

    toggle_unlink: function (checkbox, img, td, force_select) {
        var unlinked = codendi.imgroot + "ic/cross_red.png";
        var linked = codendi.imgroot + "ic/cross_grey.png";

        if (force_select) {
            checkbox.checked = true;
        } else {
            checkbox.checked = !checkbox.checked;
        }

        img.src = checkbox.checked ? unlinked : linked;

        codendi.tracker.artifact.artifactLink.strike(td, checkbox);
        codendi.tracker.artifact.artifactLink.load_nb_artifacts(
            checkbox.up(".tracker-form-element-artifactlink-trackerpanel"),
        );
        codendi.tracker.artifact.artifactLink.reload_aggregates_functions(
            checkbox.up(".tracker_artifact_field "),
        );
    },

    set_checkbox_style_as_cross: function (table_cells) {
        table_cells.each(function (td) {
            var unlinked = codendi.imgroot + "ic/cross_red.png";
            var linked = codendi.imgroot + "ic/cross_grey.png";
            if (td.down("span")) {
                td.down("span").hide();
            }
            if (!td.down("img")) {
                var checkbox = td.down('input[type="checkbox"]');
                if (checkbox) {
                    var img = new Element("img", {
                        src: checkbox.checked ? unlinked : linked,
                    })
                        .setStyle({
                            cursor: "pointer",
                            verticalAlign: "middle",
                        })
                        .observe("click", function () {
                            codendi.tracker.artifact.artifactLink.toggle_unlink(
                                checkbox,
                                img,
                                td,
                                false,
                            );

                            var table = checkbox.up(".tracker_report_table");
                            if (table) {
                                table.down(".tracker-artifact-link-mass-unlink").checked = false;
                            }
                        })
                        .observe("mouseover", function () {
                            img.src = checkbox.checked ? linked : unlinked;
                        })
                        .observe("mouseout", function () {
                            img.src = checkbox.checked ? unlinked : linked;
                        });
                    td.appendChild(img);
                    codendi.tracker.artifact.artifactLink.strike(td, checkbox);
                }
            }
        });
    },
    newArtifact: function (aid) {
        if (codendi.tracker.artifact.artifactLinker_currentField) {
            //add to the existing ones
            var input_field = codendi.tracker.artifact.artifactLinker_currentField.down(
                "input[type=text][name^=artifact]",
            );
            if (input_field.value) {
                input_field.value += ",";
            }
            input_field.value += aid;
            codendi.tracker.artifact.artifactLink.addTemporaryArtifactLinks();
            codendi.tracker.artifact.artifactLink.overlay_window.deactivate();
        }
    },
    showReverseArtifactLinks: function () {
        var show_reverse_artifact_button = $("display-tracker-form-element-artifactlink-reverse");

        if (show_reverse_artifact_button) {
            show_reverse_artifact_button.observe("click", function (event) {
                Event.stop(event);

                this.adjacent("#tracker-form-element-artifactlink-reverse").invoke("show");
                this.hide();
            });
        }
    },
    addTemporaryArtifactLinks: function () {
        if (codendi.tracker.artifact.artifactLinker_currentField) {
            var ids = codendi.tracker.artifact.artifactLinker_currentField.down(
                "input.tracker-form-element-artifactlink-new",
            ).value;
            if (
                $("lightwindow_contents") &&
                $("lightwindow_contents").down('input[name="link-artifact[manual]"]')
            ) {
                if (ids) {
                    ids += ",";
                }
                ids += $("lightwindow_contents").down('input[name="link-artifact[manual]"]').value;
            }
            ids = ids
                .split(",")
                .invoke("strip")
                .reject(function (id) {
                    //prevent doublons
                    return (
                        $$(
                            'input[name="artifact[' +
                                codendi.tracker.artifact.artifactLinker_currentField_id +
                                "][removed_values][" +
                                id +
                                '][]"]',
                        ).size() != 0
                    );
                })
                .join(",");
            if (ids) {
                var type_select = codendi.tracker.artifact.artifactLinker_currentField.down(
                    "select.tracker-form-element-artifactlink-new",
                );
                var type = "";
                if (type_select) {
                    type = type_select.value;
                }
                //eslint-disable-next-line @typescript-eslint/no-unused-vars
                var req = new Ajax.Request(codendi.tracker.base_url + "?", {
                    parameters: {
                        formElement: codendi.tracker.artifact.artifactLinker_currentField_id,
                        func: "fetch-artifacts",
                        ids,
                        type,
                    },
                    onSuccess: function (transport) {
                        if (transport.responseJSON) {
                            var json = transport.responseJSON;
                            if (json.rows) {
                                $H(json.rows).each(function (pair) {
                                    var renderer_table = $("tracker_report_table_" + pair.key);
                                    if (!renderer_table) {
                                        // remove the empty element
                                        var empty_value =
                                            codendi.tracker.artifact.artifactLinker_currentField.down(
                                                ".empty_value",
                                            );
                                        if (empty_value) {
                                            empty_value.remove();
                                        }
                                        var list =
                                            codendi.tracker.artifact.artifactLinker_currentField.down(
                                                ".tracker-form-element-artifactlink-list",
                                            );
                                        list.insert(json.head[pair.key] + "<tbody>");
                                        var first_list = $$(
                                            ".tracker-form-element-artifactlink-list ul",
                                        ).first();
                                        var tabs_id = first_list.up().identify();
                                        var current_tab = first_list.down(
                                            "li.tracker-form-element-artifactlink-list-nav-current",
                                        );
                                        if (pair.key.includes("type")) {
                                            codendi.tracker.artifact.artifactLink.tabs[
                                                tabs_id
                                            ].loadTypeTab(
                                                list.childElements().last().down("h2"),
                                                first_list,
                                            );
                                        } else {
                                            codendi.tracker.artifact.artifactLink.tabs[
                                                tabs_id
                                            ].loadTrackerTab(
                                                list.childElements().last().down("h2"),
                                                first_list,
                                            );
                                        }
                                        renderer_table = $("tracker_report_table_" + pair.key);
                                        if (typeof current_tab !== "undefined") {
                                            renderer_table.up("div").hide();
                                        }
                                    }

                                    //make sure new rows are inserted before the aggregate function row
                                    renderer_table
                                        .select("tr.tracker_report_table_aggregates")
                                        .invoke("remove");
                                    const tbody = renderer_table.down("tbody").insert(pair.value);
                                    const rows_edition_section = tbody.querySelectorAll(
                                        ".tracker_formelement_read_and_edit_edition_section",
                                    );
                                    [].forEach.call(
                                        rows_edition_section,
                                        function (row_edition_section) {
                                            row_edition_section.style.display = "block";
                                        },
                                    );
                                    const rows_read_only_section = tbody.querySelectorAll(
                                        ".tracker_formelement_read_and_edit_read_section",
                                    );
                                    [].forEach.call(
                                        rows_read_only_section,
                                        function (row_read_only_section) {
                                            row_read_only_section.style.display = "none";
                                        },
                                    );

                                    codendi.tracker.artifact.artifactLink.set_checkbox_style_as_cross(
                                        renderer_table.select("td.tracker_report_table_unlink"),
                                    );
                                    codendi.tracker.artifact.artifactLink.load_nb_artifacts(
                                        renderer_table.up(),
                                    );
                                });
                                codendi.tracker.artifact.artifactLink.reload_aggregates_functions(
                                    codendi.tracker.artifact.artifactLinker_currentField,
                                );
                                codendi.tracker.artifact.artifactLink.enable_mass_unlink();
                            }
                        }
                    },
                });
            }
        }
    },
    reload_aggregates_functions_request: null,
    reload_aggregates_functions: function (artifactlink_field) {
        //If there is a pending request, abort it
        if (codendi.tracker.artifact.artifactLink.reload_aggregates_functions_request) {
            codendi.tracker.artifact.artifactLink.reload_aggregates_functions_request.abort();
        }
        //remove old aggregates
        artifactlink_field.select("tr.tracker_report_table_aggregates").invoke("remove");

        var field_id = artifactlink_field
            .down(".tracker-form-element-artifactlink-new")
            .name.split("[")[1]
            .split("]")[0];

        //Compute the new aggregates
        codendi.tracker.artifact.artifactLink.reload_aggregates_functions_request =
            new Ajax.Request(codendi.tracker.base_url + "?", {
                parameters: {
                    formElement: field_id,
                    func: "fetch-aggregates",
                    ids: artifactlink_field
                        .select(
                            'input[type=checkbox][name^="artifact[' +
                                field_id +
                                '][removed_values]"]',
                        )
                        .reject(function (checkbox) {
                            return checkbox.checked;
                        })
                        .collect(function (checkbox) {
                            return checkbox.name.split("[")[3].split("]")[0];
                        })
                        .join(","),
                },
                onSuccess: function (transport) {
                    transport.responseJSON.tabs.each(function (tab) {
                        if ($("tracker_report_table_" + tab.key)) {
                            //make sure that the previous aggregates have been removed
                            $("tracker_report_table_" + tab.key)
                                .select("tr.tracker_report_table_aggregates")
                                .invoke("remove");
                            //insert the new ones
                            $("tracker_report_table_" + tab.key)
                                .down("tbody")
                                .insert(tab.src);
                        }
                    });
                },
            });
    },
    load_nb_artifacts: function (tracker_panel) {
        var nb_artifacts = tracker_panel
            .down("tbody")
            .childElements()
            .findAll(function (tr) {
                return !tr.hasClassName("tracker_report_table_aggregates");
            })
            .size();
        var h3 = tracker_panel.down("h3");
        var txt = nb_artifacts + " " + codendi.locales.tracker_artifact_link.nb_artifacts;
        if (h3) {
            h3.update(txt);
        } else {
            tracker_panel.insert({ top: "<h3>" + txt + "</h3>" });
        }
    },

    Tab: Class.create({
        // Store all tracker panels of this artifact links
        tracker_panels: [],
        ul: null,
        initialize: function (artifact_link) {
            var self = this;

            //build a nifty navigation list
            if (
                location.href.toQueryParams().func != "new-artifact" &&
                location.href.toQueryParams().func != "submit-artifact"
            ) {
                if (!location.href.toQueryParams().modal) {
                    this.ul = new Element("ul").addClassName(
                        "nav nav-tabs tracker-form-element-artifactlink-list-nav",
                    );
                }
            }

            if (this.ul) {
                artifact_link.insert({ top: this.ul });

                this.type_label = new Element("li").update(
                    codendi.getText("tracker_artifact_link", "type_label") + ":",
                );
                this.type_label.addClassName("tracker-form-element-artifactlink-list-nav-label");
                this.type_label.hide();

                this.trackerLabel = new Element("li").update(
                    codendi.getText("tracker_artifact_link", "trackers_label") + ":",
                );
                this.trackerLabel.addClassName("tracker-form-element-artifactlink-list-nav-label");
                this.trackerLabel.hide();

                this.ul.appendChild(this.type_label);
                this.ul.appendChild(this.trackerLabel);
            }

            var type_tabs = artifact_link.select(
                'h2[class*="tracker-form-element-artifactlink-type"]',
            );
            if (type_tabs.length > 0) {
                this.type_label.show();
                type_tabs.each(function (obj) {
                    self.loadTypeTab(obj);
                });
            }

            var trackerTabs = artifact_link.select(
                'h2[class*="tracker-form-element-artifactlink-tracker"]',
            );
            if (trackerTabs.length > 0) {
                this.trackerLabel.show();
                trackerTabs.each(function (obj) {
                    self.loadTrackerTab(obj);
                });
            }
        },

        showTrackerPanel: function (event, tracker_panel, element, h2) {
            var ul = element.up("ul");
            ul.childElements().invoke(
                "removeClassName",
                "tracker-form-element-artifactlink-list-nav-current",
            );
            ul.childElements().invoke("removeClassName", "active");
            element
                .up("li")
                .addClassName("tracker-form-element-artifactlink-list-nav-current active");

            // hide all panels
            tracker_panel.adjacent("div").invoke("hide");

            //except the wanted one
            tracker_panel.show();

            if (!ul.up("div").hasClassName("read-only")) {
                //change the current tracker for the selector
                codendi.tracker.artifact.artifactLink.selector_url.tracker =
                    h2.className.split("_")[1]; // class="tracker-form-element-artifactlink-tracker_974"
            }

            // stop the propagation of the event
            if (event) {
                Event.stop(event);
            }
        },
        loadTrackerTab: function (h2, tab_list) {
            if (typeof tab_list === "undefined") {
                tab_list = this.ul;
            }
            this.trackerLabel.show();
            this.loadTabAfter(h2, tab_list, tab_list.childElements().last());
        },
        loadTypeTab: function (h2, tab_list) {
            if (typeof tab_list === "undefined") {
                tab_list = this.ul;
            }
            this.type_label.show();
            this.loadTabAfter(h2, tab_list, this.trackerLabel.previous());
        },
        loadTabAfter: function (h2, tab_list, afterElement) {
            if (typeof tab_list === "undefined") {
                tab_list = this.ul;
            }

            var self = this;
            var tracker_panel = h2.up();
            //add a new navigation element
            var li = new Element("li");
            var a = new Element("a", {
                href: "#show-tab-" + h2.innerHTML,
            }).observe("click", function (evt) {
                self.showTrackerPanel(evt, tracker_panel, a, h2);
            });

            a.update(h2.innerHTML);
            if (tracker_panel.querySelector(".tracker-form-element-artifactlink-renderer-async")) {
                const spinner = document.createElement("i");
                spinner.classList.add("fa");
                spinner.classList.add("fa-spin");
                spinner.classList.add("fa-spinner");
                spinner.classList.add("tracker-form-element-artifactlink-tab-spinner");
                a.appendChild(spinner);

                document.addEventListener("renderer-async-loaded", function (evt) {
                    if (tracker_panel === evt.detail.tracker_panel) {
                        a.removeChild(spinner);
                    }
                });
            }

            li.appendChild(a);
            afterElement.insert({ after: li });

            //hide this panel and its title unless is first
            if (this.tracker_panels.size() == 0) {
                codendi.tracker.artifact.artifactLink.selector_url.tracker =
                    h2.className.split("_")[1]; // class="tracker-form-element-artifactlink-tracker_974"
            }

            var firstNotLabel = tab_list //eslint-disable-line @typescript-eslint/no-unused-vars
                .childElements()
                .grep(new Selector(":not(li.tracker-form-element-artifactlink-list-nav-label)"))[0];
            var current_tab = tab_list.down(
                "li.tracker-form-element-artifactlink-list-nav-current",
            );
            if (typeof current_tab === "undefined") {
                li.addClassName("tracker-form-element-artifactlink-list-nav-current active");
                this.showTrackerPanel(null, tracker_panel, a, h2);
            }

            h2.hide();

            //add this panel to the store
            this.tracker_panels.push(tracker_panel);
        },
        loadNbArtifacts: function () {
            this.tracker_panels.forEach(codendi.tracker.artifact.artifactLink.load_nb_artifacts);
        },
    }),
    tabs: {},

    selector_url: {
        tracker: null,
        "link-artifact-id": location.href.toQueryParams().aid
            ? location.href.toQueryParams().aid
            : "",
    },
};
