import _ from "lodash";
import angular from "angular";
import "angular-mocks";

import planning_module from "./app.js";
import BaseController from "./planning-controller.js";

describe("PlanningController -", () => {
    let $scope,
        $filter,
        $controller,
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

    const milestone = {
        id: 592,
        resources: {
            backlog: {
                accept: {
                    trackers: [{ id: 99, label: "story" }],
                },
            },
            content: {
                accept: {
                    trackers: [{ id: 99, label: "story" }],
                },
            },
        },
        sub_milestone_type: { id: 66, label: "sprints" },
    };
    const initial_milestones = {
        milestones_representations: [
            {
                resources: {
                    backlog: {
                        accept: {
                            trackers: [{ id: 98, label: "task" }],
                        },
                    },
                    content: {
                        accept: {
                            trackers: [{ id: 98, label: "task" }],
                        },
                    },
                },
            },
        ],
    };

    beforeEach(() => {
        angular.mock.module(planning_module);

        angular.mock.inject(function (
            _$controller_,
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
            jest.spyOn(SharedPropertiesService, "getUserId").mockReturnValue(102);
            jest.spyOn(SharedPropertiesService, "getProjectId").mockReturnValue(736);
            jest.spyOn(SharedPropertiesService, "getMilestoneId").mockReturnValue(592);
            jest.spyOn(SharedPropertiesService, "getMilestone").mockReturnValue(undefined);
            jest.spyOn(
                SharedPropertiesService,
                "getInitialMilestones"
            ).mockImplementation(() => {});
            jest.spyOn(SharedPropertiesService, "getViewMode").mockImplementation(() => {});

            var returnPromise = function (method) {
                var self = this;
                jest.spyOn(self, method).mockReturnValue($q.defer().promise);
            };

            BacklogItemService = _BacklogItemService_;
            _([
                "getBacklogItemChildren",
                "getMilestoneBacklogItems",
                "getProjectBacklogItems",
                "getBacklogItem",
                "removeAddBacklogItemChildren",
            ]).forEach(returnPromise, BacklogItemService);

            MilestoneService = _MilestoneService_;
            _([
                "addReorderToContent",
                "addToContent",
                "augmentMilestone",
                "defineAllowedBacklogItemTypes",
                "getClosedMilestones",
                "getClosedSubMilestones",
                "getMilestone",
                "getOpenMilestones",
                "getOpenSubMilestones",
                "putSubMilestones",
                "patchSubMilestones",
                "removeAddReorderToBacklog",
                "removeAddToBacklog",
            ]).forEach(returnPromise, MilestoneService);

            NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
            jest.spyOn(NewTuleapArtifactModalService, "showCreation").mockImplementation(() => {});
            jest.spyOn(NewTuleapArtifactModalService, "showEdition").mockImplementation(() => {});

            UserPreferencesService = _UserPreferencesService_;
            jest.spyOn(UserPreferencesService, "setPreference").mockReturnValue($q.defer().promise);

            BacklogService = _BacklogService_;

            BacklogItemCollectionService = _BacklogItemCollectionService_;
            jest.spyOn(
                BacklogItemCollectionService,
                "refreshBacklogItem"
            ).mockImplementation(() => {});

            $filter = jest.fn(function () {
                return function () {};
            });

            ItemAnimatorService = _ItemAnimatorService_;
            BacklogItemSelectedService = _BacklogItemSelectedService_;
            $controller = _$controller_;
        });

        jest.spyOn(ItemAnimatorService, "animateCreated").mockImplementation(() => {});

        PlanningController = $controller(BaseController, {
            $filter,
            $q,
            BacklogService,
            BacklogItemService,
            MilestoneService,
            NewTuleapArtifactModalService,
            SharedPropertiesService,
            UserPreferencesService,
            BacklogItemCollectionService,
            BacklogItemSelectedService,
        });
        PlanningController.$onInit();
    });

    describe("$onInit() -", () => {
        describe("Given we were in a Project context (Top backlog)", () => {
            beforeEach(() => {
                SharedPropertiesService.getMilestoneId.mockImplementation(() => {});
            });

            it("and given that no milestone was injected, when I load the controller, then the milestones will be retrieved", function () {
                SharedPropertiesService.getInitialMilestones.mockImplementation(() => {});
                var milestone_request = $q.defer();
                MilestoneService.getOpenMilestones.mockReturnValue(milestone_request.promise);

                PlanningController.$onInit();
                milestone_request.resolve({
                    results: [
                        {
                            id: 184,
                            label: "Release v1.0",
                        },
                    ],
                    total: 1,
                });
                expect(PlanningController.milestones.loading).toBeTruthy();
                $scope.$apply();

                expect(MilestoneService.getOpenMilestones).toHaveBeenCalledWith(
                    736,
                    50,
                    0,
                    expect.any(Object)
                );
                expect(PlanningController.milestones.loading).toBeFalsy();
                expect(PlanningController.milestones.content).toEqual([
                    {
                        id: 184,
                        label: "Release v1.0",
                    },
                ]);
            });
        });

        describe("Given we were in a Milestone context", () => {
            beforeEach(() => {
                SharedPropertiesService.getMilestoneId.mockReturnValue(592);
            });

            it("and given that no milestone was injected, when I load the controller, then the submilestones will be retrieved", () => {
                SharedPropertiesService.getInitialMilestones.mockImplementation(() => {});
                var milestone_request = $q.defer();
                var submilestone_request = $q.defer();
                MilestoneService.getMilestone.mockReturnValue(milestone_request.promise);
                MilestoneService.getOpenSubMilestones.mockReturnValue(submilestone_request.promise);

                PlanningController.$onInit();
                milestone_request.resolve({
                    results: milestone,
                });
                submilestone_request.resolve({
                    results: [
                        {
                            id: 249,
                            label: "Sprint 2015-38",
                        },
                    ],
                    total: 1,
                });
                expect(PlanningController.milestones.loading).toBeTruthy();
                $scope.$apply();

                expect(MilestoneService.getOpenSubMilestones).toHaveBeenCalledWith(
                    592,
                    50,
                    0,
                    expect.any(Object)
                );
                expect(PlanningController.milestones.loading).toBeFalsy();
                expect(PlanningController.milestones.content).toEqual([
                    {
                        id: 249,
                        label: "Sprint 2015-38",
                    },
                ]);
            });
        });

        it(
            "Load injected milestones",
            angular.mock.inject(() => {
                SharedPropertiesService.getInitialMilestones.mockReturnValue(initial_milestones);
                jest.spyOn(PlanningController, "loadInitialMilestones");

                PlanningController.$onInit();

                expect(PlanningController.loadInitialMilestones).toHaveBeenCalledWith(
                    initial_milestones
                );
            })
        );

        it("Load injected view mode", () => {
            SharedPropertiesService.getViewMode.mockReturnValue("detailed-view");
            PlanningController.show_closed_view_key = "show-closed-view";

            PlanningController.$onInit();

            expect(PlanningController.current_view_class).toEqual("detailed-view");
            expect(PlanningController.current_closed_view_class).toEqual("show-closed-view");
        });
    });

    describe("switchViewMode() -", function () {
        it("Given a view mode, when I switch to this view mode, then the current view class will be updated and this mode will be saved as my user preference", function () {
            PlanningController.switchViewMode("detailed-view");

            expect(PlanningController.current_view_class).toEqual("detailed-view");
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

            expect(PlanningController.current_closed_view_class).toEqual("show-closed-view");
        });
    });

    describe("displayClosedMilestones() -", function () {
        var milestone_request;
        beforeEach(function () {
            milestone_request = $q.defer();
            jest.spyOn(PlanningController, "isMilestoneContext").mockImplementation(() => {});
            PlanningController.milestones.content = [{ id: 747 }];
        });

        it("Given that we were in a project's context, when I display closed milestones, then MilestoneService will be called and the milestones collection will be updated with the closed milestones in reverse order", function () {
            PlanningController.isMilestoneContext.mockReturnValue(false);
            MilestoneService.getClosedMilestones.mockReturnValue(milestone_request.promise);

            PlanningController.displayClosedMilestones();
            expect(PlanningController.milestones.loading).toBeTruthy();
            milestone_request.resolve({
                results: [{ id: 108 }, { id: 982 }],
                total: 2,
            });
            $scope.$apply();

            expect(PlanningController.milestones.loading).toBeFalsy();
            expect(PlanningController.milestones.content).toEqual([
                { id: 982 },
                { id: 747 },
                { id: 108 },
            ]);
        });

        it("Given that we were in a milestone's context, when I display closed milestones, then MilestoneService will be called and the milestones collection will be updated with the closed milestones in reverse order", function () {
            PlanningController.isMilestoneContext.mockReturnValue(true);
            MilestoneService.getClosedSubMilestones.mockReturnValue(milestone_request.promise);

            PlanningController.displayClosedMilestones();
            expect(PlanningController.milestones.loading).toBeTruthy();
            milestone_request.resolve({
                results: [{ id: 316 }, { id: 960 }],
                total: 2,
            });
            $scope.$apply();

            expect(PlanningController.milestones.loading).toBeFalsy();
            expect(PlanningController.milestones.content).toEqual([
                { id: 960 },
                { id: 747 },
                { id: 316 },
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

            expect(result).toEqual(
                "?group_id=736&planning_id=207&action=show&aid=71&pane=burndown"
            );
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
            expect(BacklogItemCollectionService.refreshBacklogItem).toHaveBeenCalledWith(8541);
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
                102,
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
            NewTuleapArtifactModalService.showCreation.mockImplementation((a, b, callback) =>
                callback(1668)
            );
        });

        it("Given any click event and a submilestone_type object, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback", () => {
            PlanningController.showAddSubmilestoneModal(event, submilestone_type);

            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(
                82,
                PlanningController.milestone_id,
                expect.any(Function)
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
                    expect(PlanningController.milestones.content).toEqual([
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
                expect(PlanningController.milestones.content).toEqual([
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
            NewTuleapArtifactModalService.showCreation.mockImplementation((a, b, callback) =>
                callback(7488)
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
                94,
                null,
                expect.any(Function)
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
                expect(submilestone.content).toEqual([{ id: 7488 }, { id: 9402 }]);
            });

            it("and given that the submilestone's content was empty, when the artifact modal calls its callback, then the artifact will be prepended to the submilestone using the REST route and will be prepended to its content attribute", () => {
                BacklogItemService.getBacklogItem.mockReturnValue($q.when(artifact));
                MilestoneService.addToContent.mockReturnValue($q.when());

                PlanningController.showAddItemToSubMilestoneModal(item_type, submilestone);
                $scope.$apply();

                expect(MilestoneService.addToContent).toHaveBeenCalledWith(92, [7488]);
                expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(7488);
                expect(submilestone.content).toEqual([{ id: 7488 }]);
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
            expect(PlanningController.milestones.content).toEqual([
                expect.objectContaining({ id: 9040, updating: true }),
            ]);
            $scope.$apply();

            expect(MilestoneService.getMilestone).toHaveBeenCalledWith(9040);
            expect(PlanningController.milestones.content).toEqual([
                expect.objectContaining({ id: 9040, updating: false }),
            ]);
        });
    });
});
