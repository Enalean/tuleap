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

import planning_module from "../app.js";
import angular from "angular";
import "angular-mocks";

import BaseBacklogController from "./backlog-controller.js";
import BacklogFilterValue from "../backlog-filter-terms.js";
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";

function createElement(tag_name, class_name) {
    const local_document = document.implementation.createHTMLDocument();
    const element = local_document.createElement(tag_name);
    if (!class_name) {
        return element;
    }
    element.classList.add(class_name);
    return element;
}

describe("BacklogController -", () => {
    let $q,
        $scope,
        $document,
        $controller,
        wrapPromise,
        dragularService,
        BacklogController,
        BacklogService,
        MilestoneService,
        BacklogItemService,
        DroppedService,
        MilestoneCollectionService,
        BacklogItemSelectedService,
        BacklogItemCollectionService,
        ProjectService,
        SharedPropertiesService,
        NewTuleapArtifactModalService,
        ItemAnimatorService;

    const user_id = 102;

    beforeEach(() => {
        angular.mock.module(planning_module);

        let $rootScope;
        angular.mock.inject(function (
            _$q_,
            _$document_,
            _$rootScope_,
            _$controller_,
            _dragularService_,
            _BacklogService_,
            _MilestoneService_,
            _BacklogItemService_,
            _BacklogItemCollectionService_,
            _ProjectService_,
            _DroppedService_,
            _MilestoneCollectionService_,
            _BacklogItemSelectedService_,
            _SharedPropertiesService_,
            _NewTuleapArtifactModalService_,
            _ItemAnimatorService_
        ) {
            $q = _$q_;
            $document = _$document_;
            $rootScope = _$rootScope_;
            dragularService = _dragularService_;

            var returnPromise = function (method) {
                var self = this;
                jest.spyOn(self, method).mockReturnValue($q.defer().promise);
            };

            BacklogService = _BacklogService_;
            jest.spyOn(BacklogService, "removeBacklogItemsFromBacklog").mockImplementation(
                () => {}
            );
            jest.spyOn(BacklogService, "appendBacklogItems").mockImplementation(() => {});
            jest.spyOn(BacklogService, "filterItems").mockImplementation(() => {});
            jest.spyOn(BacklogService, "loadProjectBacklog").mockImplementation(() => {});
            jest.spyOn(BacklogService, "loadMilestoneBacklog").mockImplementation(() => {});
            jest.spyOn(BacklogService, "addOrReorderBacklogItemsInBacklog").mockImplementation(
                () => {}
            );

            MilestoneService = _MilestoneService_;
            [
                "addReorderToContent",
                "addToContent",
                "augmentMilestone",
                "defineAllowedBacklogItemTypes",
                "getMilestone",
                "removeAddReorderToBacklog",
                "removeAddToBacklog",
                "updateInitialEffort",
            ].forEach(returnPromise, MilestoneService);

            BacklogItemService = _BacklogItemService_;
            [
                "getBacklogItemChildren",
                "getMilestoneBacklogItems",
                "getProjectBacklogItems",
                "getBacklogItem",
                "removeAddBacklogItemChildren",
            ].forEach(returnPromise, BacklogItemService);

            BacklogItemCollectionService = _BacklogItemCollectionService_;
            jest.spyOn(BacklogItemCollectionService, "refreshBacklogItem").mockImplementation(
                () => {}
            );

            ProjectService = _ProjectService_;
            [
                "getProjectBacklog",
                "getProject",
                "removeAddToBacklog",
                "removeAddReorderToBacklog",
            ].forEach(returnPromise, ProjectService);

            DroppedService = _DroppedService_;
            jest.spyOn(DroppedService, "moveFromBacklogToSubmilestone").mockImplementation(
                () => {}
            );
            jest.spyOn(DroppedService, "defineComparedToBeFirstItem");
            jest.spyOn(DroppedService, "defineComparedToBeLastItem");
            jest.spyOn(DroppedService, "reorderBacklog").mockImplementation(() => {});

            MilestoneCollectionService = _MilestoneCollectionService_;
            jest.spyOn(MilestoneCollectionService, "refreshMilestone").mockImplementation(() => {});
            jest.spyOn(
                MilestoneCollectionService,
                "removeBacklogItemsFromMilestoneContent"
            ).mockImplementation(() => {});
            jest.spyOn(
                MilestoneCollectionService,
                "addOrReorderBacklogItemsInMilestoneContent"
            ).mockImplementation(() => {});

            BacklogItemSelectedService = _BacklogItemSelectedService_;
            jest.spyOn(
                BacklogItemSelectedService,
                "areThereMultipleSelectedBaklogItems"
            ).mockImplementation(() => {});
            jest.spyOn(
                BacklogItemSelectedService,
                "getCompactedSelectedBacklogItem"
            ).mockImplementation(() => {});

            SharedPropertiesService = _SharedPropertiesService_;
            jest.spyOn(SharedPropertiesService, "getProjectId").mockReturnValue(736);
            jest.spyOn(SharedPropertiesService, "getMilestoneId").mockReturnValue(592);
            jest.spyOn(SharedPropertiesService, "getUserId").mockReturnValue(user_id);

            NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
            jest.spyOn(NewTuleapArtifactModalService, "showCreation").mockImplementation(() => {});
            jest.spyOn(NewTuleapArtifactModalService, "showEdition").mockImplementation(() => {});

            ItemAnimatorService = _ItemAnimatorService_;
            $controller = _$controller_;
        });
        $scope = $rootScope.$new();
        wrapPromise = createAngularPromiseWrapper($rootScope);

        jest.spyOn(ItemAnimatorService, "animateCreated").mockImplementation(() => {});

        BacklogFilterValue.terms = "";

        BacklogController = $controller(BaseBacklogController, {
            $q,
            $scope,
            $document,
            dragularService,
            BacklogService,
            MilestoneService,
            BacklogItemService,
            ProjectService,
            BacklogItemCollectionService,
            DroppedService,
            MilestoneCollectionService,
            BacklogItemSelectedService,
            SharedPropertiesService,
            NewTuleapArtifactModalService,
            ItemAnimatorService,
        });
        BacklogController.$onInit();
    });

    describe("$onInit() -", function () {
        describe("Given we are in a backlog context", function () {
            it("When I load the controller, then the project's backlog will be retrieved and the backlog updated", function () {
                BacklogController.milestone_id = undefined;
                BacklogController.$onInit();

                expect(BacklogService.loadProjectBacklog).toHaveBeenCalledWith(736);
            });
        });

        describe("Given we are in a milestone context", function () {
            it("will retrieve the milestone", () => {
                const milestone = {
                    id: 592,
                    resources: {
                        backlog: { accept: { trackers: [{ id: 99, label: "story" }] } },
                        content: { accept: { trackers: [{ id: 99, label: "story" }] } },
                    },
                    sub_milestone_type: { id: 66, label: "sprints" },
                };

                MilestoneService.getMilestone.mockReturnValue($q.when({ results: milestone }));
                jest.spyOn(BacklogController, "loadBacklog");

                BacklogController.$onInit();
                $scope.$apply();

                expect(BacklogService.loadMilestoneBacklog).toHaveBeenCalledWith(milestone);
            });
        });
    });

    describe("displayBacklogItems() -", function () {
        var fetch_backlog_items_request;

        beforeEach(function () {
            fetch_backlog_items_request = $q.defer();
            jest.spyOn(BacklogController, "fetchBacklogItems").mockReturnValue(
                fetch_backlog_items_request.promise
            );
            BacklogController.backlog_items = {
                loading: false,
                fully_loaded: false,
                pagination: { limit: 50, offset: 50 },
            };
        });

        it(`Given that we aren't already loading backlog_items
            and all backlog_items have not yet been loaded,
            when I display the backlog items,
            then the REST route will be called
            and a promise will be resolved`, () => {
            const promise = BacklogController.displayBacklogItems();
            fetch_backlog_items_request.resolve(86);

            expect(BacklogController.fetchBacklogItems).toHaveBeenCalledWith(50, 50);
            return wrapPromise(promise);
        });

        it(`Given that we were already loading backlog_items,
            when I display the backlog items
            then the REST route won't be called again
            and a promise will be resolved`, () => {
            BacklogController.backlog_items.loading = true;

            const promise = BacklogController.displayBacklogItems();

            expect(BacklogController.fetchBacklogItems).not.toHaveBeenCalled();
            return wrapPromise(promise);
        });

        it(`Given that all the backlog_items had been loaded,
            when I display the backlog items,
            then the REST route won't be called again
            and a promise will be resolved`, () => {
            BacklogController.backlog_items.fully_loaded = true;

            const promise = BacklogController.displayBacklogItems();

            expect(BacklogController.fetchBacklogItems).not.toHaveBeenCalled();
            return wrapPromise(promise);
        });
    });

    describe("fetchAllBacklogItems() -", function () {
        var fetch_backlog_items_request;

        beforeEach(function () {
            fetch_backlog_items_request = $q.defer();
            jest.spyOn(BacklogController, "fetchBacklogItems").mockReturnValue(
                fetch_backlog_items_request.promise
            );
            BacklogController.backlog_items = {
                loading: false,
                fully_loaded: false,
            };
        });

        it(`Given that we aren't already loading backlog_items
            and all backlog_items have not yet been loaded,
            when I fetch all the backlog items,
            then the REST route will be called
            and a promise will be resolved`, () => {
            const promise = BacklogController.fetchAllBacklogItems(50, 50);
            fetch_backlog_items_request.resolve(40);

            expect(BacklogController.fetchBacklogItems).toHaveBeenCalledWith(50, 50);
            return wrapPromise(promise);
        });

        it(`Given that there were more items than the current offset and limit,
            when I fetch all the backlog items,
            then the REST route will be called twice
            and a promise will be resolved`, async () => {
            const promise = BacklogController.fetchAllBacklogItems(50, 50);
            fetch_backlog_items_request.resolve(134);

            await wrapPromise(promise);
            expect(BacklogController.fetchBacklogItems).toHaveBeenCalledWith(50, 50);
            expect(BacklogController.fetchBacklogItems).toHaveBeenCalledWith(50, 100);
            expect(BacklogController.fetchBacklogItems.mock.calls).toHaveLength(2);
        });

        it(`Given that we were already loading backlog_items,
            when I fetch all the backlog items,
            then the REST route won't be called again
            and a promise will be rejected`, () => {
            BacklogController.backlog_items.loading = true;

            expect.assertions(1);
            // eslint-disable-next-line jest/valid-expect-in-promise
            const promise = BacklogController.fetchAllBacklogItems(50, 50).catch(() => {
                // eslint-disable-next-line jest/no-conditional-expect
                expect(BacklogController.fetchBacklogItems).not.toHaveBeenCalled();
            });
            return wrapPromise(promise);
        });

        it(`Given that all the backlog_items had been loaded,
            when I fetch all the backlog items,
            then the REST route won't be called again
            and a promise will be rejected`, () => {
            BacklogController.backlog_items.fully_loaded = true;

            expect.assertions(1);
            // eslint-disable-next-line jest/valid-expect-in-promise
            const promise = BacklogController.fetchAllBacklogItems(50, 50).catch(() => {
                // eslint-disable-next-line jest/no-conditional-expect
                expect(BacklogController.fetchBacklogItems).not.toHaveBeenCalled();
            });
            return wrapPromise(promise);
        });
    });

    describe("fetchBacklogItems() -", () => {
        it(`Given that we were in a project's context
            and given a limit and an offset,
            when I fetch backlog items,
            then the backlog will be marked as loading,
            BacklogItemService's Project route will be queried,
            its result will be appended to the backlog items
            and its promise will be returned`, async () => {
            jest.spyOn(BacklogController, "isMilestoneContext").mockReturnValue(false);
            BacklogItemService.getProjectBacklogItems.mockReturnValue(
                $q.resolve({
                    results: [{ id: 734 }],
                    total: 34,
                })
            );
            BacklogController.all_backlog_items = { 7: { id: 7 } };

            var promise = BacklogController.fetchBacklogItems(60, 25);
            expect(BacklogController.backlog_items.loading).toBeTruthy();

            await expect(wrapPromise(promise)).resolves.toBe(34);
            expect(BacklogItemService.getProjectBacklogItems).toHaveBeenCalledWith(736, 60, 25);
            expect(BacklogController.all_backlog_items).toStrictEqual({
                7: { id: 7 },
                734: { id: 734 },
            });
            expect(BacklogService.appendBacklogItems).toHaveBeenCalledWith([{ id: 734 }]);
            expect(BacklogService.filterItems).toHaveBeenCalledWith("");
        });

        it(`Given that we were in a milestone's context
            and given a limit and an offset,
            when I fetch backlog items,
            then the backlog will be marked as loading,
            BacklogItemService's Milestone route will be queried,
            its result will be appended to the backlog items
            and its promise will be returned`, async () => {
            BacklogItemService.getMilestoneBacklogItems.mockReturnValue(
                $q.resolve({
                    results: [{ id: 836 }],
                    total: 85,
                })
            );
            BacklogController.all_backlog_items = { 7: { id: 7 } };

            var promise = BacklogController.fetchBacklogItems(60, 25);
            expect(BacklogController.backlog_items.loading).toBeTruthy();

            await expect(wrapPromise(promise)).resolves.toBe(85);
            expect(BacklogItemService.getMilestoneBacklogItems).toHaveBeenCalledWith(592, 60, 25);
            expect(BacklogController.all_backlog_items).toStrictEqual({
                7: { id: 7 },
                836: { id: 836 },
            });
            expect(BacklogService.appendBacklogItems).toHaveBeenCalledWith([{ id: 836 }]);
            expect(BacklogService.filterItems).toHaveBeenCalledWith("");
        });
    });

    describe("filterBacklog() -", () => {
        beforeEach(() => {
            jest.spyOn(BacklogController, "fetchAllBacklogItems").mockImplementation(() => {});
        });

        it(`Given that all items had not been loaded,
            when I filter the backlog,
            then all the backlog items will be loaded and filtered`, () => {
            BacklogController.fetchAllBacklogItems.mockReturnValue($q.when(50));
            BacklogController.filter.terms = "flamboyantly";

            BacklogController.filterBacklog();
            $scope.$apply();

            expect(BacklogController.fetchAllBacklogItems).toHaveBeenCalledWith(50, 0);
            expect(BacklogService.filterItems).toHaveBeenCalledWith("flamboyantly");
        });

        it(`Given that all items had already been loaded,
            when I filter the backlog,
            then all the backlog items will be filtered`, () => {
            BacklogController.fetchAllBacklogItems.mockReturnValue($q.reject(99));
            BacklogController.filter.terms = "Jeffersonianism";

            BacklogController.filterBacklog();
            $scope.$apply();

            expect(BacklogController.fetchAllBacklogItems).toHaveBeenCalledWith(50, 0);
            expect(BacklogService.filterItems).toHaveBeenCalledWith("Jeffersonianism");
        });
    });

    describe("showAddBacklogItemModal() -", () => {
        let event, item_type;
        beforeEach(() => {
            event = {
                preventDefault: jest.fn(),
            };
            item_type = { id: 50 };
        });

        it(`Given an event and an item_type object,
            when I show the new artifact modal,
            then the event's default action will be prevented
            and the NewTuleapArtifactModalService will be called with a callback`, () => {
            BacklogController.showAddBacklogItemModal(event, item_type);

            expect(event.preventDefault).toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(
                user_id,
                50,
                null,
                expect.any(Function),
                []
            );
        });

        describe("callback -", () => {
            let artifact;
            beforeEach(() => {
                NewTuleapArtifactModalService.showCreation.mockImplementation(
                    (user_id, a, b, callback) => callback(5202)
                );
                artifact = {
                    backlog_item: {
                        id: 5202,
                    },
                };
            });

            describe("Given an item id and given that we were in a project's context,", () => {
                beforeEach(() => {
                    BacklogController.details = {
                        rest_route_id: 80,
                        rest_base_route: "projects",
                    };

                    jest.spyOn(BacklogController, "isMilestoneContext").mockReturnValue(false);
                });

                it("when the new artifact modal calls its callback, then the artifact will be prepended to the backlog using REST, it will be retrieved from the server, and the items and backlog_items collections will be updated", () => {
                    BacklogController.backlog_items.content = [{ id: 3894 }];
                    BacklogController.backlog_items.filtered_content = [{ id: 3894 }];
                    BacklogItemService.getBacklogItem.mockReturnValue($q.when(artifact));
                    ProjectService.removeAddReorderToBacklog.mockReturnValue($q.when());

                    BacklogController.showAddBacklogItemModal(event, item_type);
                    $scope.$apply();

                    expect(ProjectService.removeAddReorderToBacklog).toHaveBeenCalledWith(
                        undefined,
                        80,
                        [5202],
                        {
                            direction: "before",
                            item_id: 3894,
                        }
                    );
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect(BacklogController.all_backlog_items[5202]).toStrictEqual({ id: 5202 });
                    expect(BacklogController.backlog_items.content).toStrictEqual([
                        { id: 5202 },
                        { id: 3894 },
                    ]);
                    expect(BacklogController.backlog_items.filtered_content).toStrictEqual([
                        { id: 5202 },
                        { id: 3894 },
                    ]);
                });

                it("and given that the backlog was filtered, when the new artifact modal calls its callback, then the artifact will be prepended to the backlog's content but not its filtered content", () => {
                    BacklogController.filter.terms = "needle";
                    BacklogController.backlog_items.content = [{ id: 7453 }];
                    BacklogController.backlog_items.filtered_content = [];
                    BacklogItemService.getBacklogItem.mockReturnValue($q.when(artifact));
                    ProjectService.removeAddReorderToBacklog.mockReturnValue($q.when());

                    BacklogController.showAddBacklogItemModal(event, item_type);
                    $scope.$apply();

                    expect(BacklogController.backlog_items.content).toStrictEqual([
                        { id: 5202 },
                        { id: 7453 },
                    ]);
                    expect(BacklogController.backlog_items.filtered_content).toStrictEqual([]);
                });

                it("and given that the backlog_items collection was empty, when the new artifact modal calls its callback, then the artifact will be prepended to the backlog and prepended to the backlog_items collection", () => {
                    BacklogController.backlog_items.content = [];
                    BacklogItemService.getBacklogItem.mockReturnValue($q.when(artifact));
                    ProjectService.removeAddToBacklog.mockReturnValue($q.when());

                    BacklogController.showAddBacklogItemModal(event, item_type);
                    $scope.$apply();

                    expect(ProjectService.removeAddToBacklog).toHaveBeenCalledWith(undefined, 80, [
                        5202,
                    ]);
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect(BacklogController.backlog_items.content).toStrictEqual([{ id: 5202 }]);
                });
            });

            describe("Given an item id and given we were in a milestone's context", () => {
                beforeEach(() => {
                    BacklogController.details = {
                        rest_route_id: 26,
                        rest_base_route: "milestones",
                    };
                });

                it(", when the new artifact modal calls its callback, then the artifact will be prepended to the backlog, it will be retrieved from the server, and the items and backlog_items collections will be updated", () => {
                    BacklogController.backlog_items.content = [{ id: 6240 }];
                    BacklogItemService.getBacklogItem.mockReturnValue($q.when(artifact));
                    MilestoneService.removeAddReorderToBacklog.mockReturnValue($q.when());

                    BacklogController.showAddBacklogItemModal(event, item_type);
                    $scope.$apply();

                    expect(MilestoneService.removeAddReorderToBacklog).toHaveBeenCalledWith(
                        undefined,
                        26,
                        [5202],
                        {
                            direction: "before",
                            item_id: 6240,
                        }
                    );
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect(BacklogController.all_backlog_items[5202]).toStrictEqual({ id: 5202 });
                    expect(BacklogController.backlog_items.content).toStrictEqual([
                        { id: 5202 },
                        { id: 6240 },
                    ]);
                });

                it("and given that the scope's backlog_items was empty, when the new artifact modal calls its callback, then the artifact will be prepended to the backlog and prepended to the backlog_items collection", () => {
                    BacklogController.backlog_items.content = [];
                    BacklogItemService.getBacklogItem.mockReturnValue($q.when(artifact));
                    MilestoneService.removeAddToBacklog.mockReturnValue($q.when());

                    BacklogController.showAddBacklogItemModal(event, item_type);
                    $scope.$apply();

                    expect(MilestoneService.removeAddToBacklog).toHaveBeenCalledWith(
                        undefined,
                        26,
                        [5202]
                    );
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect(BacklogController.backlog_items.content).toStrictEqual([{ id: 5202 }]);
                });
            });
        });
    });

    describe("displayUserCantPrioritize() -", function () {
        it("Given that the user cannot move cards in the backlog and the backlog is empty, when I check, then it will return false", function () {
            BacklogService.backlog.user_can_move_cards = false;
            BacklogService.items.content = [];

            var result = BacklogController.displayUserCantPrioritize();

            expect(result).toBeFalsy();
        });

        it("Given that the user cannot move cards in the backlog and the backlog is not empty, when I check, then it will return true", function () {
            BacklogService.backlog.user_can_move_cards = false;
            BacklogService.items.content = [{ id: 448 }];

            var result = BacklogController.displayUserCantPrioritize();

            expect(result).toBeTruthy();
        });
    });

    describe("isBacklogLoadedAndEmpty() -", function () {
        it("Given that the backlog was loaded and had no children backlog items, when I check if the backlog is loaded and empty, then it will return true", function () {
            BacklogService.items.loading = false;
            BacklogService.items.fully_loaded = true;
            BacklogService.items.content = [];

            var result = BacklogController.isBacklogLoadedAndEmpty();

            expect(result).toBeTruthy();
        });

        it("Given that the backlog was loading, when I check if the backlog is loaded and empty, then it will return false", function () {
            BacklogService.items.loading = true;

            var result = BacklogController.isBacklogLoadedAndEmpty();

            expect(result).toBeFalsy();
        });

        it("Given that the backlog was loaded and had children backlog items, when I check if the backlog is loaded and empty, then it will return false", function () {
            BacklogService.items.loading = false;
            BacklogService.items.fully_loaded = true;
            BacklogService.items.content = [{ id: 92 }];

            var result = BacklogController.isBacklogLoadedAndEmpty();

            expect(result).toBeFalsy();
        });
    });

    describe("dragularDrop() -", function () {
        var $dropped_item_element,
            dropped_item_ids,
            dropped_items,
            $target_element,
            $source_element,
            source_model,
            target_model,
            initial_index,
            target_index,
            compared_to,
            move_request;

        beforeEach(function () {
            dropped_item_ids = [78];
            dropped_items = [{ id: 78 }];
            $dropped_item_element = createElement("li");
            angular.element($dropped_item_element).data("item-id", dropped_item_ids[0]);
            $source_element = createElement("ul", "backlog");
            initial_index = 0;
            target_index = 0;
            compared_to = {
                direction: "before",
                item_id: 53,
            };

            move_request = $q.defer();
        });

        describe(`Given an event,
            the dropped element,
            the target element,
            the source element,
            the source model,
            the initial index,
            the target model
            and the target index`, () => {
            it(`when I reorder an item in the backlog,
                then the item will be reordered using DroppedService`, () => {
                DroppedService.reorderBacklog.mockReturnValue(move_request.promise);
                $target_element = $source_element;
                source_model = [{ id: dropped_item_ids[0] }, { id: 53 }];
                target_model = undefined;

                $scope.$emit(
                    "dragulardrop",
                    $dropped_item_element,
                    $target_element,
                    $source_element,
                    source_model,
                    initial_index,
                    target_model,
                    target_index
                );

                expect(DroppedService.reorderBacklog).toHaveBeenCalledWith(
                    dropped_item_ids,
                    compared_to,
                    BacklogService.backlog
                );
            });

            it(`when I move an item from the backlog to a submilestone (e.g. to a Sprint),
                then the item will be moved using DroppedService
                and the submilestone's initial effort will be updated`, () => {
                DroppedService.moveFromBacklogToSubmilestone.mockReturnValue(move_request.promise);
                var destination_milestone_id = 80;

                $target_element = createElement("ul", "submilestone");
                angular.element($target_element).data("submilestone-id", destination_milestone_id);
                source_model = [];
                target_model = [{ id: dropped_item_ids[0] }, { id: 53 }];

                $scope.$emit(
                    "dragulardrop",
                    $dropped_item_element,
                    $target_element,
                    $source_element,
                    source_model,
                    initial_index,
                    target_model,
                    target_index
                );
                move_request.resolve();
                $scope.$apply();

                expect(DroppedService.moveFromBacklogToSubmilestone).toHaveBeenCalledWith(
                    dropped_item_ids,
                    compared_to,
                    destination_milestone_id
                );
                expect(BacklogService.removeBacklogItemsFromBacklog).toHaveBeenCalledWith(
                    dropped_items
                );
                expect(MilestoneCollectionService.refreshMilestone).toHaveBeenCalledWith(
                    destination_milestone_id
                );
            });
        });
    });

    describe("reorderBacklogItems() -", function () {
        it("reorder the content of a milestone", function () {
            var dropped_request = $q.defer(),
                backlog_items = [{ id: 1 }, { id: 2 }],
                compared_to = { item_id: 3, direction: "before" };

            DroppedService.reorderBacklog.mockReturnValue(dropped_request.promise);

            BacklogController.reorderBacklogItems(backlog_items, compared_to);
            dropped_request.resolve();
            $scope.$apply();

            expect(BacklogService.addOrReorderBacklogItemsInBacklog).toHaveBeenCalledWith(
                backlog_items,
                compared_to
            );
            expect(DroppedService.reorderBacklog).toHaveBeenCalledWith(
                [1, 2],
                compared_to,
                BacklogService.backlog
            );
        });
    });

    describe("moveToTop() -", function () {
        beforeEach(function () {
            jest.spyOn(BacklogController, "reorderBacklogItems").mockReturnValue(
                $q.defer().promise
            );
        });

        it("move one item to the top of the backlog", function () {
            var moved_backlog_item = { id: 69 };

            BacklogController.backlog_items = {
                content: [{ id: 50 }, { id: 61 }, moved_backlog_item, { id: 88 }],
            };

            BacklogController.moveToTop(moved_backlog_item);

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeFirstItem).toHaveBeenCalled();
            expect(BacklogController.reorderBacklogItems).toHaveBeenCalledWith(
                [moved_backlog_item],
                { direction: "before", item_id: 50 }
            );
        });

        it("move multiple items to the top of the backlog", function () {
            var moved_backlog_item = { id: 50 };
            var selected_backlog_items = [{ id: 50 }, { id: 69 }];

            BacklogItemSelectedService.areThereMultipleSelectedBaklogItems.mockReturnValue(true);
            BacklogItemSelectedService.getCompactedSelectedBacklogItem.mockReturnValue(
                selected_backlog_items
            );

            BacklogController.backlog_items = {
                content: [
                    selected_backlog_items[0],
                    { id: 61 },
                    selected_backlog_items[1],
                    { id: 88 },
                ],
            };

            BacklogController.moveToTop(moved_backlog_item);

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeFirstItem).toHaveBeenCalled();
            expect(BacklogController.reorderBacklogItems).toHaveBeenCalledWith(
                selected_backlog_items,
                { direction: "before", item_id: 61 }
            );
        });

        it("move multiple items to the top of the backlog, even if the backlog is filtered", function () {
            var moved_backlog_item = { id: 50 };
            var selected_backlog_items = [{ id: 50 }, { id: 69 }];

            BacklogItemSelectedService.areThereMultipleSelectedBaklogItems.mockReturnValue(true);
            BacklogItemSelectedService.getCompactedSelectedBacklogItem.mockReturnValue(
                selected_backlog_items
            );

            BacklogController.backlog_items = {
                content: [
                    selected_backlog_items[0],
                    { id: 61 },
                    selected_backlog_items[1],
                    { id: 88 },
                ],
                filtered_content: [
                    selected_backlog_items[0],
                    selected_backlog_items[1],
                    { id: 88 },
                ],
            };

            BacklogController.moveToTop(moved_backlog_item);

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeFirstItem).toHaveBeenCalled();
            expect(BacklogController.reorderBacklogItems).toHaveBeenCalledWith(
                selected_backlog_items,
                { direction: "before", item_id: 61 }
            );
        });
    });

    describe("moveToBottom() -", function () {
        var fetch_all_backlog_items_request;

        beforeEach(function () {
            fetch_all_backlog_items_request = $q.defer();
            jest.spyOn(BacklogController, "reorderBacklogItems").mockReturnValue(
                $q.defer().promise
            );
            jest.spyOn(BacklogController, "fetchAllBacklogItems").mockReturnValue(
                fetch_all_backlog_items_request.promise
            );
        });

        it("move one item to the bottom of the fully loaded backlog", function () {
            var moved_backlog_item = { id: 69 };

            BacklogController.backlog_items = {
                content: [{ id: 50 }, { id: 61 }, moved_backlog_item, { id: 88 }],
                pagination: {
                    limit: 50,
                    offset: 0,
                },
            };

            BacklogController.moveToBottom(moved_backlog_item);

            fetch_all_backlog_items_request.resolve();
            $scope.$apply();

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeLastItem).toHaveBeenCalled();
            expect(BacklogController.reorderBacklogItems).toHaveBeenCalledWith(
                [moved_backlog_item],
                { direction: "after", item_id: 88 }
            );
        });

        it("move multiple items to the bottom of the fully loaded backlog, even if the backlog is filtered", function () {
            var moved_backlog_item = { id: 50 };
            var selected_backlog_items = [{ id: 50 }, { id: 69 }];

            BacklogItemSelectedService.areThereMultipleSelectedBaklogItems.mockReturnValue(true);
            BacklogItemSelectedService.getCompactedSelectedBacklogItem.mockReturnValue(
                selected_backlog_items
            );

            BacklogController.backlog_items = {
                content: [{ id: 50 }, { id: 61 }, moved_backlog_item, { id: 88 }],
                filtered_content: [
                    selected_backlog_items[0],
                    selected_backlog_items[1],
                    { id: 88 },
                ],
                pagination: {
                    limit: 50,
                    offset: 0,
                },
            };

            BacklogController.moveToBottom(moved_backlog_item);

            fetch_all_backlog_items_request.resolve();
            $scope.$apply();

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeLastItem).toHaveBeenCalled();
            expect(BacklogController.reorderBacklogItems).toHaveBeenCalledWith(
                selected_backlog_items,
                { direction: "after", item_id: 88 }
            );
        });

        it("move multiple items to the bottom of the not fully loaded backlog", function () {
            var moved_backlog_item = { id: 50 };
            var selected_backlog_items = [{ id: 50 }, { id: 69 }];

            BacklogItemSelectedService.areThereMultipleSelectedBaklogItems.mockReturnValue(true);
            BacklogItemSelectedService.getCompactedSelectedBacklogItem.mockReturnValue(
                selected_backlog_items
            );

            BacklogController.backlog_items = {
                content: [{ id: 50 }, { id: 61 }, moved_backlog_item, { id: 88 }],
                pagination: {
                    limit: 2,
                    offset: 0,
                },
            };

            BacklogController.moveToBottom(moved_backlog_item);

            fetch_all_backlog_items_request.resolve();
            $scope.$apply();

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeLastItem).toHaveBeenCalled();
            expect(BacklogController.reorderBacklogItems).toHaveBeenCalledWith(
                selected_backlog_items,
                { direction: "after", item_id: 88 }
            );
        });
    });

    describe("dragularOptionsForBacklog()", () => {
        describe("accepts()", () => {
            var $element_to_drop, $target_container_element;
            beforeEach(() => {
                $element_to_drop = createElement("li");
                $target_container_element = createElement("ul");
            });

            describe("Given an element to drop and a target container element", function () {
                it("and given that the element's type was in the container's accepted types, when I check if the element can be dropped, then it will return true", function () {
                    angular.element($element_to_drop).data("type", "trackerId49");
                    angular
                        .element($target_container_element)
                        .data("accept", "trackerId38|trackerId49");

                    var result = BacklogController.dragularOptionsForBacklog().accepts(
                        $element_to_drop,
                        $target_container_element
                    );

                    expect(result).toBeTruthy();
                });

                it("and given that the element's type was not in the container's accepted types, when I check if the element can be dropped, then it will return false", function () {
                    angular.element($element_to_drop).data("type", "trackerId49");
                    angular.element($target_container_element).data("accept", "trackerId38");

                    var result = BacklogController.dragularOptionsForBacklog().accepts(
                        $element_to_drop,
                        $target_container_element
                    );

                    expect(result).toBeFalsy();
                });

                it("and given that the container had nodrop data, when I check if the element can be dropped, then it will return false", function () {
                    angular.element($target_container_element).data("nodrop", true);

                    var result = BacklogController.dragularOptionsForBacklog().accepts(
                        $element_to_drop,
                        $target_container_element
                    );

                    expect(result).toBeFalsy();
                });
            });
        });

        describe("moves()", () => {
            var $element_to_drag, $container, $handle_element;
            beforeEach(() => {
                $element_to_drag = createElement("li");
                $container = undefined;
            });

            describe("Given an element to drag and its child handle element", () => {
                it(`and given that the handle has an ancestor with the 'dragular-handle' class
                    and the element didn't have nodrag data,
                    when I check if the element can be dragged,
                    then it will return true`, () => {
                    const dragular_handle = createElement("div", "dragular-handle");
                    const $handle_element = createElement("span");
                    dragular_handle.appendChild($handle_element);
                    $element_to_drag.appendChild(dragular_handle);

                    var result = BacklogController.dragularOptionsForBacklog().moves(
                        $element_to_drag,
                        $container,
                        $handle_element
                    );

                    expect(result).toBeTruthy();
                });

                it(`and given that the handle didn't have any ancestor with the 'dragular-handle' class
                    and the element didn't have nodrag data,
                    when I check if the element can be dragged,
                    then it will return false`, () => {
                    const $handle_element = createElement("span");
                    $element_to_drag.appendChild($handle_element);

                    var result = BacklogController.dragularOptionsForBacklog().moves(
                        $element_to_drag,
                        $container,
                        $handle_element
                    );

                    expect(result).toBeFalsy();
                });

                it("and given that the element had nodrag data, when I check if the element can be dragged, then it will return false", function () {
                    angular.element($element_to_drag).data("nodrag", true);

                    var result = BacklogController.dragularOptionsForBacklog().moves(
                        $element_to_drag,
                        $container,
                        $handle_element
                    );

                    expect(result).toBeFalsy();
                });
            });
        });
    });

    describe("soloButtonCanBeDisplayed()", () => {
        it("button is not displayed if user cannot move cards", () => {
            jest.spyOn(BacklogController, "canUserMoveCards").mockReturnValue(false);
            BacklogController.details = {
                accepted_types: {
                    content: [{ id: 104 }],
                },
            };

            expect(BacklogController.soloButtonCanBeDisplayed()).toBe(false);
        });

        it("button is not displayed if there are multiple trackers", () => {
            jest.spyOn(BacklogController, "canUserMoveCards").mockReturnValue(true);
            BacklogController.details = {
                accepted_types: {
                    content: [{ id: 104 }, { id: 105 }],
                },
            };

            expect(BacklogController.soloButtonCanBeDisplayed()).toBe(false);
        });

        it("button is not displayed if no tracker can be planned", () => {
            jest.spyOn(BacklogController, "canUserMoveCards").mockReturnValue(true);
            BacklogController.details = {
                accepted_types: { content: [] },
            };

            expect(BacklogController.soloButtonCanBeDisplayed()).toBe(false);
        });

        it("button is displayed if there is only one element, shared property is set to true and user can move cards", function () {
            jest.spyOn(BacklogController, "canUserMoveCards").mockReturnValue(true);
            BacklogController.details = {
                accepted_types: {
                    content: [{ id: 104 }],
                },
            };

            expect(BacklogController.soloButtonCanBeDisplayed()).toBe(true);
        });
    });

    describe("addItemButtonCanBeDisplayed()", () => {
        it("button is not displayed if user cannot move cards", () => {
            BacklogController.details = {
                accepted_types: {
                    content: [{ id: 104 }, { id: 105 }],
                },
                user_can_move_cards: false,
            };

            expect(BacklogController.addItemButtonCanBeDisplayed()).toBe(false);
        });

        it("button is not displayed if no tracker can be planned", () => {
            BacklogController.details = {
                accepted_types: { content: [] },
                user_can_move_cards: true,
            };

            expect(BacklogController.addItemButtonCanBeDisplayed()).toBe(false);
        });

        it(`button is displayed if there are elements,
            shared property is set to true and user can move cards`, () => {
            BacklogController.details = {
                accepted_types: {
                    content: [{ id: 104 }, { id: 105 }],
                },
                user_can_move_cards: true,
            };

            expect(BacklogController.addItemButtonCanBeDisplayed()).toBe(true);
        });
    });
});
