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
import { createAngularPromiseWrapper } from "@tuleap/build-system-configurator/dist/jest/angular-promise-wrapper";

import planning_module from "../app.js";
import * as rest_querier from "../api/rest-querier";
import { SESSION_STORAGE_KEY } from "../session";

const noop = () => {
    // Do nothing
};

const SUB_MILESTONE_ID = 9040;

describe("PlannerView", () => {
    let $scope,
        $filter,
        $q,
        $window,
        PlanningController,
        BacklogItemService,
        BacklogService,
        MilestoneService,
        SharedPropertiesService,
        NewTuleapArtifactModalService,
        UserPreferencesService,
        BacklogItemCollectionService,
        BacklogItemSelectedService,
        ItemAnimatorService,
        wrapPromise;

    const user_id = 102;

    beforeEach(() => {
        angular.mock.module(planning_module);

        angular.mock.inject(
            function (
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
                _ItemAnimatorService_,
            ) {
                wrapPromise = createAngularPromiseWrapper($rootScope);
                $scope = $rootScope.$new();
                $q = _$q_;
                $window = {
                    sessionStorage: { setItem: noop },
                    location: { reload: noop },
                };

                SharedPropertiesService = _SharedPropertiesService_;
                jest.spyOn(SharedPropertiesService, "getUserId").mockReturnValue(user_id);
                jest.spyOn(SharedPropertiesService, "getProjectId").mockReturnValue(736);
                jest.spyOn(SharedPropertiesService, "getMilestoneId").mockReturnValue(592);
                jest.spyOn(SharedPropertiesService, "getViewMode").mockImplementation(() => {});
                jest.spyOn(
                    SharedPropertiesService,
                    "shouldloadOpenAndClosedMilestones",
                ).mockReturnValue(false);

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
                jest.spyOn(NewTuleapArtifactModalService, "showCreation").mockImplementation(
                    () => {},
                );
                jest.spyOn(NewTuleapArtifactModalService, "showEdition").mockImplementation(
                    () => {},
                );

                UserPreferencesService = _UserPreferencesService_;
                jest.spyOn(UserPreferencesService, "setPreference").mockReturnValue(
                    $q.defer().promise,
                );

                BacklogService = _BacklogService_;

                BacklogItemCollectionService = _BacklogItemCollectionService_;
                jest.spyOn(BacklogItemCollectionService, "refreshBacklogItem").mockImplementation(
                    () => {},
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
                    $window,
                    BacklogService,
                    BacklogItemService,
                    MilestoneService,
                    NewTuleapArtifactModalService,
                    SharedPropertiesService,
                    UserPreferencesService,
                    BacklogItemCollectionService,
                    BacklogItemSelectedService,
                });
            },
        );

        jest.spyOn(ItemAnimatorService, "animateCreated").mockImplementation(() => {});
        jest.spyOn(rest_querier, "getOpenTopMilestones").mockImplementation(() => {});
        jest.spyOn(rest_querier, "getOpenSubMilestones").mockImplementation(() => {});

        PlanningController.$onInit();
    });

    describe("$onInit", () => {
        describe("Given we were in a Project context (Backlog)", () => {
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
                expect(PlanningController.milestones.open_milestones_fully_loaded).toBeTruthy();
                expect(PlanningController.milestones.closed_milestones_fully_loaded).toBeFalsy();
                expect(PlanningController.milestones.content).toStrictEqual([
                    { id: 307, start_date: "2016-11-12T01:00:00+01:00" },
                    { id: 295, start_date: "2017-01-01T01:00:00+01:00" },
                    { id: 184, start_date: null },
                ]);
            });

            it(`when we are asked to load all milestones on init, then open and closed milestone will be retrieved`, () => {
                SharedPropertiesService.shouldloadOpenAndClosedMilestones.mockReturnValue(true);

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

                const getClosedTopMilestones = jest
                    .spyOn(rest_querier, "getClosedTopMilestones")
                    .mockImplementation((id, callback) => {
                        callback([
                            { id: 1295, start_date: "2017-01-01T01:00:00+01:00" },
                            { id: 1184, start_date: null },
                            { id: 1307, start_date: "2016-11-12T01:00:00+01:00" },
                        ]);
                        return $q.when();
                    });

                PlanningController.$onInit();
                expect(PlanningController.milestones.loading).toBeTruthy();
                $scope.$apply();

                expect(getTopMilestones).toHaveBeenCalledWith(736, expect.any(Function));
                expect(getClosedTopMilestones).toHaveBeenCalledWith(736, expect.any(Function));
                expect(PlanningController.milestones.loading).toBeFalsy();
                expect(PlanningController.milestones.open_milestones_fully_loaded).toBeTruthy();
                expect(PlanningController.milestones.closed_milestones_fully_loaded).toBeTruthy();
                expect(PlanningController.milestones.content).toStrictEqual([
                    { id: 307, start_date: "2016-11-12T01:00:00+01:00" },
                    { id: 1307, start_date: "2016-11-12T01:00:00+01:00" },
                    { id: 295, start_date: "2017-01-01T01:00:00+01:00" },
                    { id: 1295, start_date: "2017-01-01T01:00:00+01:00" },
                    { id: 184, start_date: null },
                    { id: 1184, start_date: null },
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
                "detailed-view",
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

    describe("showEditSubmilestoneModal()", () => {
        const SUB_MILESTONE_TRACKER_ID = 12;
        let did_artifact_links_change, event;
        beforeEach(() => {
            did_artifact_links_change = false;
            event = new Event("click", { cancelable: true });
        });

        const editSubMilestone = () => {
            NewTuleapArtifactModalService.showEdition.mockImplementation((a, b, c, callback) => {
                callback(SUB_MILESTONE_ID, { did_artifact_links_change });
            });

            const item = {
                artifact: { id: SUB_MILESTONE_ID, tracker: { id: SUB_MILESTONE_TRACKER_ID } },
            };
            PlanningController.showEditSubmilestoneModal(event, item);
        };

        it(`When I click on a sub-milestone's edit button,
            it will prevent default and stop propagation to avoid navigating to the Artifact view page
            and after edition, it will refresh the sub-milestone`, () => {
            const stopPropagation = jest.spyOn(event, "stopPropagation");
            const refreshSubmilestone = jest
                .spyOn(PlanningController, "refreshSubmilestone")
                .mockImplementation(noop);

            editSubMilestone();

            expect(event.defaultPrevented).toBe(true);
            expect(stopPropagation).toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showEdition).toHaveBeenCalledWith(
                user_id,
                SUB_MILESTONE_TRACKER_ID,
                SUB_MILESTONE_ID,
                expect.any(Function),
            );
            expect(refreshSubmilestone).toHaveBeenCalledWith(SUB_MILESTONE_ID);
        });

        it(`when the user changed the artifact links during edition,
            it will store a feedback message in the session storage
            and will reload the page
            so that user stories that might have been planned / unplanned from the sub-milestone are shown correctly`, () => {
            did_artifact_links_change = true;
            const reload = jest.spyOn($window.location, "reload");
            const setItem = jest.spyOn($window.sessionStorage, "setItem");

            editSubMilestone();

            expect(setItem).toHaveBeenCalledWith(SESSION_STORAGE_KEY, expect.any(String));
            expect(reload).toHaveBeenCalled();
        });
    });

    describe("showAddSubmilestoneModal() -", () => {
        let event, submilestone_type;
        beforeEach(() => {
            submilestone_type = { id: 82 };
            event = { preventDefault: jest.fn() };
            NewTuleapArtifactModalService.showCreation.mockImplementation(
                (user_id, a, b, callback) => callback(1668),
            );
        });

        it("Given any click event and a submilestone_type object, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback", () => {
            PlanningController.showAddSubmilestoneModal(event, submilestone_type);

            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(
                102,
                82,
                PlanningController.milestone_id,
                expect.any(Function),
                [],
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
                        }),
                    );

                    PlanningController.showAddSubmilestoneModal(event, submilestone_type);
                    $scope.$apply();

                    expect(MilestoneService.patchSubMilestones).toHaveBeenCalledWith(736, [1668]);
                    expect(MilestoneService.getMilestone).toHaveBeenCalledWith(
                        1668,
                        expect.any(Object),
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
                    }),
                );

                PlanningController.showAddSubmilestoneModal(event, submilestone_type);
                $scope.$apply();

                expect(MilestoneService.getMilestone).toHaveBeenCalledWith(
                    1668,
                    expect.any(Object),
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
                (user_id, a, b, callback) => callback(7488),
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
                [],
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

    describe("refreshSubmilestone()", () => {
        it(`will retrieve the sub-milestone again from the server
            and will update the milestones collection`, async () => {
            PlanningController.milestones.content = [{ id: SUB_MILESTONE_ID }];
            MilestoneService.getMilestone.mockReturnValue(
                $q.when({ results: { id: SUB_MILESTONE_ID } }),
            );

            const promise = PlanningController.refreshSubmilestone(SUB_MILESTONE_ID);

            expect(PlanningController.milestones.content).toStrictEqual([
                expect.objectContaining({ id: SUB_MILESTONE_ID, updating: true }),
            ]);
            await wrapPromise(promise);

            expect(MilestoneService.getMilestone).toHaveBeenCalledWith(SUB_MILESTONE_ID);
            expect(PlanningController.milestones.content).toStrictEqual([
                expect.objectContaining({ id: SUB_MILESTONE_ID, updating: false }),
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
