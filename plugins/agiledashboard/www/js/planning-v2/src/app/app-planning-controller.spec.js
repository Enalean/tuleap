describe("PlanningCtrl", function() {
    var $scope, $filter, $q, PlanningCtrl, BacklogItemService, BacklogService, ProjectService,
        MilestoneService, SharedPropertiesService, TuleapArtifactModalService,
        NewTuleapArtifactModalService, UserPreferencesService, DroppedService,
        BacklogItemCollectionService, MilestoneCollectionService;

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
        }],
        initial_backlog_items = {
            backlog_items_representations: [
                { id: 7 }
            ],
            total_size: 104
        };

    beforeEach(function() {
        module('planning');
        module('shared-properties');

        inject(function(
            $controller,
            $rootScope,
            _$q_,
            _BacklogService_,
            _BacklogItemService_,
            _DroppedService_,
            _MilestoneService_,
            _NewTuleapArtifactModalService_,
            _ProjectService_,
            _SharedPropertiesService_,
            _TuleapArtifactModalService_,
            _UserPreferencesService_,
            _BacklogItemCollectionService_,
            _MilestoneCollectionService_
        ) {
            $scope = $rootScope.$new();
            $q = _$q_;

            SharedPropertiesService = _SharedPropertiesService_;
            spyOn(SharedPropertiesService, 'getUserId').and.returnValue(102);
            spyOn(SharedPropertiesService, 'getProjectId').and.returnValue(736);
            spyOn(SharedPropertiesService, 'getMilestoneId').and.returnValue(592);
            spyOn(SharedPropertiesService, 'getUseAngularNewModal').and.returnValue(true);
            spyOn(SharedPropertiesService, 'getMilestone').and.returnValue(undefined);
            spyOn(SharedPropertiesService, 'getInitialMilestones');
            spyOn(SharedPropertiesService, 'getInitialBacklogItems').and.returnValue(initial_backlog_items);
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

            ProjectService = _ProjectService_;
            _([
                "getProjectBacklog",
                "getProject",
                "removeAddToBacklog",
                "removeAddReorderToBacklog"
            ]).forEach(returnPromise, ProjectService);

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

            TuleapArtifactModalService = _TuleapArtifactModalService_;
            spyOn(TuleapArtifactModalService, "showCreateItemForm");

            NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
            spyOn(NewTuleapArtifactModalService, "showCreation");
            spyOn(NewTuleapArtifactModalService, "showEdition");

            UserPreferencesService = _UserPreferencesService_;
            spyOn(UserPreferencesService, 'setPreference').and.returnValue($q.defer().promise);

            DroppedService = _DroppedService_;
            _([
                "moveFromBacklogToSubmilestone",
                "moveFromChildrenToChildren",
                "moveFromSubmilestoneToBacklog",
                "moveFromSubmilestoneToSubmilestone",
                "reorderBacklog",
                "reorderBacklogItemChildren",
                "reorderSubmilestone"
            ]).forEach(returnPromise, DroppedService);

            BacklogService = _BacklogService_;
            spyOn(BacklogService, 'appendBacklogItems');
            spyOn(BacklogService, 'filterItems');
            spyOn(BacklogService, 'loadProjectBacklog');
            spyOn(BacklogService, 'loadMilestoneBacklog');

            BacklogItemCollectionService = _BacklogItemCollectionService_;
            spyOn(BacklogItemCollectionService, 'refreshBacklogItem');

            MilestoneCollectionService = _MilestoneCollectionService_;

            $filter = jasmine.createSpy("$filter").and.callFake(function() {
                return function() {};
            });

            PlanningCtrl = $controller('PlanningCtrl', {
                $filter                      : $filter,
                $q                           : $q,
                BacklogService               : BacklogService,
                BacklogItemService           : BacklogItemService,
                DroppedService               : DroppedService,
                MilestoneService             : MilestoneService,
                NewTuleapArtifactModalService: NewTuleapArtifactModalService,
                ProjectService               : ProjectService,
                SharedPropertiesService      : SharedPropertiesService,
                TuleapArtifactModalService   : TuleapArtifactModalService,
                UserPreferencesService       : UserPreferencesService,
                BacklogItemCollectionService : BacklogItemCollectionService
            });
        });

        installPromiseMatchers();
    });

    describe("init() -", function() {
        describe("Given we were in a Project context (Top backlog)", function() {
            beforeEach(function() {
                SharedPropertiesService.getMilestoneId.and.stub();
            });

            it(", when I load the controller, then the project's backlog will be retrieved and the backlog updated", function() {
                PlanningCtrl.init();

                expect(BacklogService.loadProjectBacklog).toHaveBeenCalledWith(736);
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
                expect(BacklogService.loadMilestoneBacklog).toHaveBeenCalledWith(milestone);
            });
        });

        it("Load injected milestone", inject(function() {
            SharedPropertiesService.getInitialMilestones.and.returnValue(initial_milestones);
            SharedPropertiesService.getMilestone.and.returnValue(milestone);
            spyOn(PlanningCtrl, 'loadBacklog').and.callThrough();

            PlanningCtrl.init();

            expect(PlanningCtrl.loadBacklog).toHaveBeenCalledWith(milestone);
            expect(BacklogService.loadMilestoneBacklog).toHaveBeenCalledWith(milestone);
        }));


        it("Load injected backlog items", inject(function() {
            SharedPropertiesService.getInitialBacklogItems.and.returnValue(initial_backlog_items);
            spyOn(PlanningCtrl, 'loadInitialBacklogItems').and.callThrough();

            PlanningCtrl.init();

            expect(PlanningCtrl.loadInitialBacklogItems).toHaveBeenCalledWith(initial_backlog_items);
            expect(PlanningCtrl.items).toEqual({
                7: { id: 7 }
            });
            expect(BacklogService.appendBacklogItems).toHaveBeenCalledWith([
                { id: 7 }
            ]);
            expect(BacklogService.filterItems).toHaveBeenCalledWith('');
        }));

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

    describe("displayBacklogItems() -", function() {
        var fetch_backlog_items_request;

        beforeEach(function() {
            fetch_backlog_items_request = $q.defer();
            spyOn(PlanningCtrl, "fetchBacklogItems").and.returnValue(fetch_backlog_items_request.promise);
            PlanningCtrl.backlog_items = {
                loading: false,
                fully_loaded: false
            };
        });

        it("Given that we aren't already loading backlog_items and all backlog_items have not yet been loaded, when I display the backlog items, then the REST route will be called and a promise will be resolved", function() {
            var promise = PlanningCtrl.displayBacklogItems();
            fetch_backlog_items_request.resolve(86);

            expect(PlanningCtrl.fetchBacklogItems).toHaveBeenCalledWith(50, 50);
            expect(promise).toBeResolved();
        });

        it("Given that we were already loading backlog_items, when I display the backlog items then the REST route won't be called again and a promise will be resolved", function() {
            PlanningCtrl.backlog_items.loading = true;

            var promise = PlanningCtrl.displayBacklogItems();

            expect(PlanningCtrl.fetchBacklogItems).not.toHaveBeenCalled();
            expect(promise).toBeResolved();
        });

        it("Given that all the backlog_items had been loaded, when I display the backlog items, then the REST route won't be called again and a promise will be resolved", function() {
            PlanningCtrl.backlog_items.fully_loaded = true;

            var promise = PlanningCtrl.displayBacklogItems();

            expect(PlanningCtrl.fetchBacklogItems).not.toHaveBeenCalled();
            expect(promise).toBeResolved();
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

    describe("fetchAllBacklogItems() -", function() {
        var fetch_backlog_items_request;

        beforeEach(function() {
            fetch_backlog_items_request = $q.defer();
            spyOn(PlanningCtrl, "fetchBacklogItems").and.returnValue(fetch_backlog_items_request.promise);
            PlanningCtrl.backlog_items = {
                loading: false,
                fully_loaded: false
            };
        });

        it("Given that we aren't already loading backlog_items and all backlog_items have not yet been loaded, when I fetch all the backlog items, then the REST route will be called and a promise will be resolved", function() {
            var promise = PlanningCtrl.fetchAllBacklogItems(50, 50);
            fetch_backlog_items_request.resolve(40);

            expect(PlanningCtrl.fetchBacklogItems).toHaveBeenCalledWith(50, 50);
            expect(promise).toBeResolved();
        });

        it("Given that there were more items than the current offset and limit, when I fetch all the backlog items, then the REST route will be called twice and a promise will be resolved", function() {
            var promise = PlanningCtrl.fetchAllBacklogItems(50, 50);
            fetch_backlog_items_request.resolve(134);

            expect(promise).toBeResolved();
            expect(PlanningCtrl.fetchBacklogItems).toHaveBeenCalledWith(50, 50);
            expect(PlanningCtrl.fetchBacklogItems).toHaveBeenCalledWith(50, 100);
            expect(PlanningCtrl.fetchBacklogItems.calls.count()).toEqual(2);
        });

        it("Given that we were already loading backlog_items, when I fetch all the backlog items, then the REST route won't be called again and a promise will be rejected", function() {
            PlanningCtrl.backlog_items.loading = true;

            var promise = PlanningCtrl.fetchAllBacklogItems(50, 50);

            expect(PlanningCtrl.fetchBacklogItems).not.toHaveBeenCalled();
            expect(promise).toBeRejected();
        });

        it("Given that all the backlog_items had been loaded, when I fetch all the backlog items, then the REST route won't be called again and a promise will be resolved", function() {
            PlanningCtrl.backlog_items.fully_loaded = true;

            var promise = PlanningCtrl.fetchAllBacklogItems(50, 50);

            expect(PlanningCtrl.fetchBacklogItems).not.toHaveBeenCalled();
            expect(promise).toBeRejected();
        });
    });

    describe("fetchBacklogItems() -", function() {
        var get_project_backlog_items_request;

        beforeEach(function() {
            get_project_backlog_items_request = $q.defer();
        });

        it("Given that we were in a project's context and given a limit and an offset, when I fetch backlog items, then the backlog will be marked as loading, BacklogItemService's Project route will be queried, its result will be appended to the backlog items and its promise will be returned", function() {
            spyOn(PlanningCtrl, "isMilestoneContext").and.returnValue(false);
            BacklogItemService.getProjectBacklogItems.and.returnValue(get_project_backlog_items_request.promise);

            var promise = PlanningCtrl.fetchBacklogItems(60, 25);
            expect(PlanningCtrl.backlog_items.loading).toBeTruthy();
            get_project_backlog_items_request.resolve({
                results: [
                    { id: 734 }
                ],
                total: 34
            });

            expect(promise).toBeResolvedWith(34);
            expect(BacklogItemService.getProjectBacklogItems).toHaveBeenCalledWith(736, 60, 25);
            expect(PlanningCtrl.items).toEqual({
                7  : { id: 7 },
                734: { id: 734 }
            });
            expect(BacklogService.appendBacklogItems).toHaveBeenCalledWith([
                { id: 734 }
            ]);
            expect(BacklogService.filterItems).toHaveBeenCalledWith('');
        });

        it("Given that we were in a milestone's context and given a limit and an offset, when I fetch backlog items, then the backlog will be marked as loading, BacklogItemService's Milestone route will be queried, its result will be appended to the backlog items and its promise will be returned", function() {
            BacklogItemService.getMilestoneBacklogItems.and.returnValue(get_project_backlog_items_request.promise);

            var promise = PlanningCtrl.fetchBacklogItems(60, 25);
            expect(PlanningCtrl.backlog_items.loading).toBeTruthy();
            get_project_backlog_items_request.resolve({
                results: [
                    { id: 836 }
                ],
                total: 85
            });

            expect(promise).toBeResolvedWith(85);
            expect(BacklogItemService.getMilestoneBacklogItems).toHaveBeenCalledWith(592, 60, 25);
            expect(PlanningCtrl.items).toEqual({
                7  : { id: 7 },
                836: { id: 836 }
            });
            expect(BacklogService.appendBacklogItems).toHaveBeenCalledWith([
                { id: 836 }
            ]);
            expect(BacklogService.filterItems).toHaveBeenCalledWith('');
        });
    });

    describe("filterBacklog() -", function() {
        var fetch_all_backlog_items_request;

        beforeEach(function() {
            fetch_all_backlog_items_request = $q.defer();
            spyOn(PlanningCtrl, "fetchAllBacklogItems").and.returnValue(fetch_all_backlog_items_request.promise);
        });

        it("Given that all items had not been loaded, when I filter the backlog, then all the backlog items will be loaded and filtered", function() {
            PlanningCtrl.filter_terms = 'flamboyantly';

            PlanningCtrl.filterBacklog();
            fetch_all_backlog_items_request.resolve(50);
            $scope.$apply();

            expect(PlanningCtrl.fetchAllBacklogItems).toHaveBeenCalledWith(50, 50);
            expect(BacklogService.filterItems).toHaveBeenCalledWith('flamboyantly');
        });

        it("Given that all items had already been loaded, when I filter the backlog, then all the backlog items will be filtered", function() {
            PlanningCtrl.filter_terms = 'Jeffersonianism';

            PlanningCtrl.filterBacklog();
            fetch_all_backlog_items_request.reject(99);
            $scope.$apply();

            expect(PlanningCtrl.fetchAllBacklogItems).toHaveBeenCalledWith(50, 50);
            expect(BacklogService.filterItems).toHaveBeenCalledWith('Jeffersonianism');
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

    describe("showAddBacklogItemModal() -", function() {
        var event, item_type, get_backlog_item_request;
        beforeEach(function() {
            get_backlog_item_request = $q.defer();
            event                    = jasmine.createSpyObj("Click event", ["preventDefault"]);
            BacklogItemService.getBacklogItem.and.returnValue(get_backlog_item_request.promise);
        });

        it("Given that we use the 'old' modal and given an event and an item_type object, when I show the new artifact modal, then the event's default action will be prevented and the TuleapArtifactModal Service will be called with a callback", function() {
            PlanningCtrl.use_angular_new_modal = false;
            SharedPropertiesService.getMilestone.and.returnValue(milestone);

            item_type = { id: 97 };
            PlanningCtrl.backlog = { rest_route_id: 504 };

            PlanningCtrl.showAddBacklogItemModal(event, item_type);

            expect(event.preventDefault).toHaveBeenCalled();
            expect(TuleapArtifactModalService.showCreateItemForm).toHaveBeenCalledWith(97, 504, jasmine.any(Function));
        });

        it("Given that we use the 'new' modal and given an event and an item_type object, when I show the new artifact modal, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback", function() {
            item_type = { id: 50 };
            SharedPropertiesService.getMilestone.and.returnValue(undefined);

            PlanningCtrl.showAddBacklogItemModal(event, item_type);

            expect(event.preventDefault).toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(50, undefined, jasmine.any(Function));
        });

        describe("callback -", function() {
            var artifact, remove_add_reorder_request;
            beforeEach(function() {
                remove_add_reorder_request = $q.defer();
                NewTuleapArtifactModalService.showCreation.and.callFake(function(a, b, callback) {
                    callback(5202);
                });
                artifact = {
                    backlog_item: {
                        id: 5202
                    }
                };
            });

            describe("Given an item id and given that we were in a project's context,", function() {
                beforeEach(function() {
                    PlanningCtrl.backlog = {
                        rest_route_id: 80,
                        rest_base_route: "projects"
                    };

                    spyOn(PlanningCtrl, "isMilestoneContext").and.returnValue(false);
                    ProjectService.removeAddReorderToBacklog.and.returnValue(remove_add_reorder_request.promise);
                });

                it("when the new artifact modal calls its callback, then the artifact will be prepended to the backlog using REST, it will be retrieved from the server, and the items and backlog_items collections will be updated", function() {
                    PlanningCtrl.backlog_items.content = [
                        { id: 3894 }
                    ];
                    PlanningCtrl.backlog_items.filtered_content = [
                        { id: 3894 }
                    ];

                    PlanningCtrl.showAddBacklogItemModal(event, item_type);
                    get_backlog_item_request.resolve(artifact);
                    remove_add_reorder_request.resolve();
                    $scope.$apply();

                    expect(ProjectService.removeAddReorderToBacklog).toHaveBeenCalledWith(undefined, 80, 5202, {
                        direction: "before",
                        item_id: 3894
                    });
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect(PlanningCtrl.items[5202]).toEqual({ id: 5202 });
                    expect(PlanningCtrl.backlog_items.content).toEqual([
                        { id: 5202 },
                        { id: 3894 }
                    ]);
                    expect(PlanningCtrl.backlog_items.filtered_content).toEqual([
                        { id: 5202 },
                        { id: 3894 }
                    ]);
                });

                it("and given that the backlog was filtered, when the new artifact modal calls its callback, then the artifact will be prepended to the backlog's content but not its filtered content", function() {
                    PlanningCtrl.filter_terms = 'needle';
                    PlanningCtrl.backlog_items.content = [
                        { id: 7453 }
                    ];
                    PlanningCtrl.backlog_items.filtered_content = [];

                    PlanningCtrl.showAddBacklogItemModal(event, item_type);
                    get_backlog_item_request.resolve(artifact);
                    remove_add_reorder_request.resolve();
                    $scope.$apply();

                    expect(PlanningCtrl.backlog_items.content).toEqual([
                        { id: 5202 },
                        { id: 7453 }
                    ]);
                    expect(PlanningCtrl.backlog_items.filtered_content).toEqual([]);
                });

                it("and given that the backlog_items collection was empty, when the new artifact modal calls its callback, then the artifact will be prepended to the backlog and prepended to the backlog_items collection", function() {
                    PlanningCtrl.backlog_items.content = [];
                    ProjectService.removeAddToBacklog.and.returnValue(remove_add_reorder_request.promise);

                    PlanningCtrl.showAddBacklogItemModal(event, item_type);
                    get_backlog_item_request.resolve(artifact);
                    remove_add_reorder_request.resolve();
                    $scope.$apply();

                    expect(ProjectService.removeAddToBacklog).toHaveBeenCalledWith(undefined, 80, 5202);
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect(PlanningCtrl.backlog_items.content).toEqual([
                        { id: 5202 }
                    ]);
                });
            });

            describe("Given an item id and given we were in a milestone's context", function() {
                beforeEach(function() {
                    PlanningCtrl.backlog = {
                        rest_route_id: 26,
                        rest_base_route: "milestones"
                    };
                });

                it(", when the new artifact modal calls its callback, then the artifact will be prepended to the backlog, it will be retrieved from the server, and the items and backlog_items collections will be updated", function() {
                    PlanningCtrl.backlog_items.content = [
                        { id: 6240 }
                    ];
                    MilestoneService.removeAddReorderToBacklog.and.returnValue(remove_add_reorder_request.promise);

                    PlanningCtrl.showAddBacklogItemModal(event, item_type);
                    get_backlog_item_request.resolve(artifact);
                    remove_add_reorder_request.resolve();
                    $scope.$apply();

                    expect(MilestoneService.removeAddReorderToBacklog).toHaveBeenCalledWith(undefined, 26, 5202, {
                        direction: "before",
                        item_id: 6240
                    });
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect(PlanningCtrl.items[5202]).toEqual({ id: 5202 });
                    expect(PlanningCtrl.backlog_items.content).toEqual([
                        { id: 5202 },
                        { id: 6240 }
                    ]);
                });

                it("and given that the scope's backlog_items was empty, when the new artifact modal calls its callback, then the artifact will be prepended to the backlog and prepended to the backlog_items collection", function() {
                    PlanningCtrl.backlog_items.content = [];
                    MilestoneService.removeAddToBacklog.and.returnValue(remove_add_reorder_request.promise);

                    PlanningCtrl.showAddBacklogItemModal(event, item_type);
                    get_backlog_item_request.resolve(artifact);
                    remove_add_reorder_request.resolve();
                    $scope.$apply();

                    expect(MilestoneService.removeAddToBacklog).toHaveBeenCalledWith(undefined, 26, 5202);
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect(PlanningCtrl.backlog_items.content).toEqual([
                        { id: 5202 }
                    ]);
                });
            });
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

            it("Given that we were in a milestone context, when the artifact modal calls its callback, then the MilestoneService will be called and the milestones collection will be updated", function() {
                var put_request = $q.defer();
                MilestoneService.putSubMilestones.and.returnValue(put_request.promise);
                PlanningCtrl.backlog.rest_route_id = 736;
                PlanningCtrl.milestones.content = [
                    {
                        id: 3118,
                        label: "Sprint 2015-38"
                    }
                ];

                PlanningCtrl.showAddSubmilestoneModal(event, submilestone_type);
                put_request.resolve();
                get_request.resolve({
                    results: {
                        id: 1668,
                        label: "Sprint 2015-20"
                    }
                });
                $scope.$apply();

                expect(MilestoneService.putSubMilestones).toHaveBeenCalledWith(736, [3118, 1668]);
                expect(MilestoneService.getMilestone).toHaveBeenCalledWith(1668, 50, 0, jasmine.any(Object));
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

                expect(MilestoneService.getMilestone).toHaveBeenCalledWith(1668, 50, 0, jasmine.any(Object));
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

                expect(MilestoneService.addReorderToContent).toHaveBeenCalledWith(92, 7488, {
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

                expect(MilestoneService.addToContent).toHaveBeenCalledWith(92, 7488);
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

    describe("isItemDroppable() -", function() {
        var sourceNodeScope, destNodeScope;
        beforeEach(function() {
            sourceNodeScope = {
                $element: {
                    attr: function() {
                        return 'trackerId2';
                    }
                }
            };
            destNodeScope = {
                $element: {
                    attr: function() {
                        return 'trackerId10|trackerId2';
                    }
                }
            };
        });

        describe("Given a source element and a destination element", function() {
            it("and given the source element's type was in the destination's accepted types, when I check whether the source element is droppable then it will return true", function() {
                var result = PlanningCtrl.treeOptions.accept(sourceNodeScope, destNodeScope);

                expect(result).toBeTruthy();
            });

            it("and given the source element's type wasn't in the destination's accepted types, when I check whether the source element is droppable, then it will return false", function() {
                destNodeScope.$element.attr = function() {
                    return 'trackerId10';
                };

                var result = PlanningCtrl.treeOptions.accept(sourceNodeScope, destNodeScope);

                expect(result).toBeFalsy();
            });

            it("and given the destination had a 'data-nodrag' attribute set to true, when I check whether the source element is droppable, then it will return undefined", function() {
                destNodeScope.$element.attr = function(attr_name) {
                    if (attr_name === 'data-nodrag')  {
                        return 'true';
                    }
                };

                var result = PlanningCtrl.treeOptions.accept(sourceNodeScope, destNodeScope);

                expect(result).toBeUndefined();
            });
        });
    });

    describe("dropped() -", function() {
        describe("Given an event containing a source list element, a destination list element and a dropped item's id,", function() {
            var event, compared_to, move_request, dropped_item_id;
            beforeEach(function() {
                dropped_item_id = 48;
                event = {
                    source: {
                        nodesScope: {
                            $id: 32,
                            $element: {
                                hasClass: function() {}
                            }
                        },
                        nodeScope: {
                            $modelValue: {
                                id: dropped_item_id
                            }
                        }
                    },
                    dest: {
                        index: 0,
                        nodesScope: {
                            $id: 20,
                            $element: {
                                hasClass: function() {}
                            },
                            $modelValue: [
                                { id: dropped_item_id },
                                { id: 53 }
                            ]
                        }
                    }
                };
                compared_to = {
                    direction: 'before',
                    item_id: 53
                };

                move_request = $q.defer();
            });

            describe("reorder an item in the same list - ", function() {
                var reorder_request;
                beforeEach(function() {
                    reorder_request = $q.defer();
                    event.source.nodesScope.$id = event.dest.nodesScope.$id;
                });

                it("when I reorder an item in the backlog, then the item will be reordered using DroppedService", function() {
                    function hasClass(name) {
                        return (name === 'backlog');
                    }

                    event.source.nodesScope.$element.hasClass = hasClass;
                    event.dest.nodesScope.$element.hasClass   = hasClass;
                    DroppedService.reorderBacklog.and.returnValue(reorder_request.promise);

                    PlanningCtrl.treeOptions.dropped(event);
                    reorder_request.resolve();
                    $scope.$apply();

                    expect(DroppedService.reorderBacklog).toHaveBeenCalledWith(dropped_item_id, compared_to, PlanningCtrl.backlog);
                });

                it("when I reorder an item in a submilestone (e.g. Sprint), then the item will be reordered using DroppedService", function() {
                    function hasClass(name) {
                        return (name === 'submilestone');
                    }

                    event.source.nodesScope.$element.hasClass = hasClass;
                    event.dest.nodesScope.$element.hasClass   = hasClass;
                    event.dest.nodesScope.$element.attr = function() { return 34; };
                    DroppedService.reorderSubmilestone.and.returnValue(reorder_request.promise);

                    PlanningCtrl.treeOptions.dropped(event);
                    reorder_request.resolve();
                    $scope.$apply();

                    expect(DroppedService.reorderSubmilestone).toHaveBeenCalledWith(dropped_item_id, compared_to, 34);
                });

                it("when I reorder children of an item (e.g. User stories in an Epic), then the children will be reordered using DroppedService", function() {
                    function hasClass(name) {
                        return (name === 'backlog-item-children');
                    }
                    event.source.nodesScope.$element.hasClass = hasClass;
                    event.dest.nodesScope.$element.hasClass   = hasClass;
                    event.dest.nodesScope.$element.attr = function() { return 67; };
                    DroppedService.reorderBacklogItemChildren.and.returnValue(reorder_request.promise);

                    PlanningCtrl.treeOptions.dropped(event);
                    reorder_request.resolve();
                    $scope.$apply();

                    expect(DroppedService.reorderBacklogItemChildren).toHaveBeenCalledWith(dropped_item_id, compared_to, 67);
                });
            });

            it("when I move an item from the backlog to a submilestone (e.g. to a Sprint), then the item will be moved using DroppedService and the submilestone's initial effort will be updated", function() {
                event.source.nodesScope.$element.hasClass = function(name) {
                    return (name === 'backlog');
                };
                event.dest.nodesScope.$element.hasClass = function(name) {
                    return (name === 'submilestone');
                };
                event.dest.nodesScope.$element.attr = function() { return 80; };
                DroppedService.moveFromBacklogToSubmilestone.and.returnValue(move_request.promise);
                PlanningCtrl.backlog_items.content = [
                    { id: 17 },
                    { id: dropped_item_id }
                ];
                var destination_milestone = { id: 80 };
                PlanningCtrl.milestones.content = [
                    destination_milestone
                ];

                PlanningCtrl.treeOptions.dropped(event);
                move_request.resolve();
                $scope.$apply();

                expect(DroppedService.moveFromBacklogToSubmilestone).toHaveBeenCalledWith(dropped_item_id, compared_to, 80);
                expect(PlanningCtrl.backlog_items.content).toEqual([
                    { id: 17 }
                ]);
                expect(MilestoneService.updateInitialEffort).toHaveBeenCalledWith(destination_milestone);
            });

            describe("when I move a child from an item to another (e.g. move a User story from an Epic to another Epic),", function() {
                beforeEach(function() {
                    event.source.nodesScope.$element.hasClass = function(name) {
                        return (name === 'backlog-item-children');
                    };
                    event.dest.nodesScope.$element.hasClass = function(name) {
                        return (name === 'backlog-item-children');
                    };
                    event.source.nodesScope.$element.attr = function() { return 54; };
                    event.dest.nodesScope.$element.attr   = function() { return 21; };
                    PlanningCtrl.items[54] = { updating: false };
                    PlanningCtrl.items[21] = { updating: false };
                    DroppedService.moveFromChildrenToChildren.and.returnValue(move_request.promise);
                });

                it("then the item will be moved using DroppedService and the source parent will be collapsed", function() {
                    PlanningCtrl.treeOptions.dropped(event);

                    expect(PlanningCtrl.items[54].updating).toBeTruthy();
                    expect(PlanningCtrl.items[21].updating).toBeTruthy();
                    move_request.resolve();
                    $scope.$apply();

                    expect(DroppedService.moveFromChildrenToChildren).toHaveBeenCalledWith(dropped_item_id, compared_to, 54, 21);
                    expect(BacklogItemCollectionService.refreshBacklogItem).toHaveBeenCalledWith(54);
                    expect(BacklogItemCollectionService.refreshBacklogItem).toHaveBeenCalledWith(21);
                });

                it("then the source parent will be collapsed", function() {
                    event.sourceParent = {
                        hasChild: function() { return false; },
                        collapse: jasmine.createSpy("collapse")
                    };

                    PlanningCtrl.treeOptions.dropped(event);

                    expect(event.sourceParent.collapse).toHaveBeenCalled();
                });

                it("and given that the destination parent was collapsed, then the child will be removed from it", function() {
                    var spy_remove = jasmine.createSpy("remove");
                    _.extend(event.dest.nodesScope, {
                        collapsed: true,
                        childNodes: function() {
                            return [
                                {
                                    remove: spy_remove
                                }
                            ];
                        },
                        $nodeScope: {
                            $modelValue: {
                                has_children: true,
                                children: {
                                    loaded: false
                                }
                            }
                        }
                    });

                    PlanningCtrl.treeOptions.dropped(event);

                    expect(spy_remove).toHaveBeenCalled();
                });
            });

            it("when I move an item from a submilestone to the backlog (e.g. from a Sprint), then the item will be moved using DroppedService and the submilestone's initial effort will be updated", function() {
                event.source.nodesScope.$element.hasClass = function(name) {
                    return (name === 'submilestone');
                };
                event.dest.nodesScope.$element.hasClass = function(name) {
                    return (name === 'backlog');
                };
                event.source.nodesScope.$element.attr = function() { return 33; };
                DroppedService.moveFromSubmilestoneToBacklog.and.returnValue(move_request.promise);
                var source_milestone = { id: 33 };
                PlanningCtrl.milestones.content = [
                    source_milestone
                ];

                PlanningCtrl.treeOptions.dropped(event);
                move_request.resolve();
                $scope.$apply();

                expect(DroppedService.moveFromSubmilestoneToBacklog).toHaveBeenCalledWith(dropped_item_id, compared_to, 33, jasmine.any(Object));
                expect(MilestoneService.updateInitialEffort).toHaveBeenCalledWith(source_milestone);
            });

            it("when I move an item from a submilestone to another submilestone (e.g. from Sprint 1 to Sprint 2), then the item will be moved using DroppedService and both submilestones' initial effort will be updated", function() {
                function hasClass(name) {
                    return (name === 'submilestone');
                }
                event.source.nodesScope.$element.hasClass = hasClass;
                event.dest.nodesScope.$element.hasClass   = hasClass;
                event.source.nodesScope.$element.attr = function() { return 56; };
                event.dest.nodesScope.$element.attr   = function() { return 74; };
                DroppedService.moveFromSubmilestoneToSubmilestone.and.returnValue(move_request.promise);
                var source_milestone      = { id: 56 };
                var destination_milestone = { id: 74 };
                PlanningCtrl.milestones.content = [
                    source_milestone,
                    destination_milestone
                ];

                PlanningCtrl.treeOptions.dropped(event);
                move_request.resolve();
                $scope.$apply();

                expect(DroppedService.moveFromSubmilestoneToSubmilestone).toHaveBeenCalledWith(dropped_item_id, compared_to, 56, 74);
                expect(MilestoneService.updateInitialEffort).toHaveBeenCalledWith(source_milestone);
                expect(MilestoneService.updateInitialEffort).toHaveBeenCalledWith(destination_milestone);
            });
        });
    });
});
