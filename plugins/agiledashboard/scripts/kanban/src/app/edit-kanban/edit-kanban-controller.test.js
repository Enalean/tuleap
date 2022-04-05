/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import kanban_module from "../app.js";
import angular from "angular";
import "angular-mocks";
import BaseController from "./edit-kanban-controller.js";

describe("EditKanbanController", () => {
    let $q,
        $scope,
        EditKanbanController,
        KanbanService,
        FilterTrackerReportService,
        SharedPropertiesService,
        RestErrorService,
        ColumnCollectionService,
        modal_instance;

    beforeEach(() => {
        angular.mock.module(kanban_module);

        let $controller, $rootScope;

        angular.mock.inject(function (
            _$controller_,
            _$rootScope_,
            _$q_,
            _KanbanService_,
            _FilterTrackerReportService_,
            _SharedPropertiesService_,
            _ColumnCollectionService_,
            _RestErrorService_
        ) {
            $controller = _$controller_;
            $rootScope = _$rootScope_;
            $q = _$q_;
            KanbanService = _KanbanService_;
            FilterTrackerReportService = _FilterTrackerReportService_;
            SharedPropertiesService = _SharedPropertiesService_;
            ColumnCollectionService = _ColumnCollectionService_;
            RestErrorService = _RestErrorService_;
        });

        $scope = $rootScope.$new();

        modal_instance = {
            tlp_modal: { hide: jest.fn() },
        };
        const rebuild_scrollbars = angular.noop;

        jest.spyOn(SharedPropertiesService, "getKanban").mockReturnValue({
            id: 8,
            label: "boxman",
            tracker: {
                id: 48,
                label: "pinkwort",
            },
            columns: [],
        });

        jest.spyOn(RestErrorService, "reload").mockImplementation(() => {});

        EditKanbanController = $controller(BaseController, {
            $scope,
            KanbanService,
            FilterTrackerReportService,
            modal_instance,
            rebuild_scrollbars,
        });
    });

    describe("saveReports() -", () => {
        let selectable_report_ids;

        beforeEach(() => {
            selectable_report_ids = [7, 63];
            Object.assign(EditKanbanController, {
                kanban: { id: 48 },
                selectable_report_ids,
            });
            jest.spyOn(KanbanService, "updateSelectableReports").mockImplementation(() => {});
            jest.spyOn(FilterTrackerReportService, "changeSelectableReports").mockImplementation(
                () => {}
            );
        });

        it("Given that we selected reports to be selectable, then the reports' ids will be saved in backend and will be updated in the dedicated service", () => {
            KanbanService.updateSelectableReports.mockReturnValue($q.when());

            EditKanbanController.saveReports();
            expect(EditKanbanController.saving).toBe(true);
            $scope.$apply();

            expect(EditKanbanController.saving).toBe(false);
            expect(EditKanbanController.saved).toBe(true);
            expect(KanbanService.updateSelectableReports).toHaveBeenCalledWith(
                EditKanbanController.kanban.id,
                selectable_report_ids
            );
            expect(FilterTrackerReportService.changeSelectableReports).toHaveBeenCalledWith(
                selectable_report_ids
            );
        });

        it("when there is a REST error, then the modal will be closed and the error modal will be shown", () => {
            KanbanService.updateSelectableReports.mockReturnValue($q.reject({ status: 500 }));

            EditKanbanController.saveReports();
            $scope.$apply();

            expect(modal_instance.tlp_modal.hide).toHaveBeenCalled();
            expect(RestErrorService.reload).toHaveBeenCalled();
        });
    });

    describe(`addColumn`, () => {
        it(`when I click on "Add a column", it will set a flag and reset the new column label`, () => {
            EditKanbanController.addColumn();

            expect(EditKanbanController.adding_column).toBe(true);
            expect(EditKanbanController.new_column_label).toBe("");
        });

        it(`when I click a second time after writing a new column label,
            it will send a request with the new column label
            and will add the column representation to the list of columns
            and reset the flags and new column label`, () => {
            const column_representation = { id: 78, label: "Testing", is_open: true };
            const createInBackend = jest
                .spyOn(KanbanService, "addColumn")
                .mockReturnValue($q.when(column_representation));
            const addColumnToCollection = jest.spyOn(ColumnCollectionService, "addColumn");

            EditKanbanController.addColumn();
            EditKanbanController.new_column_label = "Testing";
            EditKanbanController.addColumn();
            expect(EditKanbanController.saving_new_column).toBe(true);
            $scope.$apply();

            expect(createInBackend).toHaveBeenCalledWith(8, "Testing");
            expect(addColumnToCollection).toHaveBeenCalledWith(column_representation);
            expect(EditKanbanController.adding_column).toBe(false);
            expect(EditKanbanController.saving_new_column).toBe(false);
            expect(EditKanbanController.new_column_label).toBe("");
        });

        it(`when there is a problem with the request,
            it will close the modal and delegate to RestErrorService`, () => {
            const response = { status: 500 };
            jest.spyOn(KanbanService, "addColumn").mockReturnValue($q.reject(response));

            EditKanbanController.addColumn();
            EditKanbanController.new_column_label = "Testing";
            EditKanbanController.addColumn();
            $scope.$apply();

            expect(modal_instance.tlp_modal.hide).toHaveBeenCalled();
            expect(RestErrorService.reload).toHaveBeenCalledWith(response);
        });
    });
});
