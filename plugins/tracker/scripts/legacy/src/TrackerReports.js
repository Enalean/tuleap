/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Originally written by Nicolas Terray, 2009
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

/* global
    Ajax:readonly
    $:readonly
    Class:readonly
    $$:readonly
    Class:readonly
    Prototype:readonly
    TableKit:readonly
    Sortable:readonly
*/

var codendi = codendi || {};
codendi.tracker = codendi.tracker || {};
codendi.tracker.report = codendi.tracker.report || {};

var tuleap = tuleap || {};
tuleap.tracker = tuleap.tracker || {};
tuleap.tracker.report = tuleap.tracker.report || {};

codendi.tracker.report.setHasChanged = function () {
    var save_or_revert = $("tracker_report_save_or_revert");
    save_or_revert.setStyle("display: inline;");
    if (
        !save_or_revert.hasClassName("tracker_report_haschanged") &&
        !save_or_revert.hasClassName("tracker_report_haschanged_and_isobsolete")
    ) {
        if (save_or_revert.hasClassName("tracker_report_isobsolete")) {
            save_or_revert.removeClassName("tracker_report_isobsolete");
            save_or_revert.addClassName("tracker_report_haschanged_and_isobsolete");
        } else {
            save_or_revert.addClassName("tracker_report_haschanged");
        }
    }
};
Ajax.Responders.register({
    onComplete: function (response) {
        if (response.getHeader("X-Codendi-Tracker-Report-IsObsolete")) {
            var save_or_revert = $("tracker_report_save_or_revert");
            if (save_or_revert) {
                $$(".tracker_report_updated_by").invoke(
                    "update",
                    response.getHeader("X-Codendi-Tracker-Report-IsObsolete"),
                );
                if (
                    !save_or_revert.hasClassName("tracker_report_isobsolete") &&
                    !save_or_revert.hasClassName("tracker_report_haschanged_and_isobsolete")
                ) {
                    if (save_or_revert.hasClassName("tracker_report_haschanged")) {
                        save_or_revert.removeClassName("tracker_report_haschanged");
                        save_or_revert.addClassName("tracker_report_haschanged_and_isobsolete");
                    } else {
                        save_or_revert.addClassName("tracker_report_isobsolete");
                    }
                }
            }
        }
    },
});

codendi.tracker.report.table = codendi.tracker.report.table || {};

codendi.tracker.report.table.saveColumnsWidth = function (table, onComplete) {
    var total = table.offsetWidth - 16;
    var parameters = {
        func: "renderer",
        renderer: $("tracker_report_renderer_current").readAttribute("data-renderer-id"),
    };
    var cells = table.rows[0].cells;
    var n = cells.length;
    for (var i = 1; i < n; i++) {
        var id = cells[i].readAttribute("data-column-id");
        if (id) {
            parameters["renderer_table[resize-column][" + id + "]"] = Math.round(
                (cells[i].offsetWidth * 100) / total,
            );
        }
    }
    var onCompleteFunction = onComplete || Prototype.emptyFunction;
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    var req = new Ajax.Request(location.href, {
        parameters: parameters,
        onComplete: function () {
            onCompleteFunction();
            codendi.tracker.report.setHasChanged();
        },
    });
};

tuleap.tracker.report.FieldsDropDown = Class.create({
    /**
     * Constructor
     */
    initialize: function (selectbox, actions) {
        this.actions = actions;
        selectbox.select("li").each(
            function (criterion) {
                criterion.observe(
                    "click",
                    function (event) {
                        this.toggle(criterion);
                        event.stop();
                    }.bind(this),
                );
            }.bind(this),
        );
    },
    /**
     * event listener to toggle a column
     */
    toggle: function (li) {
        if (li.readAttribute("data-field-is-used") === "0") {
            this.actions.add(this, li);
        } else {
            this.actions.remove(this, li);
        }
    },
    /**
     * Set the class name of the li to used and clear waiting
     */
    setUsed: function (li) {
        li.writeAttribute("data-field-is-used", "1");
        li.select("a").each(function (element) {
            element.insert({ top: new Element("i", { class: "fa fa-check" }) });
        });
    },
    /**
     * Set the class name of the li to unused and clear waiting
     */
    setUnused: function (li) {
        li.writeAttribute("data-field-is-used", "0");
        li.select("i").each(function (element) {
            element.remove();
        });
    },
});

