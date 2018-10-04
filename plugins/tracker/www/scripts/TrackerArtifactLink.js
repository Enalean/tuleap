/*
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

var codendi = codendi || {};
codendi.tracker = codendi.tracker || {};
codendi.tracker.artifact = codendi.tracker.artifact || {};

codendi.tracker.artifact.artifactLink = {
    overlay_window: null,

    strike: function(td, checkbox) {
        td.up()
            .childElements()
            .invoke("setStyle", {
                textDecoration: checkbox.checked ? "line-through" : "none"
            });
    },
    enable_mass_unlink: function() {
        var checkboxes = $$(".tracker-artifact-link-mass-unlink");

        checkboxes.each(function(checkbox) {
            var tracker_panel = checkbox.up(".tracker-form-element-artifactlink-trackerpanel");

            checkbox.stopObserving("click");
            checkbox.observe("click", function() {
                var tds = tracker_panel.select("td.tracker_report_table_unlink");
                var force_unlink = checkbox.checked;

                tds.each(function(td) {
                    var img = td.down("img");
                    var checkbox_line = td.down('input[type="checkbox"]');

                    if (!checkbox_line) {
                        return;
                    }

                    codendi.tracker.artifact.artifactLink.toggle_unlink(
                        checkbox_line,
                        img,
                        td,
                        force_unlink
                    );
                });
            });
        });
    },

    toggle_unlink: function(checkbox, img, td, force_select) {
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
            checkbox.up(".tracker-form-element-artifactlink-trackerpanel")
        );
        codendi.tracker.artifact.artifactLink.reload_aggregates_functions(
            checkbox.up(".tracker_artifact_field ")
        );
    },

    set_checkbox_style_as_cross: function(table_cells) {
        table_cells.each(function(td) {
            var unlinked = codendi.imgroot + "ic/cross_red.png";
            var linked = codendi.imgroot + "ic/cross_grey.png";
            if (td.down("span")) {
                td.down("span").hide();
            }
            if (!td.down("img")) {
                var checkbox = td.down('input[type="checkbox"]');
                if (checkbox) {
                    var img = new Element("img", {
                        src: checkbox.checked ? unlinked : linked
                    })
                        .setStyle({
                            cursor: "pointer",
                            verticalAlign: "middle"
                        })
                        .observe("click", function(evt) {
                            codendi.tracker.artifact.artifactLink.toggle_unlink(
                                checkbox,
                                img,
                                td,
                                false
                            );

                            var table = checkbox.up(".tracker_report_table");
                            if (table) {
                                table.down(".tracker-artifact-link-mass-unlink").checked = false;
                            }
                        })
                        .observe("mouseover", function(evt) {
                            img.src = checkbox.checked ? linked : unlinked;
                        })
                        .observe("mouseout", function(evt) {
                            img.src = checkbox.checked ? unlinked : linked;
                        });
                    td.appendChild(img);
                    codendi.tracker.artifact.artifactLink.strike(td, checkbox);
                }
            }
        });
    },
    newArtifact: function(aid) {
        if (codendi.tracker.artifact.artifactLinker_currentField) {
            //add to the existing ones
            var input_field = codendi.tracker.artifact.artifactLinker_currentField.down(
                "input[type=text][name^=artifact]"
            );
            if (input_field.value) {
                input_field.value += ",";
            }
            input_field.value += aid;
            codendi.tracker.artifact.artifactLink.addTemporaryArtifactLinks();
            overlay_window.deactivate();
        }
    },
    showReverseArtifactLinks: function() {
        var show_reverse_artifact_button = $("display-tracker-form-element-artifactlink-reverse");

        if (show_reverse_artifact_button) {
            show_reverse_artifact_button.observe("click", function(event) {
                Event.stop(event);

                this.adjacent("#tracker-form-element-artifactlink-reverse").invoke("show");
                this.hide();
            });
        }
    },
    addTemporaryArtifactLinks: function() {
        if (codendi.tracker.artifact.artifactLinker_currentField) {
            var ids = codendi.tracker.artifact.artifactLinker_currentField.down(
                "input.tracker-form-element-artifactlink-new"
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
                .reject(function(id) {
                    //prevent doublons
                    return (
                        $$(
                            'input[name="artifact[' +
                                codendi.tracker.artifact.artifactLinker_currentField_id +
                                "][removed_values][" +
                                id +
                                '][]"]'
                        ).size() != 0
                    );
                })
                .join(",");
            if (ids) {
                var nature_select = codendi.tracker.artifact.artifactLinker_currentField.down(
                    "select.tracker-form-element-artifactlink-new"
                );
                var nature = "";
                if (nature_select) {
                    nature = nature_select.value;
                }
                var req = new Ajax.Request(codendi.tracker.base_url + "?", {
                    parameters: {
                        formElement: codendi.tracker.artifact.artifactLinker_currentField_id,
                        func: "fetch-artifacts",
                        ids: ids,
                        nature: nature
                    },
                    onSuccess: function(transport) {
                        if (transport.responseJSON) {
                            var json = transport.responseJSON;
                            if (json.rows) {
                                $H(json.rows).each(function(pair) {
                                    var renderer_table = $("tracker_report_table_" + pair.key);
                                    if (!renderer_table) {
                                        // remove the empty element
                                        var empty_value = codendi.tracker.artifact.artifactLinker_currentField.down(
                                            ".empty_value"
                                        );
                                        if (empty_value) {
                                            empty_value.remove();
                                        }
                                        var list = codendi.tracker.artifact.artifactLinker_currentField.down(
                                            ".tracker-form-element-artifactlink-list"
                                        );
                                        list.insert(json.head[pair.key] + "<tbody>");
                                        var first_list = $$(
                                            ".tracker-form-element-artifactlink-list ul"
                                        ).first();
                                        var tabs_id = first_list.up().identify();
                                        var current_tab = first_list.down(
                                            "li.tracker-form-element-artifactlink-list-nav-current"
                                        );
                                        if (pair.key.includes("nature")) {
                                            codendi.tracker.artifact.artifactLink.tabs[
                                                tabs_id
                                            ].loadNatureTab(
                                                list
                                                    .childElements()
                                                    .last()
                                                    .down("h2"),
                                                first_list
                                            );
                                        } else {
                                            codendi.tracker.artifact.artifactLink.tabs[
                                                tabs_id
                                            ].loadTrackerTab(
                                                list
                                                    .childElements()
                                                    .last()
                                                    .down("h2"),
                                                first_list
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
                                        ".tracker_formelement_read_and_edit_edition_section"
                                    );
                                    [].forEach.call(rows_edition_section, function(
                                        row_edition_section
                                    ) {
                                        row_edition_section.style.display = "block";
                                    });
                                    const rows_read_only_section = tbody.querySelectorAll(
                                        ".tracker_formelement_read_and_edit_read_section"
                                    );
                                    [].forEach.call(rows_read_only_section, function(
                                        row_read_only_section
                                    ) {
                                        row_read_only_section.style.display = "none";
                                    });

                                    codendi.tracker.artifact.artifactLink.set_checkbox_style_as_cross(
                                        renderer_table.select("td.tracker_report_table_unlink")
                                    );
                                    codendi.tracker.artifact.artifactLink.load_nb_artifacts(
                                        renderer_table.up()
                                    );
                                });
                                codendi.tracker.artifact.artifactLink.reload_aggregates_functions(
                                    codendi.tracker.artifact.artifactLinker_currentField
                                );
                                codendi.tracker.artifact.artifactLink.enable_mass_unlink();
                            }
                        }
                    }
                });
            }
        }
    },
    reload_aggregates_functions_request: null,
    reload_aggregates_functions: function(artifactlink_field) {
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
        codendi.tracker.artifact.artifactLink.reload_aggregates_functions_request = new Ajax.Request(
            codendi.tracker.base_url + "?",
            {
                parameters: {
                    formElement: field_id,
                    func: "fetch-aggregates",
                    ids: artifactlink_field
                        .select(
                            'input[type=checkbox][name^="artifact[' +
                                field_id +
                                '][removed_values]"]'
                        )
                        .reject(function(checkbox) {
                            return checkbox.checked;
                        })
                        .collect(function(checkbox) {
                            return checkbox.name.split("[")[3].split("]")[0];
                        })
                        .join(",")
                },
                onSuccess: function(transport) {
                    transport.responseJSON.tabs.each(function(tab) {
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
                }
            }
        );
    },
    load_nb_artifacts: function(tracker_panel) {
        var nb_artifacts = tracker_panel
            .down("tbody")
            .childElements()
            .findAll(function(tr) {
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
        initialize: function(artifact_link) {
            var self = this;

            //build a nifty navigation list
            if (
                location.href.toQueryParams().func != "new-artifact" &&
                location.href.toQueryParams().func != "submit-artifact"
            ) {
                if (!location.href.toQueryParams().modal) {
                    this.ul = new Element("ul").addClassName(
                        "nav nav-tabs tracker-form-element-artifactlink-list-nav"
                    );
                }
            }

            if (this.ul) {
                artifact_link.insert({ top: this.ul });

                this.natureLabel = new Element("li").update(
                    codendi.getText("tracker_artifact_link", "nature_label") + ":"
                );
                this.natureLabel.addClassName("tracker-form-element-artifactlink-list-nav-label");
                this.natureLabel.hide();

                this.trackerLabel = new Element("li").update(
                    codendi.getText("tracker_artifact_link", "trackers_label") + ":"
                );
                this.trackerLabel.addClassName("tracker-form-element-artifactlink-list-nav-label");
                this.trackerLabel.hide();

                this.ul.appendChild(this.natureLabel);
                this.ul.appendChild(this.trackerLabel);
            }

            var natureTabs = artifact_link.select(
                'h2[class*="tracker-form-element-artifactlink-nature"]'
            );
            if (natureTabs.length > 0) {
                this.natureLabel.show();
                natureTabs.each(function(obj) {
                    self.loadNatureTab(obj);
                });
            }

            var trackerTabs = artifact_link.select(
                'h2[class*="tracker-form-element-artifactlink-tracker"]'
            );
            if (trackerTabs.length > 0) {
                this.trackerLabel.show();
                trackerTabs.each(function(obj) {
                    self.loadTrackerTab(obj);
                });
            }
        },

        showTrackerPanel: function(event, tracker_panel, element, h2) {
            var ul = element.up("ul");
            ul.childElements().invoke(
                "removeClassName",
                "tracker-form-element-artifactlink-list-nav-current"
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
                codendi.tracker.artifact.artifactLink.selector_url.tracker = h2.className.split(
                    "_"
                )[1]; // class="tracker-form-element-artifactlink-tracker_974"
            }

            // stop the propagation of the event
            if (event) {
                Event.stop(event);
            }
        },
        loadTrackerTab: function(h2, tab_list) {
            if (typeof tab_list === "undefined") {
                tab_list = this.ul;
            }
            this.trackerLabel.show();
            this.loadTabAfter(h2, tab_list, tab_list.childElements().last());
        },
        loadNatureTab: function(h2, tab_list) {
            if (typeof tab_list === "undefined") {
                tab_list = this.ul;
            }
            this.natureLabel.show();
            this.loadTabAfter(h2, tab_list, this.trackerLabel.previous());
        },
        loadTabAfter: function(h2, tab_list, afterElement) {
            if (typeof tab_list === "undefined") {
                tab_list = this.ul;
            }

            var self = this;
            var tracker_panel = h2.up();
            codendi.tracker.artifact.artifactLink.load_nb_artifacts(tracker_panel);
            //add a new navigation element
            var li = new Element("li");
            var a = new Element("a", {
                href: "#show-tab-" + h2.innerHTML
            }).observe(
                "click",
                function(evt) {
                    self.showTrackerPanel(evt, tracker_panel, a, h2);
                }.bind(this)
            );

            a.update(h2.innerHTML);

            li.appendChild(a);
            afterElement.insert({ after: li });

            //hide this panel and its title unless is first
            if (this.tracker_panels.size() == 0) {
                codendi.tracker.artifact.artifactLink.selector_url.tracker = h2.className.split(
                    "_"
                )[1]; // class="tracker-form-element-artifactlink-tracker_974"
            }

            var firstNotLabel = tab_list
                .childElements()
                .grep(new Selector(":not(li.tracker-form-element-artifactlink-list-nav-label)"))[0];
            var current_tab = tab_list.down(
                "li.tracker-form-element-artifactlink-list-nav-current"
            );
            if (typeof current_tab === "undefined") {
                li.addClassName("tracker-form-element-artifactlink-list-nav-current active");
                this.showTrackerPanel(null, tracker_panel, a, h2);
            }

            h2.hide();

            //add this panel to the store
            this.tracker_panels.push(tracker_panel);
        }
    }),
    tabs: {},

    selector_url: {
        tracker: null,
        "link-artifact-id": location.href.toQueryParams().aid
            ? location.href.toQueryParams().aid
            : ""
    }
};

document.observe("dom:loaded", function() {
    if ($("tracker_id")) {
        codendi.tracker.artifact.artifactLink.selector_url.tracker = $("tracker_id").value;
    }

    (function() {
        $$(
            "#tracker_report_table_nature__is_child > tbody > tr > td.tracker-artifact-rollup-view > a.direct-link-to-artifact"
        ).each(function(link) {
            initRollupViewOfLink(link, 1);
        });

        function initRollupViewOfLink(link, depth) {
            var cell = link.parentNode,
                row = cell.parentNode,
                row_id = row.identify(),
                next_row = row.nextElementSibling,
                tbody = row.parentNode,
                icon = document.createElement("i"),
                artifact_id = link.dataset.artifactId,
                limit = 50,
                children = [];

            cell.classList.add("tracker-artifact-rollup-view");
            icon.classList.add("tracker-artifact-rollup-view-icon", "fa");
            cell.insertBefore(icon, link);

            loadChildrenRecursively(0);

            function loadChildrenRecursively(offset) {
                new Ajax.Request("/api/artifacts/" + artifact_id + "/linked_artifacts", {
                    method: "GET",
                    requestHeaders: {
                        Accept: "application/json"
                    },
                    parameters: {
                        direction: "forward",
                        nature: "_is_child",
                        offset: offset,
                        limit: limit
                    },
                    onSuccess: function(transport) {
                        children = children.concat(transport.responseJSON.collection);

                        if (offset + limit < transport.getResponseHeader("X-Pagination-Size")) {
                            loadChildrenRecursively(offset + limit);
                        } else if (children.length > 0) {
                            injectChildrenInTable(children);
                        }
                    }
                });
            }

            function injectChildrenInTable(children_to_inject) {
                icon.classList.add("fa-caret-right");

                icon.addEventListener("click", function() {
                    simpleExpandCollapse(this, children_to_inject);
                });
            }

            function simpleExpandCollapse(icon_clicked, children_to_inject) {
                icon_clicked.classList.toggle("fa-caret-right");
                icon_clicked.classList.toggle("fa-caret-down");

                var subrows = icon_clicked
                    .closest("tbody")
                    .querySelectorAll('[data-child-of="' + icon_clicked.closest("tr").id + '"]');

                if (subrows.length <= 0) {
                    subrows = children_to_inject.map(injectChildInTable);
                    subrows.forEach(function(row) {
                        initRollupViewOfLink(
                            row.querySelector("a.direct-link-to-artifact"),
                            depth + 1
                        );
                    });
                } else {
                    if (icon_clicked.classList.contains("fa-caret-right")) {
                        subrows.forEach(collapseRow);
                    } else {
                        subrows.forEach(expandRow);
                    }
                }
            }

            function injectChildInTable(child) {
                var additional_row = document.createElement("tr"),
                    modified_date = new Date(child.last_modified_date);

                additional_row.dataset.childOf = row_id;
                additional_row.innerHTML =
                    ' \
                    <td class="tracker_report_table_unlink"></td> \
                    <td class="tracker-artifact-rollup-view" style="padding-left: ' +
                    depth * 20 +
                    'px;"> \
                        <a class="direct-link-to-artifact" \
                            href="' +
                    child.html_url +
                    '" \
                            data-artifact-id="' +
                    child.id +
                    '" \
                        >' +
                    child.xref +
                    "</a> \
                    </td> \
                    <td>" +
                    child.project.label +
                    "</td> \
                    <td>" +
                    child.tracker.label +
                    "</td> \
                    <td>" +
                    child.title +
                    "</td> \
                    <td>" +
                    child.status +
                    "</td> \
                    <td>" +
                    formatDate(modified_date) +
                    "</td> \
                    <td>" +
                    formatUser(child.submitted_by_user) +
                    "</td> \
                    <td>" +
                    child.assignees.map(formatUser).join(", ") +
                    "</td>";

                if (next_row) {
                    tbody.insertBefore(additional_row, next_row);
                } else {
                    tbody.appendChild(additional_row);
                }

                return additional_row;
            }
        }

        function collapseRow(row) {
            var subrows = row.parentNode.querySelectorAll('[data-child-of="' + row.id + '"]');
            row.style.display = "none";
            [].forEach.call(subrows, collapseRow);
        }

        function expandRow(row) {
            var tr_rollup_view = row.querySelector(".tracker-artifact-rollup-view");
            var icon_down = tr_rollup_view.querySelector(".fa-caret-down");
            var icon_right = tr_rollup_view.querySelector(".fa-caret-right");

            if (icon_down && !icon_right) {
                var subrows = row.parentNode.querySelectorAll('[data-child-of="' + row.id + '"]');
                row.style.display = "table-row";
                [].forEach.call(subrows, expandRow);
            }

            if (!icon_down) {
                row.style.display = "table-row";
            }
        }

        function formatDate(date) {
            return (
                date.getFullYear() +
                "-" +
                ("0" + date.getMonth()).substr(-2) +
                "-" +
                ("0" + date.getDay()).substr(-2) +
                " " +
                ("0" + date.getHours()).substr(-2) +
                ":" +
                ("0" + date.getMinutes()).substr(-2)
            );
        }

        function formatUser(user_json) {
            return (
                '<a href="' +
                user_json.user_url +
                '"> \
                    ' +
                user_json.display_name +
                " \
                </a>"
            );
        }
    })();

    overlay_window = new lightwindow({
        resizeSpeed: 10,
        delay: 0,
        finalAnimationDuration: 0,
        finalAnimationDelay: 0
    });

    var artifactlink_selector_url = {
        tracker: 1,
        "link-artifact-id": location.href.toQueryParams().aid
    };

    $$(".tracker-form-element-artifactlink-list").each(function(artifact_link) {
        codendi.tracker.artifact.artifactLink.tabs[
            artifact_link.identify()
        ] = new codendi.tracker.artifact.artifactLink.Tab(artifact_link);
    });

    var artifact_links_values = {};

    codendi.tracker.artifact.artifactLink.showReverseArtifactLinks();

    function load_behaviors_in_slow_ways_panel() {
        //links to artifacts load in a new browser tab/window
        $$(
            "#tracker-link-artifact-slow-way-content a.cross-reference",
            "#tracker-link-artifact-slow-way-content a.direct-link-to-artifact",
            "#tracker-link-artifact-slow-way-content a.link-to-user"
        ).each(function(a) {
            a.target = "_blank";
            a.rel = "noreferrer";
        });

        var renderer_panel = $$(".tracker_report_renderer")[0];
        load_behavior_in_renderer_panel(renderer_panel);
        $("tracker_report_renderer_view_controls").hide();

        //links to switch among renderers should load via ajax
        $$(".tracker_report_renderer_tab a").each(function(a) {
            a.observe("click", function(evt) {
                new Ajax.Updater(renderer_panel, a.href, {
                    onComplete: function(transport, json) {
                        a.up("ul")
                            .childElements()
                            .invoke("removeClassName", "tracker_report_renderers-current");
                        a.up("li").addClassName("tracker_report_renderers-current");
                        load_behavior_in_renderer_panel();
                    }
                });
                Event.stop(evt);
                return false;
            });
        });

        $("tracker_select_tracker").observe("change", function() {
            new Ajax.Updater("tracker-link-artifact-slow-way-content", codendi.tracker.base_url, {
                parameters: {
                    tracker: $F("tracker_select_tracker"),
                    "link-artifact-id": $F("link-artifact-id"),
                    "report-only": 1
                },
                method: "GET",
                onComplete: function() {
                    load_behaviors_in_slow_ways_panel();
                }
            });
        });

        if ($("tracker_select_report")) {
            $("tracker_select_report").observe("change", function() {
                new Ajax.Updater(
                    "tracker-link-artifact-slow-way-content",
                    codendi.tracker.base_url,
                    {
                        parameters: {
                            tracker: $F("tracker_select_tracker"),
                            report: $F("tracker_select_report"),
                            "link-artifact-id": $F("link-artifact-id")
                        },
                        method: "GET",
                        onComplete: function() {
                            load_behaviors_in_slow_ways_panel();
                        }
                    }
                );
            });
        }

        codendi.Toggler.init($("tracker_report_query_form"), "hide", "noajax");

        $("tracker_report_query_form").observe("submit", function(evt) {
            $("tracker_report_query_form").request({
                parameters: {
                    aid: 0,
                    "only-renderer": 1,
                    "link-artifact-id": $F("link-artifact-id")
                },
                onSuccess: function(transport, json) {
                    var renderer_panel = $("tracker-link-artifact-slow-way-content")
                        .up()
                        .down(".tracker_report_renderer");
                    renderer_panel.update(transport.responseText);
                    load_behavior_in_renderer_panel(renderer_panel);
                }
            });
            Event.stop(evt);
            return false;
        });
    }

    function force_check(checkbox) {
        var re = new RegExp("(?:^|,)s*" + checkbox.value + "s*(?:,|$)");
        if (
            artifact_links_values[codendi.tracker.artifact.artifactLinker_currentField_id][
                checkbox.value
            ] ||
            $$(
                'input[name="artifact[' +
                    codendi.tracker.artifact.artifactLinker_currentField_id +
                    '][new_values]"]'
            )[0].value.match(re)
        ) {
            checkbox.checked = true;
            checkbox.disabled = true;
        }
    }

    function load_behavior_in_renderer_panel(renderer_panel) {
        codendi.tracker.artifact.editor.disableWarnOnPageLeave();

        $("lightwindow_title_bar_close_link").observe("click", function(evt) {
            window.onbeforeunload = codendi.tracker.artifact.editor.warnOnPageLeave;
        });

        codendi.Tooltip.load(renderer_panel);
        tuleap.dateTimePicker.init();

        //pager links should load via ajax
        $$(".tracker_report_table_pager a").each(function(a) {
            a.observe("click", function(evt) {
                new Ajax.Updater(renderer_panel, a.href, {
                    onComplete: function(transport) {
                        load_behavior_in_renderer_panel(renderer_panel);
                    }
                });
                Event.stop(evt);
                return false;
            });
        });

        var input_to_link = $("lightwindow_contents").down('input[name="link-artifact[manual]"]');
        $("lightwindow_contents")
            .select('input[name^="link-artifact[search]"]')
            .each(function(elem) {
                add_remove_selected(elem, input_to_link);
            });

        //check already linked artifacts in recent panel
        $(renderer_panel)
            .select('input[type=checkbox][name^="link-artifact[search][]"]')
            .each(force_check);

        //check manually added artifact in the renderer
        input_to_link.value.split(",").each(function(link) {
            checked_values_panels(link);
        });

        //try to resize smartly the lightwindow
        var diff = $("lightwindow_contents").scrollWidth - $("lightwindow_contents").offsetWidth;
        if (diff > 0 && document.body.offsetWidth > $("lightwindow_contents").scrollWidth + 40) {
            var previous_left = $("lightwindow").offsetLeft;
            var previous_container_width = $("lightwindow_container").offsetWidth;
            var previous_contents_width = $("lightwindow_contents").offsetWidth;

            $("lightwindow").setStyle({
                left: Math.round($("lightwindow").offsetLeft - diff / 2) + "px"
            });

            $("lightwindow_container").setStyle({
                width: Math.round(previous_container_width + diff + 30) + "px"
            });

            $("lightwindow_contents").setStyle({
                width: Math.round(previous_contents_width + diff + 30) + "px"
            });
        }

        resize_lightwindow.defer();
    }

    function resize_lightwindow() {
        var effective_height = $("lightwindow_contents")
            .childElements()
            .inject(0, function(acc, elem) {
                return acc + (elem.visible() ? elem.getHeight() : 0);
            });
        if (
            effective_height < $("lightwindow_contents").getHeight() ||
            (effective_height > $("lightwindow_contents").getHeight() &&
                effective_height + 100 < document.documentElement.clientHeight)
        ) {
            $("lightwindow_contents").setStyle({
                height: effective_height + 20 + "px"
            });
        }
    }

    function checked_values_panels(artifact_link_id) {
        checked_values_panel_recent(artifact_link_id, true);
        checked_values_panel_search(artifact_link_id, true);
    }

    function checked_values_panel_recent(artifact_link_id, checked) {
        $("lightwindow_contents")
            .select('input[name^="link-artifact[recent]"]')
            .each(function(elem) {
                if (elem.value == artifact_link_id) {
                    elem.checked = checked;
                }
            });
    }

    function checked_values_panel_search(artifact_link_id, checked) {
        $("lightwindow_contents")
            .select('input[name^="link-artifact[search]"]')
            .each(function(elem) {
                if (elem.value == artifact_link_id) {
                    elem.checked = checked;
                }
            });
    }

    function add_remove_selected(elem, input_to_link) {
        elem.observe("change", function(evt) {
            if (elem.checked) {
                if (input_to_link.value) {
                    input_to_link.value += ",";
                }
                input_to_link.value += elem.value;
            } else {
                input_to_link.value = input_to_link.value
                    .split(",")
                    .reject(function(link) {
                        return link.strip() == elem.value;
                    })
                    .join(",");
            }
            if (elem.name == "link-artifact[search][]") {
                checked_values_panel_recent(elem.value, elem.checked);
            }
            if (elem.name == "link-artifact[recent][]") {
                checked_values_panel_search(elem.value, elem.checked);
            }
        });
    }

    //TODO: inject the links 'create' with javascript to prevent bad usage for non javascript users

    // inject the links 'link'
    $$("input.tracker-form-element-artifactlink-new").each(function(input) {
        if (
            location.href.toQueryParams().func == "new-artifact" ||
            location.href.toQueryParams().func == "submit-artifact"
        ) {
            input
                .up()
                .insert(
                    '<br /><em style="color:#666; font-size: 0.9em;">' +
                        codendi.locales.tracker_artifact_link.advanced +
                        "<br />" +
                        input.title +
                        "</em>"
                );
        }

        if (
            location.href.toQueryParams().func != "new-artifact" &&
            location.href.toQueryParams().func != "submit-artifact"
        ) {
            if (!location.href.toQueryParams().modal) {
                var link = new Element("a", {
                    title: codendi.locales.tracker_artifact_link.select
                })
                    .addClassName("tracker-form-element-artifactlink-selector btn")
                    .update('<img src="' + codendi.imgroot + 'ic/clipboard-search-result.png" />');

                var link_create = new Element("a", {
                    title: codendi.locales.tracker_artifact_link.create,
                    href: "#"
                })
                    .addClassName("tracker-form-element-artifactlink-selector btn")
                    .update(
                        '<img src="' +
                            codendi.imgroot +
                            'ic/artifact-plus.png" style="vertical-align: middle;"/> '
                    );
                input
                    .up()
                    .insert(link)
                    .insert(link_create)
                    .up()
                    .insert(
                        '<br /><em style="color:#666; font-size: 0.9em;">' + input.title + "</em>"
                    );
            }
        }

        if (location.href.toQueryParams().modal == 1) {
            input
                .up()
                .insert(
                    '<br /><em style="color:#666; font-size: 0.9em;">' +
                        codendi.locales.tracker_artifact_link.advanced +
                        "<br />" +
                        input.title +
                        "</em>"
                );
        }

        if (!link) {
            return;
        }
        codendi.tracker.artifact.artifactLinker_currentField = link.up(".tracker_artifact_field");
        codendi.tracker.artifact.artifactLinker_currentField_id = input.name.gsub(
            /artifact\[(\d+)\]\[new_values\]/,
            "#{1}"
        );

        //build an array to store the existing links
        if (!artifact_links_values[codendi.tracker.artifact.artifactLinker_currentField_id]) {
            artifact_links_values[codendi.tracker.artifact.artifactLinker_currentField_id] = {};
        }
        link.up(".tracker_artifact_field")
            .select("td input[type=checkbox]")
            .inject(
                artifact_links_values[codendi.tracker.artifact.artifactLinker_currentField_id],
                function(acc, e) {
                    acc[e.name.split("[")[3].gsub("]", "")] = 1;
                    return acc;
                }
            );

        //register behavior when we click on the [create]
        link_create.observe("click", function(evt) {
            //create a new artifact via artifact links
            //tracker='.$tracker_id.'&func=new-artifact-link&id='.$artifact->getId().
            overlay_window.options.afterFinishWindow = function() {};
            overlay_window.activateWindow({
                href:
                    codendi.tracker.base_url +
                    "?" +
                    $H({
                        tracker: codendi.tracker.artifact.artifactLink.selector_url.tracker,
                        func: "new-artifact-link",
                        id: codendi.tracker.artifact.artifactLink.selector_url["link-artifact-id"],
                        modal: 1
                    }).toQueryString(),
                title: link.title,
                iframeEmbed: true
            });

            Event.stop(evt);
            return false;
        });
        //register behavior when we click on the [link]
        link.observe("click", function(evt) {
            $$("button.tracker-form-element-artifactlink-selector");
            overlay_window.options.afterFinishWindow = function() {
                if ($("tracker-link-artifact-fast-ways")) {
                    //Tooltips. load only in fast ways panels
                    // since report table are loaded later in
                    // the function load_behavior_...
                    codendi.Tooltip.load("tracker-link-artifact-fast-ways");

                    load_behaviors_in_slow_ways_panel();

                    //links to artifacts load in a new browser tab/window
                    $$(
                        "#tracker-link-artifact-fast-ways a.cross-reference",
                        "#tracker-link-artifact-fast-ways a.direct-link-to-artifact",
                        "#tracker-link-artifact-fast-ways a.link-to-user"
                    ).each(function(a) {
                        a.target = "_blank";
                        a.rel = "noreferrer";
                    });

                    var input_to_link = $("lightwindow_contents").down(
                        'input[name="link-artifact[manual]"]'
                    );

                    //Checked/unchecked values are added/removed in the manual panel
                    $("lightwindow_contents")
                        .select('input[name^="link-artifact[recent]"]')
                        .each(function(elem) {
                            add_remove_selected(elem, input_to_link);
                        });

                    //Check/Uncheck values in recent and search panels linked to manual panel changes
                    function observe_input_field(evt) {
                        var manual_value = input_to_link.value;
                        var links_array = manual_value.split(",");

                        //unchecked values from recent panel
                        $("lightwindow_contents")
                            .select('input[name^="link-artifact[recent]"]')
                            .each(function(elem) {
                                if (!elem.disabled) {
                                    elem.checked = false;
                                }
                            });

                        //unchecked values from search panel
                        $("lightwindow_contents")
                            .select('input[name^="link-artifact[search]"]')
                            .each(function(elem) {
                                if (!elem.disabled) {
                                    elem.checked = false;
                                }
                            });

                        links_array.each(function(link) {
                            checked_values_panels(link.strip());
                        });
                    }

                    input_to_link.observe("change", observe_input_field);
                    input_to_link.observe("keyup", observe_input_field);

                    //check already linked artifacts in recent panel
                    $$(
                        '#tracker-link-artifact-fast-ways input[type=checkbox][name^="link-artifact[recent][]"]'
                    ).each(force_check);

                    var button = $("lightwindow_contents").down(
                        "button[name=link-artifact-submit]"
                    );
                    button.observe("click", function(evt) {
                        var to_add = [];

                        //manual ones
                        var manual = $("lightwindow_contents").down(
                            'input[name="link-artifact[manual]"]'
                        ).value;
                        if (manual) {
                            to_add.push(manual);
                        }

                        //add to the existing ones
                        if (to_add.size()) {
                            var input_field = codendi.tracker.artifact.artifactLinker_currentField.down(
                                "input.tracker-form-element-artifactlink-new"
                            );
                            if (input_field.value) {
                                input_field.value += ",";
                            }
                            input_field.value += to_add.join(",");
                        }
                        codendi.tracker.artifact.artifactLink.addTemporaryArtifactLinks();

                        //hide the modal window
                        overlay_window.deactivate();

                        //stop the propagation of the event (don't submit any forms)
                        Event.stop(evt);
                        return false;
                    });
                }
            };

            overlay_window.activateWindow({
                href:
                    location.href.split("?")[0] +
                    "?" +
                    $H(codendi.tracker.artifact.artifactLink.selector_url).toQueryString(),
                title: ""
            });
            Event.stop(evt);
            return false;
        });
    });

    $$("a.tracker-form-element-artifactlink-add").each(function(a) {
        a.observe("click", function(evt) {
            evt.preventDefault();
            codendi.tracker.artifact.artifactLink.addTemporaryArtifactLinks();
        });
    });
    codendi.tracker.artifact.artifactLink.set_checkbox_style_as_cross(
        $$(".tracker-form-element-artifactlink-list td.tracker_report_table_unlink")
    );
    codendi.tracker.artifact.artifactLink.addTemporaryArtifactLinks();
    codendi.tracker.artifact.artifactLink.enable_mass_unlink();
});
