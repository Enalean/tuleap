describe("PlanningCtrl", function() {
    var $scope, $filter, $q, PlanningCtrl, BacklogItemService, BacklogService,
        MilestoneService, SharedPropertiesService,
        NewTuleapArtifactModalService, UserPreferencesService,
        BacklogItemCollectionService, MilestoneCollectionService, BacklogItemSelectedService;

    var milestone = {
            id: 592,
            resources: {
                backlog: {
                    accept: {
                        trackers: [
                            { id: 99, label: 'story' }
                        ]
                    }
                },
                content: {
                    accept: {
                        trackers: [
                            { id: 99, label: 'story' }
                        ]
                    }
                }
            },
            sub_milestone_type: { id: 66, label: 'sprints' }
        },
        initial_milestones = [{
            resources: {
                backlog: {
                    accept: {
                        trackers: [
                            { id: 98, label: 'task' }
                        ]
                    }
                },
                content: {
                    accept: {
                        trackers: [
                            { id: 98, label: 'task' }
                        ]
                    }
                }
            }
        }];

    beforeEach(function() {
        module('planning');
        module('shared-properties');

        inject(function(
            $controller,
            $rootScope,
            _$q_,
            _BacklogService_,
            _BacklogItemService_,
            _MilestoneService_,
            _NewTuleapArtifactModalService_,
            _SharedPropertiesService_,
            _UserPreferencesService_,
            _BacklogItemCollectionService_,
            _MilestoneCollectionService_,
            _BacklogItemSelectedService_
        ) {
            $scope = $rootScope.$new();
            $q     = _$q_;

            SharedPropertiesService = _SharedPropertiesService_;
            spyOn(SharedPropertiesService, 'getUserId').and.returnValue(102);
            spyOn(SharedPropertiesService, 'getProjectId').and.returnValue(736);
            spyOn(SharedPropertiesService, 'getMilestoneId').and.returnValue(592);
            spyOn(SharedPropertiesService, 'getMilestone').and.returnValue(undefined);
            spyOn(SharedPropertiesService, 'getInitialMilestones');
            spyOn(SharedPropertiesService, 'getViewMode');

            var returnPromise = function(method) {
                var self = this;
                spyOn(self, method).and.returnValue($q.defer().promise);
            };

            BacklogItemService = _BacklogItemService_;
            _([
                "getBacklogItemChildren",
                "getMilestoneBacklogItems",
                "getProjectBacklogItems",
                "getBacklogItem",
                "removeAddBacklogItemChildren"
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
                "removeAddReorderToBacklog",
                "removeAddToBacklog",
                "updateInitialEffort"
            ]).forEach(returnPromise, MilestoneService);

            NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
            spyOn(NewTuleapArtifactModalService, "showCreation");
            spyOn(NewTuleapArtifactModalService, "showEdition");

            UserPreferencesService = _UserPreferencesService_;
            spyOn(UserPreferencesService, 'setPreference').and.returnValue($q.defer().promise);

            BacklogService = _BacklogService_;

            BacklogItemCollectionService = _BacklogItemCollectionService_;
            spyOn(BacklogItemCollectionService, 'refreshBacklogItem');

            MilestoneCollectionService = _MilestoneCollectionService_;

            $filter = jasmine.createSpy("$filter").and.callFake(function() {
                return function() {};
            });

            BacklogItemSelectedService = _BacklogItemSelectedService_;

            PlanningCtrl = $controller('PlanningCtrl', {
                $filter                      : $filter,
                $q                           : $q,
                BacklogService               : BacklogService,
                BacklogItemService           : BacklogItemService,
                MilestoneService             : MilestoneService,
                NewTuleapArtifactModalService: NewTuleapArtifactModalService,
                SharedPropertiesService      : SharedPropertiesService,
                UserPreferencesService       : UserPreferencesService,
                BacklogItemCollectionService : BacklogItemCollectionService,
                BacklogItemSelectedService   : BacklogItemSelectedService
            });
        });

        installPromiseMatchers();
    });

    describe("init() -", function() {
        describe("Given we were in a Project context (Top backlog)", function() {
            beforeEach(function() {
                SharedPropertiesService.getMilestoneId.and.stub();
            });

            it("and given that no milestone was injected, when I load the controller, then the milestones will be retrieved", function() {
                SharedPropertiesService.getInitialMilestones.and.stub();
                var milestone_request = $q.defer();
                MilestoneService.getOpenMilestones.and.returnValue(milestone_request.promise);

                PlanningCtrl.init();
                milestone_request.resolve({
                    results: [
                        {
                            id: 184,
                            label: "Release v1.0"
                        }
                    ],
                    total: 1
                });
                expect(PlanningCtrl.milestones.loading).toBeTruthy();
                $scope.$apply();


                expect(MilestoneService.getOpenMilestones).toHaveBeenCalledWith(736, 50, 0, jasmine.any(Object));
                expect(PlanningCtrl.milestones.loading).toBeFalsy();
                expect(PlanningCtrl.milestones.content).toEqual([
                    {
                        id: 184,
                        label: "Release v1.0"
                    }
                ]);
            });
        });

        describe("Given we were in a Milestone context", function() {
            beforeEach(function() {
                SharedPropertiesService.getMilestoneId.and.returnValue(592);
            });

            it("and given that no milestone was injected, when I load the controller, then the submilestones will be retrieved", function() {
                SharedPropertiesService.getInitialMilestones.and.stub();
                var milestone_request    = $q.defer();
                var submilestone_request = $q.defer();
                MilestoneService.getMilestone.and.returnValue(milestone_request.promise);
                MilestoneService.getOpenSubMilestones.and.returnValue(submilestone_request.promise);

                PlanningCtrl.init();
                milestone_request.resolve({
                    results: milestone
                });
                submilestone_request.resolve({
                    results: [
                        {
                            id: 249,
                            label: "Sprint 2015-38"
                        }
                    ],
                    total: 1
                });
                expect(PlanningCtrl.milestones.loading).toBeTruthy();
                $scope.$apply();

                expect(MilestoneService.getOpenSubMilestones).toHaveBeenCalledWith(592, 50, 0, jasmine.any(Object));
                expect(PlanningCtrl.milestones.loading).toBeFalsy();
                expect(PlanningCtrl.milestones.content).toEqual([
                    {
                        id: 249,
                        label: "Sprint 2015-38"
                    }
                ]);
            });
        });

        it("Load injected milestones", inject(function() {
            SharedPropertiesService.getInitialMilestones.and.returnValue(initial_milestones);
            spyOn(PlanningCtrl, 'loadInitialMilestones').and.callThrough();

            PlanningCtrl.init();

            expect(PlanningCtrl.loadInitialMilestones).toHaveBeenCalledWith(initial_milestones);
        }));

        it("Load injected view mode", function() {
            SharedPropertiesService.getViewMode.and.returnValue('detailed-view');
            PlanningCtrl.show_closed_view_key = 'show-closed-view';

            PlanningCtrl.init();

            expect(PlanningCtrl.current_view_class).toEqual('detailed-view');
            expect(PlanningCtrl.current_closed_view_class).toEqual('show-closed-view');
        });
    });

    describe("switchViewMode() -", function() {
        it("Given a view mode, when I switch to this view mode, then the current view class will be updated and this mode will be saved as my user preference", function() {
            PlanningCtrl.switchViewMode('detailed-view');

            expect(PlanningCtrl.current_view_class).toEqual('detailed-view');
            expect(UserPreferencesService.setPreference).toHaveBeenCalledWith(
                102,
                'agiledashboard_planning_item_view_mode_736',
                'detailed-view'
            );
        });
    });

    describe("switchClosedMilestoneItemsViewMode() -", function() {
        it("Given a view mode, when I switch closed milestones' view mode, then the current view class will be updated", function() {
            PlanningCtrl.switchClosedMilestoneItemsViewMode('show-closed-view');

            expect(PlanningCtrl.current_closed_view_class).toEqual('show-closed-view');
        });
    });

    describe("displayClosedMilestones() -", function() {
        var milestone_request;
        beforeEach(function() {
            milestone_request = $q.defer();
            spyOn(PlanningCtrl, "isMilestoneContext");
            PlanningCtrl.milestones.content = [
                { id: 747 }
            ];
        });

        it("Given that we were in a project's context, when I display closed milestones, then MilestoneService will be called and the milestones collection will be updated with the closed milestones in reverse order", function() {
            PlanningCtrl.isMilestoneContext.and.returnValue(false);
            MilestoneService.getClosedMilestones.and.returnValue(milestone_request.promise);

            PlanningCtrl.displayClosedMilestones();
            expect(PlanningCtrl.milestones.loading).toBeTruthy();
            milestone_request.resolve({
                results: [
                    { id: 108 },
                    { id: 982 }
                ],
                total: 2
            });
            $scope.$apply();

            expect(PlanningCtrl.milestones.loading).toBeFalsy();
            expect(PlanningCtrl.milestones.content).toEqual([
                { id: 982 },
                { id: 747 },
                { id: 108 }
            ]);
        });

        it("Given that we were in a milestone's context, when I display closed milestones, then MilestoneService will be called and the milestones collection will be updated with the closed milestones in reverse order", function() {
            PlanningCtrl.isMilestoneContext.and.returnValue(true);
            MilestoneService.getClosedSubMilestones.and.returnValue(milestone_request.promise);

            PlanningCtrl.displayClosedMilestones();
            expect(PlanningCtrl.milestones.loading).toBeTruthy();
            milestone_request.resolve({
                results: [
                    { id: 316 },
                    { id: 960 }
                ],
                total: 2
            });
            $scope.$apply();

            expect(PlanningCtrl.milestones.loading).toBeFalsy();
            expect(PlanningCtrl.milestones.content).toEqual([
                { id: 960 },
                { id: 747 },
                { id: 316 }
            ]);
        });
    });

    describe("thereAreOpenMilestonesLoaded() -", function() {
        it("Given that open milestones have previously been loaded, when I check if open milestones have been loaded, then it will return true", function() {
            $filter.and.returnValue(function() {
                return [
                    {
                        id: 9,
                        semantic_status: 'open'
                    }
                ];
            });

            var result = PlanningCtrl.thereAreOpenMilestonesLoaded();

            expect(result).toBeTruthy();
        });

        it("Given that open milestones have never been loaded, when I check if open milestones have been loaded, then it will return false", function() {
            $filter.and.returnValue(function() { return []; });

            var result = PlanningCtrl.thereAreOpenMilestonesLoaded();

            expect(result).toBeFalsy();
        });
    });

    describe("thereAreClosedMilestonesLoaded() -", function() {
        it("Given that closed milestones have previously been loaded, when I check if closed milestones have been loaded, then it will return true", function() {
            $filter.and.returnValue(function() {
                return [
                    {
                        id: 36,
                        semantic_status: 'closed'
                    }
                ];
            });

            var result = PlanningCtrl.thereAreClosedMilestonesLoaded();

            expect(result).toBeTruthy();
        });

        it("Given that closed milestones have never been loaded, when I check if closed milestones have been loaded, then it will return false", function() {
            $filter.and.returnValue(function() { return []; });

            var result = PlanningCtrl.thereAreClosedMilestonesLoaded();

            expect(result).toBeFalsy();
        });
    });

    describe("generateMilestoneLinkUrl() -", function() {
        it("Given a milestone and a pane, when I generate a Milestone link URL, then a correct URL will be generated", function() {
            var milestone = {
                id: 71,
                planning: {
                    id: 207
                }
            };
            var pane = 'burndown';

            var result = PlanningCtrl.generateMilestoneLinkUrl(milestone, pane);

            expect(result).toEqual("?group_id=736&planning_id=207&action=show&aid=71&pane=burndown");
        });
    });

    describe("displayUserCantPrioritizeForMilestones() -", function() {
        it("Given that there were no milestones, when I check whether the user cannot prioritize items in milestones, then it will return false", function() {
            PlanningCtrl.milestones.content = [];

            var result = PlanningCtrl.displayUserCantPrioritizeForMilestones();

            expect(result).toBeFalsy();
        });

        it("Given that the user can prioritize items in milestones, when I check, then it will return true", function() {
            PlanningCtrl.milestones.content = [
                {
                    has_user_priority_change_permission: true
                }
            ];

            var result = PlanningCtrl.displayUserCantPrioritizeForMilestones();

            expect(result).toBeFalsy();
        });
    });

    describe("canShowBacklogItem() -", function() {
        it("Given an open backlog item, when I check whether we can show it, then it will return true", function() {
            var backlog_item = {
                isOpen: function() { return true; }
            };

            var result = PlanningCtrl.canShowBacklogItem(backlog_item);

            expect(result).toBeTruthy();
        });

        it("Given a closed backlog item, and we are displaying closed items, when I check whether we can show it, then it will return true", function() {
            var backlog_item = {
                isOpen: function() { return false; }
            };
            PlanningCtrl.current_closed_view_class = 'show-closed-view';

            var result = PlanningCtrl.canShowBacklogItem(backlog_item);

            expect(result).toBeTruthy();
        });

        it("Given a closed backlog item, and we are not displaying closed items, when I check whether we can show it, then it will return false", function() {
            var backlog_item = {
                isOpen: function() { return false; }
            };
            PlanningCtrl.current_closed_view_class = 'hide-closed-view';

            var result = PlanningCtrl.canShowBacklogItem(backlog_item);

            expect(result).toBeFalsy();
        });

        it("Given an item that didn't have an isOpen() method, when I check whether we can show it, then it will return true", function() {
            var backlog_item = { isOpen: undefined };

            var result = PlanningCtrl.canShowBacklogItem(backlog_item);

            expect(result).toBeTruthy();
        });
    });

    describe("showEditModal() -", function() {
        var event, item, get_request;
        beforeEach(function() {
            get_request = $q.defer();
            event   = jasmine.createSpyObj("Click event", ["preventDefault"]);
            event.which = 1;
            item = {
                artifact: {
                    id: 651,
                    tracker: {
                        id: 30
                    }
                }
            };
            NewTuleapArtifactModalService.showEdition.and.callFake(function(c, a, b, callback) {
                callback(8541);
            });
            BacklogItemCollectionService.refreshBacklogItem.and.returnValue(get_request.promise);
        });

        it("Given a left click event and an item to edit, when I show the edit modal, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback, and the callback will be called", function() {
            PlanningCtrl.showEditModal(event, item);

            expect(event.preventDefault).toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showEdition).toHaveBeenCalledWith(102, 30, 651, jasmine.any(Function));
            expect(BacklogItemCollectionService.refreshBacklogItem).toHaveBeenCalledWith(8541);
        });

        it("Given a middle click event and an item to edit, when I show the edit modal, then the event's default action will NOT be prevented and the NewTuleapArtifactModalService won't be called.", function() {
            event.which = 2;

            PlanningCtrl.showEditModal(event, item);

            expect(event.preventDefault).not.toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showEdition).not.toHaveBeenCalled();
        });

        describe("callback -", function() {
            it("Given a milestone, when the artifact modal calls its callback, then the milestone's initial effort will be updated", function() {
                var milestone = {
                    id: 38,
                    label: "Release v1.0"
                };

                PlanningCtrl.showEditModal(event, item, milestone);
                get_request.resolve();
                $scope.$apply();

                expect(MilestoneService.updateInitialEffort).toHaveBeenCalledWith(milestone);
            });
        });
    });

    describe("showEditSubmilestoneModal() -", function() {
        var event, item;
        beforeEach(function() {
            event = jasmine.createSpyObj("Click event", ["preventDefault"]);
            NewTuleapArtifactModalService.showEdition.and.callFake(function(c, a, b, callback) {
                callback(9040);
            });
        });

        it("Given a left click event and a submilestone to edit, when I show the edit modal, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback, and the callback will be called", function() {
            event.which = 1;
            item = {
                artifact: {
                    id: 9040,
                    tracker: {
                        id: 12
                    }
                }
            };
            spyOn(PlanningCtrl, "refreshSubmilestone");

            PlanningCtrl.showEditSubmilestoneModal(event, item);

            expect(event.preventDefault).toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showEdition).toHaveBeenCalledWith(102, 12, 9040, jasmine.any(Function));
            expect(PlanningCtrl.refreshSubmilestone).toHaveBeenCalledWith(9040);
        });

        it("Given a middle click event and a submilestone to edit, when I show the edit modal, then the event's default action will NOT be prevented and the NewTuleapArtifactModalService won't be called.", function() {
            event.which = 2;

            PlanningCtrl.showEditSubmilestoneModal(event, item);

            expect(event.preventDefault).not.toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showEdition).not.toHaveBeenCalled();
        });
    });

    describe("showAddSubmilestoneModal() -", function() {
        var event, submilestone_type;
        beforeEach(function() {
            submilestone_type = { id: 82 };
            event = jasmine.createSpyObj("Click event", ["preventDefault"]);
            NewTuleapArtifactModalService.showCreation.and.callFake(function(a, b, callback) {
                callback(1668);
            });
        });

        it("Given any click event and a submilestone_type object, when I show the artifact modal, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback", function() {
            PlanningCtrl.showAddSubmilestoneModal(event, submilestone_type);

            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(82, undefined, jasmine.any(Function));
        });

        describe("callback -", function() {
            var get_request;
            beforeEach(function() {
                get_request = $q.defer();
                MilestoneService.getMilestone.and.returnValue(get_request.promise);
            });

            describe("Given that we were in a milestone context", function() {
                var put_request;
                beforeEach(function() {
                    put_request = $q.defer();
                    MilestoneService.putSubMilestones.and.returnValue(put_request.promise);
                });

                it(", when the artifact modal calls its callback, then the closed milestones will be loaded and the milestones collection will be updated", function() {
                    var get_closed_milestones_request = $q.defer();
                    MilestoneService.getClosedSubMilestones.and.returnValue(get_closed_milestones_request.promise);
                    PlanningCtrl.backlog.rest_route_id = 736;
                    PlanningCtrl.milestones.content    = [
                        {
                            id             : 3118,
                            label          : "Sprint 2015-38",
                            semantic_status: "open"
                        }
                    ];

                    PlanningCtrl.showAddSubmilestoneModal(event, submilestone_type);
                    get_closed_milestones_request.resolve({
                        results: [
                            {
                                id             : 9932,
                                label          : "Sprint 2014-24",
                                semantic_status: "closed"
                            }
                        ]
                    });
                    put_request.resolve();
                    get_request.resolve({
                        results: {
                            id             : 1668,
                            label          : "Sprint 2015-20",
                            semantic_status: "open"
                        }
                    });
                    $scope.$apply();

                    expect(MilestoneService.getClosedSubMilestones).toHaveBeenCalledWith(736, 50, 0, jasmine.any(Object));
                    expect(MilestoneService.putSubMilestones).toHaveBeenCalledWith(736, [9932, 3118, 1668]);
                    expect(MilestoneService.getMilestone).toHaveBeenCalledWith(1668, jasmine.any(Object));
                    expect(PlanningCtrl.milestones.content).toEqual([
                        {
                            id             : 1668,
                            label          : "Sprint 2015-20",
                            semantic_status: "open"
                        }, {
                            id             : 9932,
                            label          : "Sprint 2014-24",
                            semantic_status: "closed"
                        }, {
                            id             : 3118,
                            label          : "Sprint 2015-38",
                            semantic_status: "open"
                        }
                    ]);
                });

                it("and the closed milestones had already been loaded, when the artifact modal calls its callback, then the closed milestones will not be loaded again", function() {
                    PlanningCtrl.milestones.closed_milestones_fully_loaded = true;
                    PlanningCtrl.backlog.rest_route_id                     = 902;
                    PlanningCtrl.milestones.content                        = [
                        {
                            id             : 7441,
                            label          : "Sprint 2015-44",
                            semantic_status: "open"
                        }, {
                            id             : 9307,
                            label          : "Sprint 2014-18",
                            semantic_status: "closed"
                        }
                    ];

                    PlanningCtrl.showAddSubmilestoneModal(event, submilestone_type);
                    put_request.resolve();
                    get_request.resolve({
                        results: {
                            id             : 1668,
                            label          : "Sprint 2015-22",
                            semantic_status: "open"
                        }
                    });
                    $scope.$apply();

                    expect(MilestoneService.getClosedSubMilestones).not.toHaveBeenCalled();
                    expect(MilestoneService.putSubMilestones).toHaveBeenCalledWith(902, [7441, 9307, 1668]);
                    expect(MilestoneService.getMilestone).toHaveBeenCalledWith(1668, jasmine.any(Object));
                });
            });

            it("Given that we were in a project context (Top Backlog), when the artifact modal calls its callback, then the MilestoneService will be called and the milestones collection will be updated", function() {
                spyOn(PlanningCtrl, "isMilestoneContext").and.returnValue(false);
                PlanningCtrl.milestones.content = [
                    {
                        id: 3118,
                        label: "Sprint 2015-38"
                    }
                ];

                PlanningCtrl.showAddSubmilestoneModal(event, submilestone_type);
                get_request.resolve({
                    results: {
                        id: 1668,
                        label: "Sprint 2015-20"
                    }
                });
                $scope.$apply();

                expect(MilestoneService.getMilestone).toHaveBeenCalledWith(1668, jasmine.any(Object));
                expect(PlanningCtrl.milestones.content).toEqual([
                    {
                        id: 1668,
                        label: "Sprint 2015-20"
                    }, {
                        id: 3118,
                        label: "Sprint 2015-38"
                    }
                ]);
            });
        });
    });

    describe("showAddItemToSubMilestoneModal() -", function() {
        var item_type, artifact, submilestone, get_backlog_item_request;

        beforeEach(function() {
            get_backlog_item_request = $q.defer();
            MilestoneService.updateInitialEffort.and.callThrough();
            BacklogItemService.getBacklogItem.and.returnValue(get_backlog_item_request.promise);
            NewTuleapArtifactModalService.showCreation.and.callFake(function(a, b, callback) {
                callback(7488);
            });
            artifact = {
                backlog_item: {
                    id: 7488
                }
            };
        });

        it("Given an item_type object and a milestone object, when I show the new artifact modal, then the NewTuleapArtifactModalService will be called with a callback", function() {
            item_type = { id: 94 };
            submilestone = { id: 196 };

            PlanningCtrl.showAddItemToSubMilestoneModal(item_type, submilestone);

            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(94, submilestone, jasmine.any(Function));
        });

        describe("callback - Given a submilestone object and an item id,", function() {
            var add_to_content_request;

            beforeEach(function() {
                add_to_content_request = $q.defer();
                item_type              = { id: 413 };
                submilestone = {
                    id: 92,
                    content: []
                };
            });

            it("when the new artifact modal calls its callback, then the artifact will be prepended to the submilestone using the REST route and will be prepended to its content attribute", function() {
                submilestone.content = [
                    { id: 9402 }
                ];
                MilestoneService.addReorderToContent.and.returnValue(add_to_content_request.promise);

                PlanningCtrl.showAddItemToSubMilestoneModal(item_type, submilestone);
                get_backlog_item_request.resolve(artifact);
                add_to_content_request.resolve();
                $scope.$apply();

                expect(MilestoneService.addReorderToContent).toHaveBeenCalledWith(92, [7488], {
                    direction: "before",
                    item_id: 9402
                });
                expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(7488);
                expect(submilestone.content).toEqual([
                    { id: 7488 },
                    { id: 9402 }
                ]);
            });

            it("and given that the submilestone's content was empty, when the new artifact modal calls its callback, then the artifact will be prepended to the submilestone using the REST route and will be prepended to its content attribute", function() {
                MilestoneService.addToContent.and.returnValue(add_to_content_request.promise);

                PlanningCtrl.showAddItemToSubMilestoneModal(item_type, submilestone);
                get_backlog_item_request.resolve(artifact);
                add_to_content_request.resolve();
                $scope.$apply();

                expect(MilestoneService.addToContent).toHaveBeenCalledWith(92, [7488]);
                expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(7488);
                expect(submilestone.content).toEqual([
                    { id: 7488 }
                ]);
            });
        });
    });

    describe("refreshSubmilestone() -", function() {
        var get_milestone_request;

        beforeEach(function() {
            get_milestone_request = $q.defer();
        });

        it("Given an existing submilestone, when I refresh it, then the submilestone will be retrieved from the server and the milestones collection will be updated", function() {
            PlanningCtrl.milestones.content = [
                { id: 9040 }
            ];
            MilestoneService.getMilestone.and.returnValue(get_milestone_request.promise);

            PlanningCtrl.refreshSubmilestone(9040);

            get_milestone_request.resolve({
                results: { id: 9040 }
            });
            expect(PlanningCtrl.milestones.content).toEqual([
                jasmine.objectContaining({ id: 9040, updating: true })
            ]);
            $scope.$apply();

            expect(MilestoneService.getMilestone).toHaveBeenCalledWith(9040);
            expect(PlanningCtrl.milestones.content).toEqual([
                jasmine.objectContaining({ id: 9040, updating: false })
            ]);
        });
    });
});