codendi.tracker.report.table.AddRemoveColumn = Class.create({
    /**
     * Add a column to the table
     */
    add: function (dropdown, li) {
        var column_id = li.readAttribute("data-column-id"),
            field_id = li.readAttribute("data-field-id"),
            artlink_type = li.readAttribute("data-field-artlink-type"),
            matching_columns = $$("th[data-column-id=" + column_id + "]");

        if (matching_columns.length > 0) {
            matching_columns.invoke("show");
            $$("td[data-column-id=" + column_id + "]").invoke("show");

            codendi.tracker.report.table.saveColumnsWidth($("tracker_report_table"));

            dropdown.setUsed(li);
        } else {
            var url =
                    codendi.tracker.base_url +
                    "?report=" +
                    $("tracker-report-normal-query").readAttribute("data-report-id") +
                    "&renderer=" +
                    $("tracker_report_renderer_current").readAttribute("data-renderer-id"),
                parameters = {
                    func: "renderer",
                    renderer: $("tracker_report_renderer_current").readAttribute(
                        "data-renderer-id",
                    ),
                    "renderer_table[add-column][field-id]": field_id,
                };
            if (artlink_type !== null) {
                parameters["renderer_table[add-column][artlink-type]"] = artlink_type;
            }

            //eslint-disable-next-line @typescript-eslint/no-unused-vars
            var req = new Ajax.Request(url, {
                parameters: parameters,
                onSuccess: function onSuccessAddColumn(transport) {
                    var div = new Element("div").update(transport.responseText);
                    var new_column = div.down("thead").down("th");
                    $("tracker_report_table")
                        .down("thead")
                        .down("tr")
                        .insert({ bottom: new_column });
                    var new_trs = div.down("tbody").childElements().reverse();
                    $$("#tracker_report_table > tbody > tr").each(function (tr) {
                        if (!tr.hasClassName("tracker_report_table_no_result")) {
                            tr.insert(new_trs.pop().down("td"));
                        }
                    });

                    $$("tr.tracker_report_table_no_result td").each(function (td) {
                        td.colSpan = td.up("table").rows[0].cells.length;
                    });

                    codendi.tracker.report.table.saveColumnsWidth($("tracker_report_table"));

                    //Remove entry from the selectbox
                    dropdown.setUsed(li);

                    //eval scripts now (prototype defer scripts eval but we need them now for decorators)
                    //transport.responseText.evalScripts();

                    codendi.tracker.report.setHasChanged();

                    tuleap.trackers.report.table.fixAggregatesHeights();

                    //reorder
                    codendi.reorder_columns[$("tracker_report_table").identify()].register(
                        new_column,
                    );

                    //resize
                    TableKit.reload();

                    if (artlink_type !== null) {
                        codendi.tracker.report.table.initTypeColumnEditor(
                            new_column.down(".type-column-editor"),
                        );
                    }
                },
            });
        }
    },
    /**
     * remove a column to the table
     */
    remove: function (dropdown, li) {
        var column_id = li.readAttribute("data-column-id"),
            field_id = li.readAttribute("data-field-id");
        if ($("tracker_report_table_sort_by_" + field_id)) {
            //If the column is used to sort, we need to refresh all the page
            //Because we need to resort all the table
            // but before, save the new size of the remaining columns
            var col = $("tracker_report_table_column_" + field_id);
            if (col.nextSiblings()[0]) {
                col.nextSiblings()[0].setStyle({
                    width: col.nextSiblings()[0].offsetWidth + col.offsetWidth + "px",
                });
            } else if (col.previousSiblings()[0].id) {
                col.previousSiblings()[0].setStyle({
                    width: col.previousSiblings()[0].offsetWidth + col.offsetWidth + "px",
                });
            }
            col.hide();
            $$(".tracker_report_table_column_" + field_id).invoke("hide");

            codendi.tracker.report.table.saveColumnsWidth($("tracker_report_table"), function () {
                location.href =
                    location.href +
                    "&func=renderer" +
                    "&renderer=" +
                    $("tracker_report_renderer_current").readAttribute("data-renderer-id") +
                    "&renderer_table[remove-column]=" +
                    column_id;
            });
        } else {
            //eslint-disable-next-line @typescript-eslint/no-unused-vars
            var req = new Ajax.Request(
                codendi.tracker.base_url +
                    "?report=" +
                    $("tracker-report-normal-query").readAttribute("data-report-id") +
                    "&renderer=" +
                    $("tracker_report_renderer_current").readAttribute("data-renderer-id"),
                {
                    parameters: {
                        func: "renderer",
                        renderer: $("tracker_report_renderer_current").readAttribute(
                            "data-renderer-id",
                        ),
                        "renderer_table[remove-column]": column_id,
                    },
                    onSuccess: function () {
                        $$("th[data-column-id=" + column_id + "]").each(function (col) {
                            if (col.nextSiblings()[0]) {
                                col.nextSiblings()[0].setStyle({
                                    width:
                                        col.nextSiblings()[0].offsetWidth + col.offsetWidth + "px",
                                });
                            } else if (col.previousSiblings()[0].id) {
                                col.previousSiblings()[0].setStyle({
                                    width:
                                        col.previousSiblings()[0].offsetWidth +
                                        col.offsetWidth +
                                        "px",
                                });
                            }
                            col.hide();
                        });
                        $$("td[data-column-id=" + column_id + "]").invoke("hide");

                        codendi.tracker.report.table.saveColumnsWidth($("tracker_report_table"));

                        tuleap.trackers.report.table.fixAggregatesHeights();

                        dropdown.setUnused(li);
                        codendi.tracker.report.setHasChanged();
                    },
                },
            );
        }
    },
});

