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

import angular from "angular";
import "angular-mocks";

import planning_module from "../app.js";
import * as rest_querier from "../api/rest-querier";

describe("PlannerView", () => {
    let $scope,
        $filter,
        $q,
        PlanningController,
        BacklogItemService,
        BacklogService,
        MilestoneService,
        SharedPropertiesService,
        NewTuleapArtifactModalService,
        UserPreferencesService,
        BacklogItemCollectionService,
        BacklogItemSelectedService,
        ItemAnimatorService;

    const user_id = 102;

    beforeEach(() => {
        angular.mock.module(planning_module);

        angular.mock.inject(function (
            $componentController,
            $rootScope,
            _$q_,
            _BacklogService_,
            _BacklogItemService_,
            _MilestoneService_,
            _NewTuleapArtifactModalService_,
            _SharedPropertiesService_,
            _UserPreferencesService_,
            _BacklogItemCollectionService_,
            _BacklogItemSelectedService_,
            _ItemAnimatorService_
        ) {
            $scope = $rootScope.$new();
            $q = _$q_;

            SharedPropertiesService = _SharedPropertiesService_;
            jest.spyOn(SharedPropertiesService, "getUserId").mockReturnValue(user_id);
            jest.spyOn(SharedPropertiesService, "getProjectId").mockReturnValue(736);
            jest.spyOn(SharedPropertiesService, "getMilestoneId").mockReturnValue(592);
            jest.spyOn(SharedPropertiesService, "getViewMode").mockImplementation(() => {});

            var returnPromise = function (method) {
                var self = this;
                jest.spyOn(self, method).mockReturnValue($q.defer().promise);
            };

            BacklogItemService = _BacklogItemService_;
            [
                "getBacklogItemChildren",
                "getMilestoneBacklogItems",
                "getProjectBacklogItems",
                "getBacklogItem",
                "removeAddBacklogItemChildren",
            ].forEach(returnPromise, BacklogItemService);

            MilestoneService = _MilestoneService_;
            [
                "addReorderToContent",
                "addToContent",
                "augmentMilestone",
                "defineAllowedBacklogItemTypes",
                "getMilestone",
                "patchSubMilestones",
                "removeAddReorderToBacklog",
                "removeAddToBacklog",
            ].forEach(returnPromise, MilestoneService);

            NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
            jest.spyOn(NewTuleapArtifactModalService, "showCreation").mockImplementation(() => {});
            jest.spyOn(NewTuleapArtifactModalService, "showEdition").mockImplementation(() => {});

            UserPreferencesService = _UserPreferencesService_;
            jest.spyOn(UserPreferencesService, "setPreference").mockReturnValue($q.defer().promise);

            BacklogService = _BacklogService_;

            BacklogItemCollectionService = _BacklogItemCollectionService_;
            jest.spyOn(BacklogItemCollectionService, "refreshBacklogItem").mockImplementation(
                () => {}
            );

            $filter = jest.fn(function () {
                return function () {};
            });

            ItemAnimatorService = _ItemAnimatorService_;
            BacklogItemSelectedService = _BacklogItemSelectedService_;
            PlanningController = $componentController("plannerView", {
                $filter,
                $q,
                $scope,
                BacklogService,
                BacklogItemService,
                MilestoneService,
                NewTuleapArtifactModalService,
                SharedPropertiesService,
                UserPreferencesService,
                BacklogItemCollectionService,
                BacklogItemSelectedService,
            });
        });

        jest.spyOn(ItemAnimatorService, "animateCreated").mockImplementation(() => {});
        jest.spyOn(rest_querier, "getOpenTopMilestones").mockImplementation(() => {});
        jest.spyOn(rest_querier, "getOpenSubMilestones").mockImplementation(() => {});

        PlanningController.$onInit();
    });

    describe("$onInit", () => {
        describe("Given we were in a Project context (Top backlog)", () => {
            beforeEach(() => {
                SharedPropertiesService.getMilestoneId.mockImplementation(() => {});
            });

            it("when I load the controller, then the top milestones will be retrieved", () => {
                const getTopMilestones = jest
                    .spyOn(rest_querier, "getOpenTopMilestones")
                    .mockImplementation((id, callback) => {
                        callback([
                            { id: 184, start_date: null },
                            { id: 307, start_date: "2016-11-12T01:00:00+01:00" },
                            { id: 295, start_date: "2017-01-01T01:00:00+01:00" },
                        ]);
                        return $q.when();
                    });

                PlanningController.$onInit();
                expect(PlanningController.milestones.loading).toBeTruthy();
                $scope.$apply();

                expect(getTopMilestones).toHaveBeenCalledWith(736, expect.any(Function));
                expect(PlanningController.milestones.loading).toBeFalsy();
                expect(PlanningController.milestones.content).toStrictEqual([
                    { id: 307, start_date: "2016-11-12T01:00:00+01:00" },
                    { id: 295, start_date: "2017-01-01T01:00:00+01:00" },
                    { id: 184, start_date: null },
                ]);
            });
        });

        describe("Given we were in a Milestone context", () => {
            beforeEach(() => {
                SharedPropertiesService.getMilestoneId.mockReturnValue(592);
            });

            it("when I load the controller, then the submilestones will be retrieved", () => {
                const milestone = {
                    id: 592,
                    resources: {
                        backlog: { accept: { trackers: [{ id: 99, label: "story" }] } },
                        content: { accept: { trackers: [{ id: 99, label: "story" }] } },
                    },
                    sub_milestone_type: { id: 66, label: "sprints" },
                };
                MilestoneService.getMilestone.mockReturnValue($q.when({ results: milestone }));
                const getSubMilestones = jest
                    .spyOn(rest_querier, "getOpenSubMilestones")
                    .mockImplementation((id, callback) => {
                        callback([
                            { id: 307, start_date: "2016-11-12T01:00:00+01:00" },
                            { id: 184, start_date: null },
                            { id: 295, start_date: "2017-01-01T01:00:00+01:00" },
                        ]);
                        return $q.when();
                    });

                PlanningController.$onInit();
                expect(PlanningController.milestones.loading).toBeTruthy();
                $scope.$apply();

                expect(getSubMilestones).toHaveBeenCalledWith(592, expect.any(Function));
                expect(PlanningController.milestones.loading).toBeFalsy();
                expect(PlanningController.milestones.content).toStrictEqual([
                    { id: 307, start_date: "2016-11-12T01:00:00+01:00" },
                    { id: 295, start_date: "2017-01-01T01:00:00+01:00" },
                    { id: 184, start_date: null },
                ]);
            });
        });

        it("Load injected view mode", () => {
            SharedPropertiesService.getViewMode.mockReturnValue("detailed-view");
            PlanningController.show_closed_view_key = "show-closed-view";

            PlanningController.$onInit();

            expect(PlanningController.current_view_class).toBe("detailed-view");
            expect(PlanningController.current_closed_view_class).toBe("show-closed-view");
        });
    });

    describe("switchViewMode() -", function () {
        it("Given a view mode, when I switch to this view mode, then the current view class will be updated and this mode will be saved as my user preference", function () {
            PlanningController.switchViewMode("detailed-view");

            expect(PlanningController.current_view_class).toBe("detailed-view");
            expect(UserPreferencesService.setPreference).toHaveBeenCalledWith(
                102,
                "agiledashboard_planning_item_view_mode_736",
                "detailed-view"
            );
        });
    });

    describe("switchClosedMilestoneItemsViewMode() -", function () {
        it("Given a view mode, when I switch closed milestones' view mode, then the current view class will be updated", function () {
            PlanningController.switchClosedMilestoneItemsViewMode("show-closed-view");

            expect(PlanningController.current_closed_view_class).toBe("show-closed-view");
        });
    });

    describe("displayClosedMilestones", () => {
        beforeEach(() => {
            jest.spyOn(PlanningController, "isMilestoneContext").mockImplementation(() => {});
            PlanningController.milestones.content = [
                { id: 747, start_date: "2016-08-14T01:00:00+01:00" },
            ];
        });

        it(`Given that we were in a project's context,
            when I display closed milestones,
            then the closed milestones will be retrieved and the milestones collection will be updated`, () => {
            PlanningController.isMilestoneContext.mockReturnValue(false);
            const getClosedTopMilestones = jest
                .spyOn(rest_querier, "getClosedTopMilestones")
                .mockImplementation((id, callback) => {
                    callback([
                        { id: 295, start_date: "2017-01-01T01:00:00+01:00" },
                        { id: 184, start_date: null },
                        { id: 307, start_date: "2016-11-12T01:00:00+01:00" },
                    ]);
                    return $q.when();
                });

            PlanningController.displayClosedMilestones();
            expect(PlanningController.milestones.loading).toBeTruthy();
            $scope.$apply();

            expect(getClosedTopMilestones).toHaveBeenCalledWith(736, expect.any(Function));
            expect(PlanningController.milestones.loading).toBeFalsy();
            expect(PlanningController.milestones.content).toStrictEqual([
                { id: 747, start_date: "2016-08-14T01:00:00+01:00" },
                { id: 307, start_date: "2016-11-12T01:00:00+01:00" },
                { id: 295, start_date: "2017-01-01T01:00:00+01:00" },
                { id: 184, start_date: null },
            ]);
        });

        it(`Given that we were in a milestone's context,
            when I display closed milestones,
            then the closed milestones will be retrieved and the milestones collection will be updated`, () => {
            PlanningController.isMilestoneContext.mockReturnValue(true);
            const getClosedSubMilestones = jest
                .spyOn(rest_querier, "getClosedSubMilestones")
                .mockImplementation((id, callback) => {
                    callback([
                        { id: 295, start_date: "2017-01-01T01:00:00+01:00" },
                        { id: 184, start_date: null },
                        { id: 307, start_date: "2016-11-12T01:00:00+01:00" },
                    ]);
                    return $q.when();
                });

            PlanningController.displayClosedMilestones();
            expect(PlanningController.milestones.loading).toBeTruthy();
            $scope.$apply();

            expect(getClosedSubMilestones).toHaveBeenCalledWith(592, expect.any(Function));
            expect(PlanningController.milestones.loading).toBeFalsy();
            expect(PlanningController.milestones.content).toStrictEqual([
                { id: 747, start_date: "2016-08-14T01:00:00+01:00" },
                { id: 307, start_date: "2016-11-12T01:00:00+01:00" },
                { id: 295, start_date: "2017-01-01T01:00:00+01:00" },
                { id: 184, start_date: null },
            ]);
        });
    });

    describe("thereAreOpenMilestonesLoaded() -", function () {
        it("Given that open milestones have previously been loaded, when I check if open milestones have been loaded, then it will return true", function () {
            $filter.mockReturnValue(function () {
                return [
                    {
                        id: 9,
                        semantic_status: "open",
                    },
                ];
            });

            var result = PlanningController.thereAreOpenMilestonesLoaded();

            expect(result).toBeTruthy();
        });

        it("Given that open milestones have never been loaded, when I check if open milestones have been loaded, then it will return false", function () {
            $filter.mockReturnValue(function () {
                return [];
            });

            var result = PlanningController.thereAreOpenMilestonesLoaded();

            expect(result).toBeFalsy();
        });
    });

    describe("thereAreClosedMilestonesLoaded() -", function () {
        it("Given that closed milestones have previously been loaded, when I check if closed milestones have been loaded, then it will return true", function () {
            $filter.mockReturnValue(function () {
                return [
                    {
                        id: 36,
                        semantic_status: "closed",
                    },
                ];
            });

            var result = PlanningController.thereAreClosedMilestonesLoaded();

            expect(result).toBeTruthy();
        });

        it("Given that closed milestones have never been loaded, when I check if closed milestones have been loaded, then it will return false", function () {
            $filter.mockReturnValue(function () {
                return [];
            });

            var result = PlanningController.thereAreClosedMilestonesLoaded();

            expect(result).toBeFalsy();
        });
    });

    describe("generateMilestoneLinkUrl() -", function () {
        it("Given a milestone and a pane, when I generate a Milestone link URL, then a correct URL will be generated", function () {
            var milestone = {
                id: 71,
                planning: {
                    id: 207,
                },
            };
            var pane = "burndown";

            var result = PlanningController.generateMilestoneLinkUrl(milestone, pane);

            expect(result).toBe("?group_id=736&planning_id=207&action=show&aid=71&pane=burndown");
        });
    });

    describe("displayUserCantPrioritizeForMilestones() -", function () {
        it("Given that there were no milestones, when I check whether the user cannot prioritize items in milestones, then it will return false", function () {
            PlanningController.milestones.content = [];

            var result = PlanningController.displayUserCantPrioritizeForMilestones();

            expect(result).toBeFalsy();
        });

        it("Given that the user can prioritize items in milestones, when I check, then it will return true", function () {
            PlanningController.milestones.content = [
                {
                    has_user_priority_change_permission: true,
                },
            ];

            var result = PlanningController.displayUserCantPrioritizeForMilestones();

            expect(result).toBeFalsy();
        });
    });

    describe("canShowBacklogItem() -", function () {
        it("Given an open backlog item, when I check whether we can show it, then it will return true", function () {
            var backlog_item = {
                isOpen: function () {
                    return true;
                },
            };

            var result = PlanningController.canShowBacklogItem(backlog_item);

            expect(result).toBeTruthy();
        });

        it("Given a closed backlog item, and we are displaying closed items, when I check whether we can show it, then it will return true", function () {
            var backlog_item = {
                isOpen: function () {
                    return false;
                },
            };
            PlanningController.current_closed_view_class = "show-closed-view";

            var result = PlanningController.canShowBacklogItem(backlog_item);

            expect(result).toBeTruthy();
        });

        it("Given a closed backlog item, and we are not displaying closed items, when I check whether we can show it, then it will return false", function () {
            var backlog_item = {
                isOpen: function () {
                    return false;
                },
            };
            PlanningController.current_closed_view_class = "hide-closed-view";

            var result = PlanningController.canShowBacklogItem(backlog_item);

            expect(result).toBeFalsy();
        });

        it("Given an item that didn't have an isOpen() method, when I check whether we can show it, then it will return true", function () {
            var backlog_item = { isOpen: undefined };

            var result = PlanningController.canShowBacklogItem(backlog_item);

            expect(result).toBeTruthy();
        });
    });

    describe("showEditModal() -", function () {
        var event, item, get_request;
        beforeEach(function () {
            get_request = $q.defer();
            event = { preventDefault: jest.fn() };
            event.which = 1;
            item = {
                artifact: {
                    id: 651,
                    tracker: {
                        id: 30,
                    },
                },
            };
            NewTuleapArtifactModalService.showEdition.mockImplementation(function (
                c,
                a,
                b,
                callback
            ) {
                callback(8541);
            });
            BacklogItemCollectionService.refreshBacklogItem.mockReturnValue(get_request.promise);
        });

        it("Given a left click event and an item to edit, when I show the edit modal, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback, and the callback will be called", function () {
            PlanningController.showEditModal(event, item);

            expect(event.preventDefault).toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showEdition).toHaveBeenCalledWith(
                102,
                30,
                651,
                expect.any(Function)
            );
            expect(BacklogItemCollectionService.refreshBacklogItem).toHaveBeenCalledWith(
                8541,
                undefined
            );
        });

        it("Given a middle click event and an item to edit, when I show the edit modal, then the event's default action will NOT be prevented and the NewTuleapArtifactModalService won't be called.", function () {
            event.which = 2;

            PlanningController.showEditModal(event, item);

            expect(event.preventDefault).not.toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showEdition).not.toHaveBeenCalled();
        });

        describe("callback -", function () {
            it("Given a milestone, when the artifact modal calls its callback, then the milestone's initial effort will be updated", function () {
                const updateInitialEffort = jest
                    .spyOn(MilestoneService, "updateInitialEffort")
                    .mockImplementation(() => {});
                var milestone = {
                    id: 38,
                    label: "Release v1.0",
                };

                PlanningController.showEditModal(event, item, milestone);
                get_request.resolve();
                $scope.$apply();

                expect(updateInitialEffort).toHaveBeenCalledWith(milestone);
            });
        });
    });

    describe("showEditSubmilestoneModal() -", function () {
        var event, item;
        beforeEach(function () {
            event = { preventDefault: jest.fn(), stopPropagation: jest.fn() };
            NewTuleapArtifactModalService.showEdition.mockImplementation(function (
                c,
                a,
                b,
                callback
            ) {
                callback(9040);
            });
        });

        it("Given a left click event and a submilestone to edit, when I show the edit modal, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback, and the callback will be called", function () {
            event.which = 1;
            item = {
                artifact: {
                    id: 9040,
                    tracker: {
                        id: 12,
                    },
                },
            };
            jest.spyOn(PlanningController, "refreshSubmilestone").mockImplementation(() => {});

            PlanningController.showEditSubmilestoneModal(event, item);

            expect(NewTuleapArtifactModalService.showEdition).toHaveBeenCalledWith(
                user_id,
                12,
                9040,
                expect.any(Function)
            );
            expect(PlanningController.refreshSubmilestone).toHaveBeenCalledWith(9040);
        });
    });

    describe("showAddSubmilestoneModal() -", () => {
        let event, submilestone_type;
        beforeEach(() => {
            submilestone_type = { id: 82 };
            event = { preventDefault: jest.fn() };
            NewTuleapArtifactModalService.showCreation.mockImplementation(
                (user_id, a, b, callback) => callback(1668)
            );
        });

        it("Given any click event and a submilestone_type object, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback", () => {
            PlanningController.showAddSubmilestoneModal(event, submilestone_type);

            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(
                102,
                82,
                PlanningController.milestone_id,
                expect.any(Function),
                []
            );
        });

        describe("callback -", () => {
            describe("Given that we were in a milestone context", () => {
                it(", when the artifact modal calls its callback, then the milestones collection will be updated", () => {
                    PlanningController.backlog.rest_route_id = 736;
                    PlanningController.milestones.content = [
                        {
                            id: 3118,
                            label: "Sprint 2015-38",
                            semantic_status: "open",
                        },
                    ];
                    MilestoneService.patchSubMilestones.mockReturnValue($q.when());
                    MilestoneService.getMilestone.mockReturnValue(
                        $q.when({
                            results: {
                                id: 1668,
                                label: "Sprint 2015-20",
                                semantic_status: "open",
                            },
                        })
                    );

                    PlanningController.showAddSubmilestoneModal(event, submilestone_type);
                    $scope.$apply();

                    expect(MilestoneService.patchSubMilestones).toHaveBeenCalledWith(736, [1668]);
                    expect(MilestoneService.getMilestone).toHaveBeenCalledWith(
                        1668,
                        expect.any(Object)
                    );
                    expect(PlanningController.milestones.content).toStrictEqual([
                        {
                            id: 1668,
                            label: "Sprint 2015-20",
                            semantic_status: "open",
                        },
                        {
                            id: 3118,
                            label: "Sprint 2015-38",
                            semantic_status: "open",
                        },
                    ]);
                });
            });

            it("Given that we were in a project context (Top Backlog), when the artifact modal calls its callback, then the MilestoneService will be called and the milestones collection will be updated", () => {
                jest.spyOn(PlanningController, "isMilestoneContext").mockReturnValue(false);
                PlanningController.milestones.content = [
                    {
                        id: 3118,
                        label: "Sprint 2015-38",
                    },
                ];
                MilestoneService.getMilestone.mockReturnValue(
                    $q.when({
                        results: {
                            id: 1668,
                            label: "Sprint 2015-20",
                        },
                    })
                );

                PlanningController.showAddSubmilestoneModal(event, submilestone_type);
                $scope.$apply();

                expect(MilestoneService.getMilestone).toHaveBeenCalledWith(
                    1668,
                    expect.any(Object)
                );
                expect(PlanningController.milestones.content).toStrictEqual([
                    {
                        id: 1668,
                        label: "Sprint 2015-20",
                    },
                    {
                        id: 3118,
                        label: "Sprint 2015-38",
                    },
                ]);
            });
        });
    });

    describe("showAddItemToSubMilestoneModal() -", () => {
        let item_type, artifact, submilestone;

        beforeEach(() => {
            NewTuleapArtifactModalService.showCreation.mockImplementation(
                (user_id, a, b, callback) => callback(7488)
            );
            artifact = {
                backlog_item: {
                    id: 7488,
                },
            };
        });

        it("Given an item_type object and a milestone object, then the NewTuleapArtifactModalService will be called with a callback", () => {
            item_type = { id: 94 };
            submilestone = { id: 196 };

            PlanningController.showAddItemToSubMilestoneModal(item_type, submilestone);

            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(
                user_id,
                94,
                null,
                expect.any(Function),
                []
            );
        });

        describe("callback - Given a submilestone object and an item id,", () => {
            beforeEach(() => {
                item_type = { id: 413 };
                submilestone = {
                    id: 92,
                    content: [],
                };
            });

            it("when the artifact modal calls its callback, then the artifact will be prepended to the submilestone using the REST route and will be prepended to its content attribute", () => {
                submilestone.content = [{ id: 9402 }];
                BacklogItemService.getBacklogItem.mockReturnValue($q.when(artifact));
                MilestoneService.addReorderToContent.mockReturnValue($q.when());

                PlanningController.showAddItemToSubMilestoneModal(item_type, submilestone);
                $scope.$apply();

                expect(MilestoneService.addReorderToContent).toHaveBeenCalledWith(92, [7488], {
                    direction: "before",
                    item_id: 9402,
                });
                expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(7488);
                expect(submilestone.content).toStrictEqual([{ id: 7488 }, { id: 9402 }]);
            });

            it("and given that the submilestone's content was empty, when the artifact modal calls its callback, then the artifact will be prepended to the submilestone using the REST route and will be prepended to its content attribute", () => {
                BacklogItemService.getBacklogItem.mockReturnValue($q.when(artifact));
                MilestoneService.addToContent.mockReturnValue($q.when());

                PlanningController.showAddItemToSubMilestoneModal(item_type, submilestone);
                $scope.$apply();

                expect(MilestoneService.addToContent).toHaveBeenCalledWith(92, [7488]);
                expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(7488);
                expect(submilestone.content).toStrictEqual([{ id: 7488 }]);
            });
        });
    });

    describe("refreshSubmilestone() -", function () {
        var get_milestone_request;

        beforeEach(function () {
            get_milestone_request = $q.defer();
        });

        it("Given an existing submilestone, when I refresh it, then the submilestone will be retrieved from the server and the milestones collection will be updated", function () {
            PlanningController.milestones.content = [{ id: 9040 }];
            MilestoneService.getMilestone.mockReturnValue(get_milestone_request.promise);

            PlanningController.refreshSubmilestone(9040);

            get_milestone_request.resolve({
                results: { id: 9040 },
            });
            expect(PlanningController.milestones.content).toStrictEqual([
                expect.objectContaining({ id: 9040, updating: true }),
            ]);
            $scope.$apply();

            expect(MilestoneService.getMilestone).toHaveBeenCalledWith(9040);
            expect(PlanningController.milestones.content).toStrictEqual([
                expect.objectContaining({ id: 9040, updating: false }),
            ]);
        });
    });

    describe("canUserCreateMilestone() -", function () {
        it("Given that there were no sub milestones, when I check whether the user can create milestones, then it will return false", function () {
            PlanningController.backlog.submilestone_type = null;
            PlanningController.backlog.user_can_move_cards = true;
            PlanningController.user_can_create_milestone = true;

            var result = PlanningController.canUserCreateMilestone();

            expect(result).toBeFalsy();
        });

        it("Given that user cannot move cards, when I check whether the user can create milestones, then it will return false", function () {
            PlanningController.backlog.submilestone_type = "release";
            PlanningController.backlog.user_can_move_cards = false;
            PlanningController.user_can_create_milestone = true;

            var result = PlanningController.canUserCreateMilestone();

            expect(result).toBeFalsy();
        });

        it("Given that all requirements are true, when I check whether the user can create milestones, then it will return true", function () {
            PlanningController.backlog.submilestone_type = "release";
            PlanningController.backlog.user_can_move_cards = true;
            PlanningController.user_can_create_milestone = true;

            var result = PlanningController.canUserCreateMilestone();

            expect(result).toBeTruthy();
        });
    });

    describe("hasOriginalProject() -", function () {
        it("Given that there is no original project, then it will returns false", function () {
            const result = PlanningController.hasOriginalProject();

            expect(result).toBeFalsy();
        });

        it("Given that there is an original project, then it will returns true", function () {
            PlanningController.backlog.original_project = { id: 101, label: "other project" };
            const result = PlanningController.hasOriginalProject();

            expect(result).toBeTruthy();
        });
    });
});
