/*
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

import { element } from "angular";
// eslint-disable-next-line you-dont-need-lodash-underscore/map
import { map } from "lodash-es";
import { escaper } from "@tuleap/html-escaper";

export default EditKanbanCtrl;

EditKanbanCtrl.$inject = [
    "$scope",
    "gettextCatalog",
    "KanbanService",
    "ColumnCollectionService",
    "SharedPropertiesService",
    "RestErrorService",
    "FilterTrackerReportService",
    "modal_instance",
    "rebuild_scrollbars",
];

function EditKanbanCtrl(
    $scope,
    gettextCatalog,
    KanbanService,
    ColumnCollectionService,
    SharedPropertiesService,
    RestErrorService,
    FilterTrackerReportService,
    modal_instance,
    rebuild_scrollbars
) {
    const self = this;
    self.kanban = SharedPropertiesService.getKanban();
    self.saving = false;
    self.saved = false;
    self.deleting = false;
    self.confirm_delete = false;
    self.saving_new_column = false;
    self.saving_column = false;
    self.adding_column = false;
    self.deleting_column = false;
    self.new_column_label = "";

    const sanitized_kanban_label = escaper.html(self.kanban.tracker.label);

    const tracker_link = `<a href="/plugins/tracker/?tracker=${self.kanban.tracker.id}">${sanitized_kanban_label}</a>`;

    self.title_tracker_link = gettextCatalog.getString("Based on the {{ trackerLink }} tracker.", {
        trackerLink: tracker_link,
    });

    const info_tracker_link =
        "<a href='/plugins/tracker/?tracker=" +
        self.kanban.tracker.id +
        "'>" +
        sanitized_kanban_label +
        "</a>";

    self.column_config_info = gettextCatalog.getString(
        "More information about columns are available in the field administration used by the semantic status in the {{ trackerLink }} tracker.",
        {
            trackerLink: info_tracker_link,
        }
    );

    self.column_config_cannot_manage_info = gettextCatalog.getString(
        "You can't manage columns of the tracker configuration. More information about columns are available in the field administration used by the semantic status in the {{ trackerLink }} tracker.",
        {
            trackerLink: info_tracker_link,
        }
    );

    self.old_kanban_label = self.kanban.label;
    self.select2_options = {
        placeholder: gettextCatalog.getString("Select tracker reports"),
        allowClear: true,
    };
    self.tracker_reports = [];
    self.selectable_reports = [];

    Object.assign(self, {
        $onInit: init,
        initModalValues,
        initDragular,
        initListenModal,
        dragularOptionsForEditModal,
        processing,
        deleteKanban,
        cancelDeleteKanban,
        saveModifications,
        addColumn,
        cancelAddColumn,
        removeColumn,
        cancelRemoveColumn,
        turnColumnToEditMode,
        cancelEditColumn,
        editColumn,
        columnsCanBeManaged,
        updateWidgetTitle,
        saveReports,
        slugifyLabel,
    });

    function init() {
        self.initModalValues();
        self.initDragular();
        self.initListenModal();
        self.tracker_reports = FilterTrackerReportService.getTrackerReports();
        self.selectable_report_ids = FilterTrackerReportService.getSelectableReports().map(
            ({ id }) => id
        );
    }

    function updateKanbanName(label) {
        KanbanService.updateKanbanName(label);
    }

    function initModalValues() {
        self.kanban.columns.forEach(function (column) {
            column.editing = false;
            column.confirm_delete = false;
        });
    }

    function initDragular() {
        $scope.$on("dragulardrop", dragularDrop);
    }

    function initListenModal() {
        modal_instance.tlp_modal.addEventListener("tlp-modal-hidden", function () {
            if (!self.saved) {
                self.kanban.label = self.old_kanban_label;
                updateWidgetTitle(self.old_kanban_label);
                $scope.$apply();
            }
        });
    }

    function saveReports() {
        self.saving = true;
        KanbanService.updateSelectableReports(self.kanban.id, self.selectable_report_ids).then(
            () => {
                self.saving = false;
                self.saved = true;
                FilterTrackerReportService.changeSelectableReports(self.selectable_report_ids);
            },
            (response) => {
                modal_instance.tlp_modal.hide();
                RestErrorService.reload(response);
            }
        );
    }

    function dragularOptionsForEditModal() {
        return {
            containersModel: self.kanban.columns,
            scope: $scope,
            revertOnSpill: true,
            nameSpace: "dragular-columns",
            moves: isItemDraggable,
        };
    }

    function isItemDraggable(element_to_drag, container, handle_element) {
        return !ancestorCannotBeDragged(handle_element);
    }

    function ancestorCannotBeDragged(handle_element) {
        return (
            element(handle_element).parentsUntil(".column").addBack().filter('[data-nodrag="true"]')
                .length > 0
        );
    }

    function dragularDrop(event) {
        event.stopPropagation();

        var sorted_columns_ids = map(self.kanban.columns, "id");

        KanbanService.reorderColumns(self.kanban.id, sorted_columns_ids).catch(function (response) {
            modal_instance.tlp_modal.hide();
            RestErrorService.reload(response);
        });
    }

    function saveModifications() {
        self.saving = true;
        KanbanService.updateKanbanLabel(self.kanban.id, self.kanban.label).then(
            function () {
                self.saving = false;
                self.saved = true;
                updateKanbanName(self.kanban.label);
            },
            function (response) {
                modal_instance.tlp_modal.hide();
                RestErrorService.reload(response);
            }
        );
    }

    function deleteKanban() {
        if (self.confirm_delete) {
            self.deleting = true;

            KanbanService.deleteKanban(self.kanban.id)
                .then(function () {
                    KanbanService.removeKanban();
                })
                .catch(function (response) {
                    modal_instance.tlp_modal.hide();
                    RestErrorService.reload(response);
                });
        } else {
            self.confirm_delete = true;
        }
    }

    function cancelDeleteKanban() {
        self.confirm_delete = false;
    }

    function processing() {
        return self.deleting || self.saving || self.saving_new_column || self.saving_column;
    }

    function cancelAddColumn() {
        self.new_column_label = "";
        self.adding_column = false;
    }

    function addColumn() {
        if (self.adding_column) {
            self.saving_new_column = true;

            KanbanService.addColumn(self.kanban.id, self.new_column_label).then(
                function (column_representation) {
                    ColumnCollectionService.addColumn(column_representation);

                    self.adding_column = false;
                    self.saving_new_column = false;
                    self.new_column_label = "";

                    rebuild_scrollbars();
                },
                function (response) {
                    modal_instance.tlp_modal.hide();
                    RestErrorService.reload(response);
                }
            );
        } else {
            self.adding_column = true;
            self.new_column_label = "";
        }
    }

    function editColumn(column) {
        self.saving_column = true;

        KanbanService.editColumn(self.kanban.id, column).then(
            function () {
                self.saving_column = false;
                column.editing = false;
                column.original_label = column.label;
            },
            function (response) {
                modal_instance.tlp_modal.hide();
                RestErrorService.reload(response);
            }
        );
    }

    function turnColumnToEditMode(column) {
        column.editing = true;
    }

    function cancelEditColumn(column) {
        self.saving_column = false;
        column.editing = false;
        column.label = column.original_label;
    }

    function removeColumn(column_to_remove) {
        if (column_to_remove.confirm_delete) {
            self.deleting_column = true;
            KanbanService.removeColumn(self.kanban.id, column_to_remove.id).then(
                function () {
                    self.deleting_column = false;
                    ColumnCollectionService.removeColumn(column_to_remove.id);

                    rebuild_scrollbars();
                },
                function (response) {
                    modal_instance.tlp_modal.hide();
                    RestErrorService.reload(response);
                }
            );
        } else {
            column_to_remove.confirm_delete = true;
        }
    }

    function cancelRemoveColumn(column_to_remove) {
        column_to_remove.confirm_delete = false;
    }

    function columnsCanBeManaged() {
        return self.kanban.user_can_reorder_columns && self.kanban.user_can_add_columns;
    }

    function updateWidgetTitle(label) {
        if (SharedPropertiesService.getUserIsOnWidget()) {
            var kanban_widget = element(
                '.dashboard-widget[data-widget-id="' + SharedPropertiesService.getWidgetId() + '"]'
            );
            var kanban_title = kanban_widget.find(".dashboard-widget-header-title");
            kanban_title[0].textContent = label;
        }
    }

    //For testing purpose
    function slugifyLabel(label) {
        return label.replace(/\s/g, "_").toLowerCase();
    }
}