codendi.tracker.report.AddRemoveCriteria = Class.create({
    initialize: function () {
        this.request_sent = false;
    },
    /**
     * Add a column to the table: criterion
     */
    add: function (dropdown, li) {
        var self = this;

        if (!this.request_sent) {
            this.request_sent = true;
            var field_id = li.readAttribute("data-field-id");
            if ($("tracker_report_crit_" + field_id)) {
                $$(".tracker_report_crit_" + field_id).invoke("show");
                $("tracker_report_crit_" + field_id).show();
                dropdown.setUsed(li);
                codendi.tracker.report.setHasChanged();
                new Ajax.Request(location.href, {
                    parameters: {
                        func: "add-criteria",
                        field: field_id,
                    },
                    onComplete: function () {
                        self.request_sent = false;
                    },
                });
            } else {
                new Ajax.Request(location.href, {
                    parameters: {
                        func: "add-criteria",
                        field: field_id,
                    },
                    onComplete: function () {
                        self.request_sent = false;
                    },
                    onSuccess: function (transport) {
                        var crit = new Element("li", {
                            id: "tracker_report_crit_" + field_id,
                        }).update(transport.responseText);
                        $("tracker_query").insert(crit);

                        //Remove entry from the selectbox
                        dropdown.setUsed(li);

                        codendi.tracker.report.setHasChanged();

                        //eval scripts now (prototype defer scripts eval but we need them now for decorators)
                        transport.responseText.evalScripts();

                        //initialize events and other dynamic stuffs
                        codendi.tracker.report.loadAdvancedCriteria(
                            crit.down("img.tracker_report_criteria_advanced_toggle"),
                        );

                        tuleap.dateTimePicker.init();
                    },
                });
            }
        }
    },
    /**
     * remove a criteria
     */
    remove: function (dropdown, li) {
        var field_id = li.readAttribute("data-field-id");
        new Ajax.Request(location.href, {
            parameters: {
                func: "remove-criteria",
                field: field_id,
            },
            onSuccess: function () {
                $$(".tracker_report_crit_" + field_id).invoke("hide");
                $("tracker_report_crit_" + field_id).hide();
                dropdown.setUnused(li);

                codendi.tracker.report.setHasChanged();
            },
        });
    },
});

