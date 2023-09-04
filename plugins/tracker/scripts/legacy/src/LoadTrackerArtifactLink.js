/*
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

/* global
    codendi:readonly
    $:readonly
    $$:readonly
    Ajax:readonly
    lightwindow:readonly
    $F:readonly
    $H:readonly
    tuleap:readonly
*/

document.observe("dom:loaded", function () {
    setTrackerInSelectorUrl();
    codendi.tracker.artifact.artifactLink.showReverseArtifactLinks();
    initTabs();
    checkIfThereAreAsyncRenderersToLoad();

    function setTrackerInSelectorUrl() {
        if ($("tracker_id")) {
            codendi.tracker.artifact.artifactLink.selector_url.tracker = $("tracker_id").value;
        }
    }

    function initTabs() {
        $$(".tracker-form-element-artifactlink-list").each(function (artifact_link) {
            var id = artifact_link.identify();
            codendi.tracker.artifact.artifactLink.tabs[id] =
                new codendi.tracker.artifact.artifactLink.Tab(artifact_link);
        });
    }

    function initExpandCollapseChildrenInArtifactLinkTable() {
        $$(
            "#tracker_report_table_type__is_child > tbody > tr > td.tracker-artifact-rollup-view > a.direct-link-to-artifact",
        ).each(function (link) {
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
            icon.classList.add("tracker-artifact-rollup-view-icon");
            icon.classList.add("fa");
            cell.insertBefore(icon, link);

            loadChildrenRecursively(0);

            function loadChildrenRecursively(offset) {
                new Ajax.Request("/api/artifacts/" + artifact_id + "/linked_artifacts", {
                    method: "GET",
                    requestHeaders: {
                        Accept: "application/json",
                    },
                    parameters: {
                        direction: "forward",
                        nature: "_is_child",
                        offset: offset,
                        limit: limit,
                    },
                    onSuccess: function (transport) {
                        children = children.concat(transport.responseJSON.collection);

                        if (offset + limit < transport.getResponseHeader("X-Pagination-Size")) {
                            loadChildrenRecursively(offset + limit);
                        } else if (children.length > 0) {
                            injectChildrenInTable(children);
                        }
                    },
                });
            }

            function injectChildrenInTable(children_to_inject) {
                icon.classList.add("fa-caret-right");

                icon.addEventListener("click", function () {
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
                    subrows.forEach(function (row) {
                        initRollupViewOfLink(
                            row.querySelector("a.direct-link-to-artifact"),
                            depth + 1,
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
                /* eslint-disable no-multi-str */
                // eslint-disable-next-line no-unsanitized/property
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
                /* eslint-enable no-multi-str */

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
            return '<a href="' + user_json.user_url + '"> ' + user_json.display_name + " </a>";
        }
    }

    function initSelectArtifactsModal() {
        var artifact_links_values = {};

        codendi.tracker.artifact.artifactLink.overlay_window = new lightwindow({
            resizeSpeed: 10,
            delay: 0,
            finalAnimationDuration: 0,
            finalAnimationDelay: 0,
        });

        $$("input.tracker-form-element-artifactlink-new").each(injectLinkAndCreateButtons);

        function load_behaviors_in_slow_ways_panel() {
            //links to artifacts load in a new browser tab/window
            $$(
                "#tracker-link-artifact-slow-way-content a.cross-reference",
                "#tracker-link-artifact-slow-way-content a.direct-link-to-artifact",
                "#tracker-link-artifact-slow-way-content a.link-to-user",
            ).each(function (a) {
                a.target = "_blank";
                a.rel = "noreferrer";
            });

            var renderer_panel = $$(".tracker_report_renderer")[0];
            load_behavior_in_renderer_panel(renderer_panel);
            $("tracker_report_renderer_view_controls").hide();

            //links to switch among renderers should load via ajax
            $$(".tracker_report_renderer_tab a").each(function (a) {
                a.observe("click", function (evt) {
                    new Ajax.Updater(renderer_panel, a.href, {
                        onComplete: function () {
                            a.up("ul")
                                .childElements()
                                .invoke("removeClassName", "tracker_report_renderers-current");
                            a.up("li").addClassName("tracker_report_renderers-current");
                            load_behavior_in_renderer_panel();
                        },
                    });
                    Event.stop(evt);
                    return false;
                });
            });

            $("tracker_select_tracker").observe("change", function () {
                new Ajax.Updater(
                    "tracker-link-artifact-slow-way-content",
                    codendi.tracker.base_url,
                    {
                        parameters: {
                            tracker: $F("tracker_select_tracker"),
                            "link-artifact-id": $F("link-artifact-id"),
                            "report-only": 1,
                        },
                        method: "GET",
                        onComplete: function () {
                            load_behaviors_in_slow_ways_panel();
                        },
                    },
                );
            });

            if ($("tracker_select_report")) {
                $("tracker_select_report").observe("change", function () {
                    new Ajax.Updater(
                        "tracker-link-artifact-slow-way-content",
                        codendi.tracker.base_url,
                        {
                            parameters: {
                                tracker: $F("tracker_select_tracker"),
                                report: $F("tracker_select_report"),
                                "link-artifact-id": $F("link-artifact-id"),
                            },
                            method: "GET",
                            onComplete: function () {
                                load_behaviors_in_slow_ways_panel();
                            },
                        },
                    );
                });
            }

            codendi.Toggler.init($("tracker_report_query_form"), "hide", "noajax");

            $("tracker_report_query_form").observe("submit", async function (evt) {
                Event.stop(evt);

                const url = encodeURI(
                    codendi.tracker.base_url +
                        "?" +
                        new URLSearchParams({
                            tracker: document.getElementById("tracker_select_tracker").value,
                        }).toString(),
                );

                const body = new URLSearchParams($("tracker_report_query_form").serialize());
                body.set("aid", "0");
                body.set("only-renderer", "1");
                body.set("link-artifact-id", document.getElementById("link-artifact-id").value);

                const response = await fetch(url, {
                    method: "POST",
                    headers: {
                        "Content-type": "application/x-www-form-urlencoded",
                    },
                    body: body.toString(),
                });

                const text = await response.text();

                const renderer_panel = $("tracker-link-artifact-slow-way-content")
                    .up()
                    .down(".tracker_report_renderer");
                renderer_panel.update(text);
                load_behavior_in_renderer_panel(renderer_panel);

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
                        '][new_values]"]',
                )[0].value.match(re)
            ) {
                checkbox.checked = true;
                checkbox.disabled = true;
            }
        }

        function load_behavior_in_renderer_panel(renderer_panel) {
            codendi.Tooltip.load(renderer_panel);
            tuleap.dateTimePicker.init();

            //pager links should load via ajax
            $$(".tracker_report_table_pager a").each(function (a) {
                a.observe("click", function (evt) {
                    new Ajax.Updater(renderer_panel, a.href, {
                        onComplete: function () {
                            load_behavior_in_renderer_panel(renderer_panel);
                        },
                    });
                    Event.stop(evt);
                    return false;
                });
            });

            var input_to_link = $("lightwindow_contents").down(
                'input[name="link-artifact[manual]"]',
            );
            $("lightwindow_contents")
                .select('input[name^="link-artifact[search]"]')
                .each(function (elem) {
                    add_remove_selected(elem, input_to_link);
                });

            //check already linked artifacts in recent panel
            $(renderer_panel)
                .select('input[type=checkbox][name^="link-artifact[search][]"]')
                .each(force_check);

            //check manually added artifact in the renderer
            input_to_link.value.split(",").each(function (link) {
                checked_values_panels(link);
            });

            //try to resize smartly the lightwindow
            var diff =
                $("lightwindow_contents").scrollWidth - $("lightwindow_contents").offsetWidth;
            if (
                diff > 0 &&
                document.body.offsetWidth > $("lightwindow_contents").scrollWidth + 40
            ) {
                var previous_container_width = $("lightwindow_container").offsetWidth;
                var previous_contents_width = $("lightwindow_contents").offsetWidth;

                $("lightwindow").setStyle({
                    left: Math.round($("lightwindow").offsetLeft - diff / 2) + "px",
                });

                $("lightwindow_container").setStyle({
                    width: Math.round(previous_container_width + diff + 30) + "px",
                });

                $("lightwindow_contents").setStyle({
                    width: Math.round(previous_contents_width + diff + 30) + "px",
                });
            }

            resize_lightwindow.defer();
        }

        function resize_lightwindow() {
            var effective_height = $("lightwindow_contents")
                .childElements()
                .inject(0, function (acc, elem) {
                    return acc + (elem.visible() ? elem.getHeight() : 0);
                });
            if (
                effective_height < $("lightwindow_contents").getHeight() ||
                (effective_height > $("lightwindow_contents").getHeight() &&
                    effective_height + 100 < document.documentElement.clientHeight)
            ) {
                $("lightwindow_contents").setStyle({
                    height: effective_height + 20 + "px",
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
                .each(function (elem) {
                    if (elem.value == artifact_link_id) {
                        elem.checked = checked;
                    }
                });
        }

        function checked_values_panel_search(artifact_link_id, checked) {
            $("lightwindow_contents")
                .select('input[name^="link-artifact[search]"]')
                .each(function (elem) {
                    if (elem.value == artifact_link_id) {
                        elem.checked = checked;
                    }
                });
        }

        function add_remove_selected(elem, input_to_link) {
            elem.observe("change", function () {
                if (elem.checked) {
                    if (input_to_link.value) {
                        input_to_link.value += ",";
                    }
                    input_to_link.value += elem.value;
                } else {
                    input_to_link.value = input_to_link.value
                        .split(",")
                        .reject(function (link) {
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

        function injectLinkAndCreateButtons(input) {
            if (
                location.href.toQueryParams().func == "new-artifact" ||
                location.href.toQueryParams().func == "submit-artifact"
            ) {
                input
                    .up()
                    .up()
                    .insert(
                        '<br /><em style="color:#666; font-size: 0.9em;">' +
                            codendi.locales.tracker_artifact_link.advanced +
                            "<br />" +
                            input.title +
                            "</em>",
                    );
            }

            if (
                location.href.toQueryParams().func != "new-artifact" &&
                location.href.toQueryParams().func != "submit-artifact"
            ) {
                if (!location.href.toQueryParams().modal) {
                    var link = new Element("a", {
                        title: codendi.locales.tracker_artifact_link.select,
                    })
                        .addClassName("tracker-form-element-artifactlink-selector btn btn-small")
                        .update(
                            '<img src="' + codendi.imgroot + 'ic/clipboard-search-result.png" />',
                        );

                    var link_create = new Element("a", {
                        title: codendi.locales.tracker_artifact_link.create,
                        href: "#",
                    })
                        .addClassName("tracker-form-element-artifactlink-selector btn btn-small")
                        .update(
                            '<img src="' +
                                codendi.imgroot +
                                'ic/artifact-plus.png" style="vertical-align: middle;"/> ',
                        );

                    var preview_button = document.createElement("button");
                    preview_button.classList.add("btn");
                    preview_button.classList.add("btn-small");
                    preview_button.classList.add("tracker-form-element-artifactlink-add");
                    preview_button.type = "button";
                    preview_button.innerText = input.dataset.previewLabel;
                    preview_button.addEventListener("click", function () {
                        codendi.tracker.artifact.artifactLink.addTemporaryArtifactLinks();
                    });

                    var input_append_element = input.up();
                    var container = input_append_element.up();

                    input_append_element.insert(link).insert(link_create);
                    container.appendChild(preview_button);
                    container.insert(
                        '<br /><em style="color:#666; font-size: 0.9em;">' + input.title + "</em>",
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
                            "</em>",
                    );
            }

            if (!link) {
                return;
            }
            codendi.tracker.artifact.artifactLinker_currentField =
                link.up(".tracker_artifact_field");
            codendi.tracker.artifact.artifactLinker_currentField_id = input.name.gsub(
                /artifact\[(\d+)\]\[new_values\]/,
                "#{1}",
            );

            //build an array to store the existing links
            if (!artifact_links_values[codendi.tracker.artifact.artifactLinker_currentField_id]) {
                artifact_links_values[codendi.tracker.artifact.artifactLinker_currentField_id] = {};
            }
            link.up(".tracker_artifact_field")
                .select("td input[type=checkbox]")
                .inject(
                    artifact_links_values[codendi.tracker.artifact.artifactLinker_currentField_id],
                    function (acc, e) {
                        acc[e.name.split("[")[3].gsub("]", "")] = 1;
                        return acc;
                    },
                );

            link_create.observe("click", openCreateArtifactModalOnClickOnCreateButton);
            link.observe("click", openSearchArtifactModalOnClickOnLinkButton);

            function openCreateArtifactModalOnClickOnCreateButton(evt) {
                //create a new artifact via artifact links
                //tracker='.$tracker_id.'&func=new-artifact-link&id='.$artifact->getId().
                codendi.tracker.artifact.artifactLink.overlay_window.options.afterFinishWindow =
                    function () {};
                codendi.tracker.artifact.artifactLink.overlay_window.activateWindow({
                    href:
                        codendi.tracker.base_url +
                        "?" +
                        $H({
                            tracker: codendi.tracker.artifact.artifactLink.selector_url.tracker,
                            func: "new-artifact-link",
                            id: codendi.tracker.artifact.artifactLink.selector_url[
                                "link-artifact-id"
                            ],
                            modal: 1,
                        }).toQueryString(),
                    title: link.title,
                    iframeEmbed: true,
                });

                Event.stop(evt);
                return false;
            }

            function openSearchArtifactModalOnClickOnLinkButton(evt) {
                $$("button.tracker-form-element-artifactlink-selector");
                codendi.tracker.artifact.artifactLink.overlay_window.options.afterFinishWindow =
                    function () {
                        if ($("tracker-link-artifact-fast-ways")) {
                            //Tooltips. load only in fast ways panels
                            // since report table are loaded later in
                            // the function load_behavior_...

                            const panel = document.getElementById(
                                "tracker-link-artifact-fast-ways",
                            );
                            if (panel) {
                                codendi.Tooltip.load(panel);
                            }

                            load_behaviors_in_slow_ways_panel();

                            //links to artifacts load in a new browser tab/window
                            $$(
                                "#tracker-link-artifact-fast-ways a.cross-reference",
                                "#tracker-link-artifact-fast-ways a.direct-link-to-artifact",
                                "#tracker-link-artifact-fast-ways a.link-to-user",
                            ).each(function (a) {
                                a.target = "_blank";
                                a.rel = "noreferrer";
                            });

                            var input_to_link = $("lightwindow_contents").down(
                                'input[name="link-artifact[manual]"]',
                            );

                            //Checked/unchecked values are added/removed in the manual panel
                            $("lightwindow_contents")
                                .select('input[name^="link-artifact[recent]"]')
                                .each(function (elem) {
                                    add_remove_selected(elem, input_to_link);
                                });

                            //Check/Uncheck values in recent and search panels linked to manual panel changes
                            // eslint-disable-next-line no-inner-declarations
                            function observe_input_field() {
                                var manual_value = input_to_link.value;
                                var links_array = manual_value.split(",");

                                //unchecked values from recent panel
                                $("lightwindow_contents")
                                    .select('input[name^="link-artifact[recent]"]')
                                    .each(function (elem) {
                                        if (!elem.disabled) {
                                            elem.checked = false;
                                        }
                                    });

                                //unchecked values from search panel
                                $("lightwindow_contents")
                                    .select('input[name^="link-artifact[search]"]')
                                    .each(function (elem) {
                                        if (!elem.disabled) {
                                            elem.checked = false;
                                        }
                                    });

                                links_array.each(function (link) {
                                    checked_values_panels(link.strip());
                                });
                            }

                            input_to_link.observe("change", observe_input_field);
                            input_to_link.observe("keyup", observe_input_field);

                            //check already linked artifacts in recent panel
                            $$(
                                '#tracker-link-artifact-fast-ways input[type=checkbox][name^="link-artifact[recent][]"]',
                            ).each(force_check);

                            var button = $("lightwindow_contents").down(
                                "button[name=link-artifact-submit]",
                            );
                            button.observe("click", function (evt) {
                                var to_add = [];

                                //manual ones
                                var manual = $("lightwindow_contents").down(
                                    'input[name="link-artifact[manual]"]',
                                ).value;
                                if (manual) {
                                    to_add.push(manual);
                                }

                                //add to the existing ones
                                if (to_add.size()) {
                                    var input_field =
                                        codendi.tracker.artifact.artifactLinker_currentField.down(
                                            "input.tracker-form-element-artifactlink-new",
                                        );
                                    if (input_field.value) {
                                        input_field.value += ",";
                                    }
                                    input_field.value += to_add.join(",");
                                }
                                codendi.tracker.artifact.artifactLink.addTemporaryArtifactLinks();

                                //hide the modal window
                                codendi.tracker.artifact.artifactLink.overlay_window.deactivate();

                                //stop the propagation of the event (don't submit any forms)
                                Event.stop(evt);

                                return false;
                            });
                        }
                    };

                codendi.tracker.artifact.artifactLink.overlay_window.activateWindow({
                    href:
                        location.href.split("?")[0] +
                        "?" +
                        $H(codendi.tracker.artifact.artifactLink.selector_url).toQueryString(),
                    title: "",
                });
                Event.stop(evt);
                return false;
            }
        }
    }

    function init() {
        Object.values(codendi.tracker.artifact.artifactLink.tabs).forEach(function (tab) {
            tab.loadNbArtifacts();
        });

        removeGlobalLoadingIndicator();
        initExpandCollapseChildrenInArtifactLinkTable();
        initSelectArtifactsModal();

        codendi.tracker.artifact.artifactLink.set_checkbox_style_as_cross(
            $$(".tracker-form-element-artifactlink-list td.tracker_report_table_unlink"),
        );
        codendi.tracker.artifact.artifactLink.addTemporaryArtifactLinks();
        codendi.tracker.artifact.artifactLink.enable_mass_unlink();
    }

    function checkIfThereAreAsyncRenderersToLoad() {
        var list_of_renderers_to_load = $$(".tracker-form-element-artifactlink-renderer-async");
        if (list_of_renderers_to_load.size() === 0) {
            init();
        } else {
            addGlobalLoadingIndicator();
            loadAsyncRenderers(list_of_renderers_to_load);
        }
    }

    function addGlobalLoadingIndicator() {
        $$(".tracker-form-element-artifactlink-section").each(function (section) {
            if (!section.querySelector(".tracker-form-element-artifactlink-section-loading")) {
                var spinner = document.createElement("i");
                spinner.classList.add("fa");
                spinner.classList.add("fa-spin");
                spinner.classList.add("fa-spinner");
                spinner.classList.add("tracker-form-element-artifactlink-section-loading-spinner");

                var message = document.createElement("span");
                message.innerText = codendi.getText(
                    "tracker_artifact",
                    "artifactlink_async_loading",
                );

                var section_loading = document.createElement("div");
                section_loading.classList.add("tracker-form-element-artifactlink-section-loading");
                section_loading.classList.add("alert");
                section_loading.classList.add("alert-info");
                section_loading.appendChild(spinner);
                section_loading.appendChild(message);

                section.appendChild(section_loading);
            }
        });
    }

    function removeGlobalLoadingIndicator() {
        $$(".tracker-form-element-artifactlink-section-loading").each(function (section) {
            section.parentNode.removeChild(section);
        });
    }

    function loadAsyncRenderers(list_of_renderers_to_load) {
        var nb_to_fetch = list_of_renderers_to_load.size();

        list_of_renderers_to_load.each(function (renderer_to_load) {
            new Ajax.Updater(renderer_to_load, getUrl(renderer_to_load), {
                onComplete: function () {
                    markRendererAsLoaded(renderer_to_load);
                    lauchInitIfAllRenderersAreLoaded();
                },
            });
        });

        function lauchInitIfAllRenderersAreLoaded() {
            nb_to_fetch--;
            if (nb_to_fetch) {
                return;
            }

            init();
        }

        function markRendererAsLoaded(renderer_to_load) {
            renderer_to_load.classList.add("loaded");
            var evt = document.createEvent("CustomEvent");
            evt.initCustomEvent("renderer-async-loaded", false, false, {
                tracker_panel: renderer_to_load.up(
                    ".tracker-form-element-artifactlink-trackerpanel",
                ),
            });
            document.dispatchEvent(evt);
        }

        function getUrl(renderer_to_load) {
            var field_id = parseInt(renderer_to_load.dataset.fieldId, 10);
            var renderer_data = renderer_to_load.dataset.rendererData;

            return (
                "/plugins/tracker/?func=artifactlink-renderer-async&formElement=" +
                field_id +
                "&renderer_data=" +
                renderer_data
            );
        }
    }
});