// Advanced criteria
codendi.tracker.report.loadAdvancedCriteria = function (element) {
    if (element) {
        var li = element.up("li");
        element.observe("click", function (evt) {
            if (/toggle_plus.png$/.test(element.src)) {
                //switch to advanced
                element.src = element.src.gsub("toggle_plus.png", "toggle_minus.png");
            } else {
                //toggle off advanced
                element.src = element.src.gsub("toggle_minus.png", "toggle_plus.png");
            }
            var field_id = element
                .up("td")
                .next()
                .down("label")
                .htmlFor.match(/_(\d+)$/)[1];
            //eslint-disable-next-line @typescript-eslint/no-unused-vars
            var req = new Ajax.Updater(li, location.href, {
                parameters: {
                    func: "toggle-advanced",
                    field: field_id,
                },
                onComplete: function (transport) {
                    //Force refresh of decorators and calendar
                    li.select("input", "select").each(function (el) {
                        if (el.id && $("fd-" + el.id)) {
                            delete $("fd-" + el.id).remove();
                            //delete datePickerController.datePickers[el.id];
                        }
                    });

                    codendi.tracker.report.setHasChanged();

                    //eval scripts now (prototype defer scripts eval but we need them now for decorators)
                    transport.responseText.evalScripts();

                    //initialize events and other dynamic stuffs
                    codendi.tracker.report.loadAdvancedCriteria(
                        li.down("img.tracker_report_criteria_advanced_toggle"),
                    );
                    tuleap.dateTimePicker.init();
                },
            });
            Event.stop(evt);
            return false;
        });
    }
};

document.observe("dom:loaded", function () {
    if ($("tracker_query")) {
        $$("img.tracker_report_criteria_advanced_toggle").map(
            codendi.tracker.report.loadAdvancedCriteria,
        );

        //User add criteria
        if ($("tracker_report_add_criteria_dropdown")) {
            new tuleap.tracker.report.FieldsDropDown(
                $("tracker_report_add_criteria_dropdown"),
                new codendi.tracker.report.AddRemoveCriteria(),
            );
        }

        //User add/remove column
        if ($("tracker_report_add_columns_dropdown")) {
            new tuleap.tracker.report.FieldsDropDown(
                $("tracker_report_add_columns_dropdown"),
                new codendi.tracker.report.table.AddRemoveColumn(),
            );
        }

        // Masschange
        var button = $$('input[name="renderer_table[masschange_all]"]')[0];
        var mc_panel = $("tracker_report_table_masschange_panel");
        var mc_all_form = $("tracker_report_table_masschange_form");
        if (button) {
            mc_panel
                .up(".tracker_report_renderer")
                .addClassName("tracker_report_table_hide_masschange");
            mc_panel
                .up(".tracker_report_renderer")
                .removeClassName("tracker_report_table_show_masschange");
            $$(".tracker_report_table_masschange").invoke("hide");
            mc_panel
                .insert({
                    top: new Element("br"),
                })
                .insert({
                    top: new Element("a", {
                        href: "#uncheck-all",
                    })
                        .observe("click", function (evt) {
                            $$(".tracker_report_table_masschange input[type=checkbox]").each(
                                function (cb) {
                                    cb.checked = false;
                                },
                            );
                            Event.stop(evt);
                        })
                        .update(codendi.locales.tracker_artifact.masschange_uncheck_all),
                })
                .insert({
                    top: new Element("span").update("&nbsp;|&nbsp;"),
                })
                .insert({
                    top: new Element("a", {
                        href: "#check-all",
                    })
                        .observe("click", function (evt) {
                            $$(".tracker_report_table_masschange input[type=checkbox]").each(
                                function (cb) {
                                    cb.checked = true;
                                },
                            );
                            Event.stop(evt);
                        })
                        .update(codendi.locales.tracker_artifact.masschange_check_all),
                });
            //get checked artifacts
            $("masschange_btn_checked").observe("click", function () {
                $$('input[name="masschange_aids[]"]').each(function (e) {
                    if ($(e).checked) {
                        mc_all_form.appendChild(
                            new Element("input", {
                                type: "hidden",
                                name: "masschange_aids[]",
                                value: $(e).value,
                            }),
                        );
                    }
                });
                //$('masschange_form').;
                //mc_all_form.submit();
            });

            if (location.href.match(/#masschange$/)) {
                mc_panel
                    .up(".tracker_report_renderer")
                    .toggleClassName("tracker_report_table_hide_masschange");
                mc_panel
                    .up(".tracker_report_renderer")
                    .toggleClassName("tracker_report_table_show_masschange");
                $$(".tracker_report_table_masschange").invoke("show");
            } else {
                var masschange_button = new Element("div", { className: "btn-group" }).update(
                    new Element("a", {
                        href: "#masschange",
                    })
                        .addClassName("btn btn-mini")
                        .observe("click", function (evt) {
                            $$(".tracker_report_table_masschange").invoke("show");
                            mc_panel
                                .up(".tracker_report_renderer")
                                .toggleClassName("tracker_report_table_hide_masschange");
                            mc_panel
                                .up(".tracker_report_renderer")
                                .toggleClassName("tracker_report_table_show_masschange");
                            if (
                                mc_panel
                                    .up(".tracker_report_renderer")
                                    .hasClassName("tracker_report_table_show_masschange")
                            ) {
                                Element.scrollTo(mc_panel);
                            }
                            Event.stop(evt);
                        })
                        .update(
                            '<i class="fas fa-pencil-alt"></i> ' +
                                codendi.locales.tracker_artifact.masschange,
                        ),
                );

                $("tracker_renderer_options").insert({ top: " " });
                $("tracker_renderer_options").insert({ top: masschange_button });
            }
        }

        //Export
        if ($("tracker_report_table_export_panel") && !location.href.match(/#export$/)) {
            var export_panel = $("tracker_report_table_export_panel");
            export_panel.childElements().invoke("hide");
            export_panel.up("form").insert({
                before: new Element("a", {
                    href: "#export",
                })
                    .observe("click", function (evt) {
                        export_panel.childElements().invoke("toggle");
                        Event.stop(evt);
                    })
                    .update(
                        '<img src="' +
                            codendi.imgroot +
                            'ic/clipboard-paste.png" style="vertical-align:top" /> export',
                    )
                    .setStyle({
                        marginLeft: "1em",
                    }),
            });
        }

        if (TableKit) {
            TableKit.options.observers.onResizeEnd = function (table) {
                if (
                    TableKit.Resizable._cell &&
                    TableKit.Resizable._cell.readAttribute("data-column-id")
                ) {
                    codendi.tracker.report.table.saveColumnsWidth(table);
                    tuleap.trackers.report.table.fixAggregatesHeights();
                }
            };
        }
    }

    if ($("tracker_select_report")) {
        $("tracker_select_report").observe("change", function () {
            this.form.submit();
        });
    }
    if ($("tracker_report_query_form")) {
        var report_id = $("tracker-report-normal-query").readAttribute("data-report-id");
        var renderer_id = $("tracker_report_renderer_current").readAttribute("data-renderer-id");

        if ($("tracker_report_renderers")) {
            var renderers_sorting = false;
            Sortable.create("tracker_report_renderers", {
                constraint: "horizontal",
                only: "tracker_report_renderer_tab",
                onUpdate: function (container) {
                    renderers_sorting = true;
                    var parameters =
                        Sortable.serialize(container) +
                        "&func=move-renderer&report=" +
                        report_id +
                        "&renderer=" +
                        renderer_id;

                    //eslint-disable-next-line @typescript-eslint/no-unused-vars
                    var req = new Ajax.Request(location.href, {
                        parameters: parameters,
                        onComplete: function () {
                            codendi.tracker.report.setHasChanged();
                        },
                    });
                },
            });
            $$("#tracker_report_renderers li a").each(function (a) {
                a.observe("click", function (evt) {
                    if (renderers_sorting) {
                        evt.stop();
                        renderers_sorting = false;
                    }
                });
            });
        }
    }

    if ($("tracker_report_updater_delete")) {
        $("tracker_report_updater_delete").observe("click", function (event) {
            //eslint-disable-next-line no-alert
            if (!confirm(codendi.locales.tracker_report.delete_report)) {
                event.stop();
            }
        });
    }

    if ($("tracker_renderer_add_handle")) {
        $("tracker_renderer_add_handle").observe("click", function () {
            if ($("tracker_renderer_add_type")) {
                $("tracker_renderer_add_type").observe("click", function (event_add_type) {
                    event_add_type.stopPropagation();
                });
            }
        });
    }
});
