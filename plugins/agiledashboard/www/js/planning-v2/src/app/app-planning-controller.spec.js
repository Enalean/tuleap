describe("PlanningCtrl", function() {
    var $scope, $filter, $q, PlanningCtrl, BacklogItemService, BacklogItemFactory, ProjectService, MilestoneService,
        SharedPropertiesService, TuleapArtifactModalService, NewTuleapArtifactModalService,
        UserPreferencesService, DroppedService, deferred, second_deferred;

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
            }
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
            _BacklogItemFactory_,
            _BacklogItemService_,
            _DroppedService_,
            _MilestoneService_,
            _NewTuleapArtifactModalService_,
            _ProjectService_,
            _SharedPropertiesService_,
            _TuleapArtifactModalService_,
            _UserPreferencesService_
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

            BacklogItemFactory = _BacklogItemFactory_;
            spyOn(BacklogItemFactory, "augment");

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

            $filter = jasmine.createSpy("$filter").and.callFake(function() {
                return function() {};
            });

            PlanningCtrl = $controller('PlanningCtrl', {
                $filter                      : $filter,
                $q                           : $q,
                $scope                       : $scope,
                BacklogItemFactory           : BacklogItemFactory,
                BacklogItemService           : BacklogItemService,
                DroppedService               : DroppedService,
                MilestoneService             : MilestoneService,
                NewTuleapArtifactModalService: NewTuleapArtifactModalService,
                ProjectService               : ProjectService,
                SharedPropertiesService      : SharedPropertiesService,
                TuleapArtifactModalService   : TuleapArtifactModalService,
                UserPreferencesService       : UserPreferencesService
            });
        });
        deferred = $q.defer();
        second_deferred = $q.defer();

        installPromiseMatchers();
    });

    describe("init() -", function() {
        beforeEach(function() {
            spyOn($scope, 'appendBacklogItems');
        });

        describe("Given we were in a Project context (Top backlog)", function() {
            beforeEach(function() {
                SharedPropertiesService.getMilestoneId.and.stub();
            });

            it(", when I load the controller, then the project's backlog will be retrieved and the scope updated", function() {
                var project_request         = $q.defer();
                var project_backlog_request = $q.defer();
                ProjectService.getProject.and.returnValue(project_request.promise);
                ProjectService.getProjectBacklog.and.returnValue(project_backlog_request.promise);

                PlanningCtrl.init();
                project_request.resolve({
                    data: {
                        additional_informations: {
                            agiledashboard: {
                                root_planning: {
                                    milestone_tracker: {
                                        id: 218,
                                        label: "Releases"
                                    }
                                }
                            }
                        }
                    }
                });
                project_backlog_request.resolve({
                    allowed_backlog_item_types: {
                        content: [{
                            id: 5,
                            label: "Epic"
                        }]
                    },
                    has_user_priority_change_permission: false
                });
                $scope.$apply();

                expect($scope.backlog.rest_base_route).toEqual('projects');
                expect($scope.backlog.rest_route_id).toEqual(736);
                expect(ProjectService.getProject).toHaveBeenCalledWith(736);
                expect($scope.submilestone_type).toEqual({
                    id: 218,
                    label: "Releases"
                });
                expect(ProjectService.getProjectBacklog).toHaveBeenCalledWith(736);
                expect($scope.backlog.accepted_types).toEqual({
                    content: [{
                        id: 5,
                        label: "Epic"
                    }]
                });
                expect($scope.backlog.user_can_move_cards).toEqual(false);
            });

            it("and given that no milestone was injected, when I load the controller, then the milestones will be retrieved and the scope updated", function() {
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
                expect($scope.milestones.loading).toBeTruthy();
                $scope.$apply();


                expect(MilestoneService.getOpenMilestones).toHaveBeenCalledWith(736, 50, 0, jasmine.any(Object));
                expect($scope.milestones.loading).toBeFalsy();
                expect($scope.milestones.content).toEqual([
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

            it("and given that no milestone was injected, when I load the controller, then the submilestones will be retrieved and the scope updated", function() {
                SharedPropertiesService.getInitialMilestones.and.stub();
                var submilestone_request = $q.defer();
                MilestoneService.getOpenSubMilestones.and.returnValue(submilestone_request.promise);

                PlanningCtrl.init();
                submilestone_request.resolve({
                    results: [
                        {
                            id: 249,
                            label: "Sprint 2015-38"
                        }
                    ],
                    total: 1
                });
                expect($scope.milestones.loading).toBeTruthy();
                $scope.$apply();

                expect(MilestoneService.getOpenSubMilestones).toHaveBeenCalledWith(592, 50, 0, jasmine.any(Object));
                expect($scope.milestones.loading).toBeFalsy();
                expect($scope.milestones.content).toEqual([
                {
                    id: 249,
                    label: "Sprint 2015-38"
                }
                ]);
            });
        });

        it("Load injected milestone", inject(function() {
            SharedPropertiesService.getInitialMilestones.and.returnValue(initial_milestones);
            SharedPropertiesService.getMilestone.and.returnValue(milestone);
            spyOn(PlanningCtrl, 'loadBacklog').and.callThrough();

            PlanningCtrl.init();

            expect(PlanningCtrl.loadBacklog).toHaveBeenCalledWith(milestone);
        }));


        it("Load injected backlog items", inject(function() {
            SharedPropertiesService.getInitialBacklogItems.and.returnValue(initial_backlog_items);
            spyOn(PlanningCtrl, 'loadInitialBacklogItems').and.callThrough();

            PlanningCtrl.init();

            expect(PlanningCtrl.loadInitialBacklogItems).toHaveBeenCalledWith(initial_backlog_items);
            expect($scope.appendBacklogItems).toHaveBeenCalledWith([{ id: 7 }]);
        }));

        it("Load injected milestones", inject(function() {
            SharedPropertiesService.getInitialMilestones.and.returnValue(initial_milestones);
            spyOn(PlanningCtrl, 'loadInitialMilestones').and.callThrough();

            PlanningCtrl.init();

            expect(PlanningCtrl.loadInitialMilestones).toHaveBeenCalledWith(initial_milestones);
        }));

        it("Load injected view mode", function() {
            SharedPropertiesService.getViewMode.and.returnValue('detailed-view');

            PlanningCtrl.init();

            expect($scope.current_view_class).toEqual('detailed-view');
        });
    });

    describe("switchViewMode() -", function() {
        it("Given a view mode, when I switch to this view mode, then the scope will be updated and this mode will be saved as my user preference", function() {
            $scope.switchViewMode('detailed-view');

            expect($scope.current_view_class).toEqual('detailed-view');
            expect(UserPreferencesService.setPreference).toHaveBeenCalledWith(
                102,
                'agiledashboard_planning_item_view_mode_736',
                'detailed-view'
            );
        });
    });

    describe("switchClosedMilestoneItemsViewMode() -", function() {
        it("Given a view mode, when I switch closed milestones' view mode, then the scope will be updated", function() {
            $scope.switchClosedMilestoneItemsViewMode('show-closed-view');

            expect($scope.current_closed_view_class).toEqual('show-closed-view');
        });
    });

    describe("displayBacklogItems() -", function() {
        beforeEach(function() {
            spyOn($scope, "fetchBacklogItems").and.returnValue(deferred.promise);
            $scope.backlog_items = {
                loading: false,
                fully_loaded: false
            };
        });

        it("Given that we aren't already loading backlog_items and all backlog_items have not yet been loaded, when I display the backlog items, then the REST route will be called and a promise will be resolved", function() {
            var promise = $scope.displayBacklogItems();
            deferred.resolve(86);

            expect($scope.fetchBacklogItems).toHaveBeenCalledWith(50, 50);
            expect(promise).toBeResolved();
        });

        it("Given that we were already loading backlog_items, when I display the backlog items then the REST route won't be called again and a promise will be resolved", function() {
            $scope.backlog_items.loading = true;

            var promise = $scope.displayBacklogItems();

            expect($scope.fetchBacklogItems).not.toHaveBeenCalled();
            expect(promise).toBeResolved();
        });

        it("Given that all the backlog_items had been loaded, when I display the backlog items, then the REST route won't be called again and a promise will be resolved", function() {
            $scope.backlog_items.fully_loaded = true;

            var promise = $scope.displayBacklogItems();

            expect($scope.fetchBacklogItems).not.toHaveBeenCalled();
            expect(promise).toBeResolved();
        });
    });

    describe("displayClosedMilestones() -", function() {
        var milestone_request;
        beforeEach(function() {
            milestone_request = $q.defer();
            spyOn(PlanningCtrl, "isMilestoneContext");
            $scope.milestones.content = [
                { id: 747 }
            ];
        });

        it("Given that we were in a project's context, when I display closed milestones, then MilestoneService will be called and the scope will be updated with the closed milestones in reverse order", function() {
            PlanningCtrl.isMilestoneContext.and.returnValue(false);
            MilestoneService.getClosedMilestones.and.returnValue(milestone_request.promise);

            $scope.displayClosedMilestones();
            expect($scope.milestones.loading).toBeTruthy();
            milestone_request.resolve({
                results: [
                    { id: 108 },
                    { id: 982 }
                ],
                total: 2
            });
            $scope.$apply();

            expect($scope.milestones.loading).toBeFalsy();
            expect($scope.milestones.content).toEqual([
                { id: 982 },
                { id: 747 },
                { id: 108 }
            ]);
        });

        it("Given that we were in a milestone's context, when I display closed milestones, then MilestoneService will be called and the scope will be updated with the closed milestones in reverse order", function() {
            PlanningCtrl.isMilestoneContext.and.returnValue(true);
            MilestoneService.getClosedSubMilestones.and.returnValue(milestone_request.promise);

            $scope.displayClosedMilestones();
            expect($scope.milestones.loading).toBeTruthy();
            milestone_request.resolve({
                results: [
                    { id: 316 },
                    { id: 960 }
                ],
                total: 2
            });
            $scope.$apply();

            expect($scope.milestones.loading).toBeFalsy();
            expect($scope.milestones.content).toEqual([
                { id: 960 },
                { id: 747 },
                { id: 316 }
            ]);
        });
    });

    describe("fetchAllBacklogItems() -", function() {
        beforeEach(function() {
            spyOn($scope, "fetchBacklogItems").and.returnValue(deferred.promise);
            $scope.backlog_items = {
                loading: false,
                fully_loaded: false
            };
        });

        it("Given that we aren't already loading backlog_items and all backlog_items have not yet been loaded, when I fetch all the backlog items, then the REST route will be called and a promise will be resolved", function() {
            var promise = $scope.fetchAllBacklogItems(50, 50);
            deferred.resolve(40);

            expect($scope.fetchBacklogItems).toHaveBeenCalledWith(50, 50);
            expect(promise).toBeResolved();
        });

        it("Given that there were more items than the current offset and limit, when I fetch all the backlog items, then the REST route will be called twice and a promise will be resolved", function() {
            var promise = $scope.fetchAllBacklogItems(50, 50);
            deferred.resolve(134);
            $scope.$apply();

            expect($scope.fetchBacklogItems).toHaveBeenCalledWith(50, 50);
            expect($scope.fetchBacklogItems).toHaveBeenCalledWith(50, 100);
            expect($scope.fetchBacklogItems.calls.count()).toEqual(2);
            expect(promise).toBeResolved();
        });

        it("Given that we were already loading backlog_items, when I fetch all the backlog items, then the REST route won't be called again and a promise will be rejected", function() {
            $scope.backlog_items.loading = true;

            var promise = $scope.fetchAllBacklogItems(50, 50);

            expect($scope.fetchBacklogItems).not.toHaveBeenCalled();
            expect(promise).toBeRejected();
        });

        it("Given that all the backlog_items had been loaded, when I fetch all the backlog items, then the REST route won't be called again and a promise will be resolved", function() {
            $scope.backlog_items.fully_loaded = true;

            var promise = $scope.fetchAllBacklogItems(50, 50);

            expect($scope.fetchBacklogItems).not.toHaveBeenCalled();
            expect(promise).toBeRejected();
        });
    });

    describe("fetchBacklogItems() -", function() {
        beforeEach(function() {
            spyOn($scope, "appendBacklogItems");
        });

        it("Given that we were in a project's context and given a limit and an offset, when I fetch backlog items, then the backlog will be marked as loading, BacklogItemService's Project route will be queried, its result will be appended to the backlog items and its promise will be returned", function() {
            spyOn(PlanningCtrl, "isMilestoneContext").and.returnValue(false);
            BacklogItemService.getProjectBacklogItems.and.returnValue(deferred.promise);

            var promise = $scope.fetchBacklogItems(60, 25);
            expect($scope.backlog_items.loading).toBeTruthy();
            deferred.resolve({
                results: [
                    { id: 734 }
                ],
                total: 34
            });
            $scope.$apply();

            expect(BacklogItemService.getProjectBacklogItems).toHaveBeenCalledWith(736, 60, 25);
            expect($scope.appendBacklogItems).toHaveBeenCalledWith([{ id: 734 }]);
            expect(promise).toBeResolvedWith(34);
        });

        it("Given that we were in a milestone's context and given a limit and an offset, when I fetch backlog items, then the backlog will be marked as loading, BacklogItemService's Milestone route will be queried, its result will be appended to the backlog items and its promise will be returned", function() {
            BacklogItemService.getMilestoneBacklogItems.and.returnValue(deferred.promise);

            var promise = $scope.fetchBacklogItems(60, 25);
            expect($scope.backlog_items.loading).toBeTruthy();
            deferred.resolve({
                results: [
                    { id: 836 }
                ],
                total: 85
            });
            $scope.$apply();

            expect(BacklogItemService.getMilestoneBacklogItems).toHaveBeenCalledWith(592, 60, 25);
            expect($scope.appendBacklogItems).toHaveBeenCalledWith([{ id: 836 }]);
            expect(promise).toBeResolvedWith(85);
        });
    });

    describe("appendBacklogItems() -", function() {
        it("Given an array of items, when I append backlog items, then the results array will be appended to the scope's items and to the scope's backlog_items' content, the filter will be applied, and the backlog_items will no longer be marked as loading", function() {
            $scope.appendBacklogItems([
                { id: 641 },
                { id: 136 }
            ]);

            expect($scope.items).toEqual({
                7  : { id: 7 },
                641: { id: 641 },
                136: { id: 136 }
            });
            expect($scope.backlog_items.content).toEqual([
                { id: 7 },
                { id: 641 },
                { id: 136 }
            ]);
            expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
            expect($scope.backlog_items.loading).toBeFalsy();
        });
    });

    describe("filterBacklog() -", function() {
        beforeEach(function() {
            spyOn($scope, "fetchAllBacklogItems").and.returnValue(deferred.promise);
        });

        it("Given that all items had not been loaded, when I filter the backlog, then all the backlog items will be loaded and filtered", function() {
            $scope.filterBacklog();
            deferred.resolve(50);
            $scope.$apply();

            expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
            expect($scope.fetchAllBacklogItems).toHaveBeenCalledWith(50, 50);
        });

        it("Given that all items had already been loaded, when I filter the backlog, then all the backlog items will be loaded and filtered", function() {
            $scope.filterBacklog();
            deferred.reject(99);
            $scope.$apply();

            expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
            expect($scope.fetchAllBacklogItems).toHaveBeenCalledWith(50, 50);
        });
    });

    describe("thereIsOpenMilestonesLoaded() -", function() {
        it("Given that open milestones have previously been loaded, when I check if open milestones have been loaded, then it will return true", function() {
            $filter.and.returnValue(function() {
                return [
                    {
                        id: 9,
                        semantic_status: 'open'
                    }
                ];
            });

            var result = $scope.thereIsOpenMilestonesLoaded();

            expect(result).toBeTruthy();
        });

        it("Given that open milestones have never been loaded, when I check if open milestones have been loaded, then it will return false", function() {
            $filter.and.returnValue(function() { return []; });

            var result = $scope.thereIsOpenMilestonesLoaded();

            expect(result).toBeFalsy();
        });
    });

    describe("thereIsClosedMilestonesLoaded() -", function() {
        it("Given that closed milestones have previously been loaded, when I check if closed milestones have been loaded, then it will return true", function() {
            $filter.and.returnValue(function() {
                return [
                    {
                        id: 36,
                        semantic_status: 'closed'
                    }
                ];
            });

            var result = $scope.thereIsClosedMilestonesLoaded();

            expect(result).toBeTruthy();
        });

        it("Given that closed milestones have never been loaded, when I check if closed milestones have been loaded, then it will return false", function() {
            $filter.and.returnValue(function() { return []; });

            var result = $scope.thereIsClosedMilestonesLoaded();

            expect(result).toBeFalsy();
        });
    });

    describe("toggle() -", function() {
        var event, milestone;
        describe("Given an event with a target that was not a create-item-link and a milestone object", function() {
            beforeEach(function() {
                event = {
                    target: {
                        classList: {
                            contains: function() {
                                return false;
                            }
                        }
                    }
                };
            });

            it("that was already loaded and collapsed, when I toggle a milestone, then it will be un-collapsed", function() {
                milestone = {
                    collapsed: true,
                    alreadyLoaded: true
                };

                $scope.toggle(event, milestone);

                expect(milestone.collapsed).toBeFalsy();
            });

            it("that was already loaded and was not collapsed, when I toggle a milestone, then it will be collapsed", function() {
                milestone = {
                    collapsed: false,
                    alreadyLoaded: true
                };

                $scope.toggle(event, milestone);

                expect(milestone.collapsed).toBeTruthy();
            });

            it("that was not already loaded, when I toggle a milestone, then its content will be loaded", function() {
                milestone = {
                    content: [],
                    getContent: jasmine.createSpy("getContent")
                };

                $scope.toggle(event, milestone);

                expect(milestone.getContent).toHaveBeenCalled();
            });
        });

        it("Given an event with a create-item-link target and a collapsed milestone, when I toggle a milestone, then it will stay collapsed", function() {
            event = {
                target: {
                    parentNode: {
                        getElementsByClassName: function() {
                            return [
                                {
                                    fakeElement: ''
                                }
                            ];
                        }
                    }
                }
            };

            milestone = {
                collapsed: true,
                alreadyLoaded: true
            };

            $scope.toggle(event, milestone);

            expect(milestone.collapsed).toBeTruthy();
        });
    });

    describe("showChildren() -", function() {
        var fake_scope, backlog_item;

        beforeEach(function() {
            fake_scope = jasmine.createSpyObj("scope", ["toggle"]);
        });

        describe("Given a scope and a backlog item", function() {
            it("with children that were not already loaded, when I show its children, then the scope will be toggled and the item's children will be loaded", function() {
                backlog_item = {
                    id: 352,
                    has_children: true,
                    children: {
                        loaded: false
                    }
                };

                $scope.showChildren(fake_scope, backlog_item);

                expect(fake_scope.toggle).toHaveBeenCalled();
                expect(BacklogItemService.getBacklogItemChildren).toHaveBeenCalledWith(352, 50, 0);
            });

            it("with no children, when I show its children, then the scope will be toggled and BacklogItemService won't be called", function() {
                backlog_item = {
                    has_children: false
                };

                $scope.showChildren(fake_scope, backlog_item);

                expect(fake_scope.toggle).toHaveBeenCalled();
                expect(BacklogItemService.getBacklogItemChildren).not.toHaveBeenCalled();
            });

            it("with children that were already loaded, when I show its children, then the scope will be toggled and BacklogItemService won't be called", function() {
                backlog_item = {
                    has_children: true,
                    children: {
                        loaded: true
                    }
                };

                $scope.showChildren(fake_scope, backlog_item);

                expect(fake_scope.toggle).toHaveBeenCalled();
                expect(BacklogItemService.getBacklogItemChildren).not.toHaveBeenCalled();
            });
        });

    });

    describe("fetchBacklogItemChildren() -", function() {
        beforeEach(function() {
            BacklogItemService.getBacklogItemChildren.and.returnValue(deferred.promise);
        });

        it("Given a backlog item and given there are 2 children, when I fetch the backlog item's children then the BacklogItemService will be queried, the children will be added to the item and the loader will be set to false", function() {
            var backlog_item = {
                id: 95,
                children: {
                    data: []
                }
            };
            $scope.fetchBacklogItemChildren(backlog_item, 50, 50);
            deferred.resolve({
                results: [
                    { id: 151 },
                    { id: 857 }
                ],
                total: 2
            });
            $scope.$apply();

            expect(BacklogItemService.getBacklogItemChildren).toHaveBeenCalledWith(95, 50, 50);
            expect(backlog_item.children.data).toEqual([
                { id: 151 },
                { id: 857 }
            ]);
            expect(backlog_item.loading).toBeFalsy();
            expect(backlog_item.children.loaded).toBeTruthy();
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

            var result = $scope.generateMilestoneLinkUrl(milestone, pane);

            expect(result).toEqual("?group_id=736&planning_id=207&action=show&aid=71&pane=burndown");
        });
    });

    describe("displayUserCantPrioritizeForBacklog() -", function() {
        it("Given that the user cannot move cards in the backlog and the backlog is empty, when I check, then it will return false", function() {
            $scope.backlog.user_can_move_cards = false;
            $scope.backlog_items.content = [];

            var result = $scope.displayUserCantPrioritizeForBacklog();

            expect(result).toBeFalsy();
        });

        it("Given that the user cannot move cards in the backlog and the backlog is not empty, when I check, then it will return true", function() {
            $scope.backlog.user_can_move_cards = false;
            $scope.backlog_items.content = [
                { id: 448 }
            ];

            var result = $scope.displayUserCantPrioritizeForBacklog();

            expect(result).toBeTruthy();
        });
    });

    describe("displayUserCantPrioritizeForMilestones() -", function() {
        it("Given that there were no milestones, when I check whether the user cannot prioritize items in milestones, then it will return false", function() {
            $scope.milestones.content = [];

            var result = $scope.displayUserCantPrioritizeForMilestones();

            expect(result).toBeFalsy();
        });

        it("Given that the user can prioritize items in milestones, when I check, then it will return true", function() {
            $scope.milestones.content = [
                {
                    has_user_priority_change_permission: true
                }
            ];

            var result = $scope.displayUserCantPrioritizeForMilestones();

            expect(result).toBeFalsy();
        });
    });

    describe("canShowBacklogItem() -", function() {
        it("Given an open backlog item, when I check whether we can show it, then it will return true", function() {
            var backlog_item = {
                isOpen: function() { return true; }
            };

            var result = $scope.canShowBacklogItem(backlog_item);

            expect(result).toBeTruthy();
        });

        it("Given a closed backlog item, and we are displaying closed items, when I check whether we can show it, then it will return true", function() {
            var backlog_item = {
                isOpen: function() { return false; }
            };
            $scope.current_closed_view_class = 'show-closed-view';

            var result = $scope.canShowBacklogItem(backlog_item);

            expect(result).toBeTruthy();
        });

        it("Given a closed backlog item, and we are not displaying closed items, when I check whether we can show it, then it will return false", function() {
            var backlog_item = {
                isOpen: function() { return false; }
            };
            $scope.current_closed_view_class = 'hide-closed-view';

            var result = $scope.canShowBacklogItem(backlog_item);

            expect(result).toBeFalsy();
        });

        it("Given an item that didn't have an isOpen() method, when I check whether we can show it, then it will return true", function() {
            var backlog_item = { isOpen: undefined };

            var result = $scope.canShowBacklogItem(backlog_item);

            expect(result).toBeTruthy();
        });
    });

    describe("showCreateNewModal() -", function() {
        var fakeEvent, fakeItemType, fakeBacklog;
        beforeEach(function() {
            fakeEvent = jasmine.createSpyObj("Click event", ["preventDefault"]);
            BacklogItemService.getBacklogItem.and.returnValue(deferred.promise);
        });

        it("Given that we use the 'old' modal and given an event, an item_type object and a project backlog object, when I show the new artifact modal, then the event's default action will be prevented and the TuleapArtifactModal Service will be called with a callback", function() {
            PlanningCtrl.use_angular_new_modal = false;
            SharedPropertiesService.getMilestone.and.returnValue(milestone);

            fakeItemType = { id: 97 };
            fakeBacklog = { rest_route_id: 504 };

            $scope.showCreateNewModal(fakeEvent, fakeItemType, fakeBacklog);

            expect(fakeEvent.preventDefault).toHaveBeenCalled();
            expect(TuleapArtifactModalService.showCreateItemForm).toHaveBeenCalledWith(97, 504, jasmine.any(Function));
        });

        it("Given that we use the 'new' modal and given an event, an item_type object and a project backlog object, when I show the new artifact modal, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback", function() {
            fakeItemType = { id: 50 };
            SharedPropertiesService.getMilestone.and.returnValue(undefined);

            $scope.showCreateNewModal(fakeEvent, fakeItemType, fakeBacklog);

            expect(fakeEvent.preventDefault).toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(50, undefined, jasmine.any(Function));
        });

        describe("callback -", function() {
            var fakeBacklog, fakeArtifact;
            beforeEach(function() {
                BacklogItemService.getBacklogItem.and.returnValue(deferred.promise);
                NewTuleapArtifactModalService.showCreation.and.callFake(function(a, b, callback) {
                    callback(5202);
                });
                fakeArtifact = {
                    backlog_item: {
                        id: 5202
                    }
                };
            });

            describe("Given a project backlog object and an item id,", function() {
                beforeEach(function() {
                    fakeBacklog = {
                        rest_route_id: 80,
                        rest_base_route: "projects"
                    };

                    spyOn(PlanningCtrl, "isMilestoneContext").and.returnValue(false);
                    ProjectService.removeAddReorderToBacklog.and.returnValue(second_deferred.promise);
                });

                it("when the new artifact modal calls its callback, then the artifact will be prepended to the backlog using REST, it will be retrieved from the server, published on the scope's items object and prepended to the backlog_items array", function() {
                    $scope.backlog_items.content = [
                        { id: 3894 }
                    ];
                    $scope.backlog_items.filtered_content = [
                        { id: 3894 }
                    ];

                    $scope.showCreateNewModal(fakeEvent, fakeItemType, fakeBacklog);
                    deferred.resolve(fakeArtifact);
                    second_deferred.resolve();
                    $scope.$apply();

                    expect(ProjectService.removeAddReorderToBacklog).toHaveBeenCalledWith(undefined, 80, 5202, {
                        direction: "before",
                        item_id: 3894
                    });
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect($scope.items[5202]).toEqual({ id: 5202 });
                    expect($scope.backlog_items.content).toEqual([
                        { id: 5202 },
                        { id: 3894 }
                    ]);
                    expect($scope.backlog_items.filtered_content).toEqual([
                        { id: 5202 },
                        { id: 3894 }
                    ]);
                });

                it("and given that the backlog was filtered, when the new artifact modal calls its callback, then the artifact will be prepended to the backlog's content but not its filtered content", function() {
                    $scope.filter_terms = 'needle';
                    $scope.backlog_items.content = [
                        { id: 7453 }
                    ];
                    $scope.backlog_items.filtered_content = [];

                    $scope.showCreateNewModal(fakeEvent, fakeItemType, fakeBacklog);
                    deferred.resolve(fakeArtifact);
                    second_deferred.resolve();
                    $scope.$apply();

                    expect($scope.backlog_items.content).toEqual([
                        { id: 5202 },
                        { id: 7453 }
                    ]);
                    expect($scope.backlog_items.filtered_content).toEqual([]);
                });

                it("and given that the scope's backlog_items was empty, when the new artifact modal calls its callback, then the artifact will be prepended to the backlog and prepended to the scope's backlog_items array", function() {
                    $scope.backlog_items.content = [];
                    ProjectService.removeAddToBacklog.and.returnValue(second_deferred.promise);

                    $scope.showCreateNewModal(fakeEvent, fakeItemType, fakeBacklog);
                    deferred.resolve(fakeArtifact);
                    second_deferred.resolve();
                    $scope.$apply();

                    expect(ProjectService.removeAddToBacklog).toHaveBeenCalledWith(undefined, 80, 5202);
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect($scope.backlog_items.content).toEqual([
                        { id: 5202 }
                    ]);
                });
            });

            describe("Given a milestone backlog object and an item id", function() {
                beforeEach(function() {
                    fakeBacklog = {
                        rest_route_id: 26,
                        rest_base_route: "milestones"
                    };
                });

                it(", when the new artifact modal calls its callback, then the artifact will be prepended to the backlog, it will be retrieved from the server, published on the scope's items object and prepended to the backlog_items array", function() {
                    $scope.backlog_items.content = [
                        { id: 6240 }
                    ];
                    MilestoneService.removeAddReorderToBacklog.and.returnValue(second_deferred.promise);

                    $scope.showCreateNewModal(fakeEvent, fakeItemType, fakeBacklog);
                    deferred.resolve(fakeArtifact);
                    second_deferred.resolve();
                    $scope.$apply();

                    expect(MilestoneService.removeAddReorderToBacklog).toHaveBeenCalledWith(undefined, 26, 5202, {
                        direction: "before",
                        item_id: 6240
                    });
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect($scope.items[5202]).toEqual({ id: 5202 });
                    expect($scope.backlog_items.content).toEqual([
                        { id: 5202 },
                        { id: 6240 }
                    ]);
                });

                it("and given that the scope's backlog_items was empty, when the new artifact modal calls its callback, then the artifact will be prepended to the backlog and prepended to the scope's backlog_items array", function() {
                    $scope.backlog_items.content = [];
                    MilestoneService.removeAddToBacklog.and.returnValue(second_deferred.promise);

                    $scope.showCreateNewModal(fakeEvent, fakeItemType, fakeBacklog);
                    deferred.resolve(fakeArtifact);
                    second_deferred.resolve();
                    $scope.$apply();

                    expect(MilestoneService.removeAddToBacklog).toHaveBeenCalledWith(undefined, 26, 5202);
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect($scope.backlog_items.content).toEqual([
                        { id: 5202 }
                    ]);
                });
            });
        });
    });

    describe("showAddChildModal() -", function() {
        var fake_event, fake_item_type, fake_parent_item;
        beforeEach(function() {
            fake_event       = jasmine.createSpyObj("Click event", ["preventDefault"]);
            fake_item_type   = { id: 77 };
            fake_parent_item = {
                id: 928,
                has_children: true,
                children: {
                    loaded: true,
                    data: [
                        { id: 3525 }
                    ]
                },
                updating: false
            };
            $scope.items[928] = fake_parent_item;
        });

        it("Given an event, an item type and a parent item, when I show the modal to add a child to an item, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback", function() {
            $scope.showAddChildModal(fake_event, fake_item_type, fake_parent_item);

            expect(fake_event.preventDefault).toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(77, fake_parent_item, jasmine.any(Function));
        });

        describe("callback -", function() {
            var fake_artifact;
            beforeEach(function() {
                NewTuleapArtifactModalService.showCreation.and.callFake(function(a, b, callback) {
                    callback(9268);
                });
                BacklogItemService.getBacklogItem.and.returnValue(deferred.promise);
                fake_artifact = {
                    backlog_item: {
                        id: 9268
                    }
                };
                BacklogItemService.removeAddBacklogItemChildren.and.returnValue(second_deferred.promise);
            });

            it("When the new artifact modal calls its callback, then the artifact will be appended to the parent item's children using REST, it will be retrieved from the server, added to the scope's items and appended to the parent's children array", function() {
                $scope.showAddChildModal(fake_event, fake_item_type, fake_parent_item);
                deferred.resolve(fake_artifact);
                second_deferred.resolve();
                $scope.$apply();

                expect(BacklogItemService.removeAddBacklogItemChildren).toHaveBeenCalledWith(undefined, 928, 9268);
                expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(9268);
                expect($scope.items[9268]).toEqual({ id: 9268 });
                expect(fake_parent_item.children.data).toEqual([
                    { id: 3525 },
                    { id: 9268 }
                ]);
            });
        });
    });

    describe("canBeAddedToBacklogItemChildren() - ", function() {
        it("Given a parent with no child, it appends the newly created child", function() {
            var parent = {
                has_children: false,
                children    : {}
            };
            var created_item = {
                id: 8
            };

            expect(PlanningCtrl.canBeAddedToBacklogItemChildren(created_item.id, parent)).toBeTruthy();
        });

        it("Given a parent with already loaded children, it appends the newly created child if not already present", function() {
            var parent = {
                has_children: true,
                children    : {
                    loaded: true,
                    data: [
                        { id: 1 },
                        { id: 2 },
                        { id: 3 }
                    ]
                }
            };
            var created_item = {
                id: 8
            };

            expect(PlanningCtrl.canBeAddedToBacklogItemChildren(created_item.id, parent)).toBeTruthy();
        });

        it("Given a parent with already loaded children, it doesn't append the newly created child if already present", function() {
            var parent = {
                has_children: true,
                children    : {
                    loaded: true,
                    data: [
                        { id: 1 },
                        { id: 2 },
                        { id: 8 }
                    ]
                }
            };
            var created_item = {
                id: 8
            };

            expect(PlanningCtrl.canBeAddedToBacklogItemChildren(created_item.id, parent)).toBeFalsy();
        });

        it("Given a parent with not already loaded children, it doesn't append the newly created child", function() {
            var parent = {
                has_children: true,
                children    : {
                    loaded: false,
                    children: []
                }
            };
            var created_item = {
                id: 8
            };

            expect(PlanningCtrl.canBeAddedToBacklogItemChildren(created_item.id, parent)).toBeFalsy();
        });
    });

    describe("showEditModal() -", function() {
        var fakeEvent, item, get_request;
        beforeEach(function() {
            get_request = $q.defer();
            fakeEvent   = jasmine.createSpyObj("Click event", ["preventDefault"]);
            fakeEvent.which = 1;
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
            spyOn($scope, "refreshBacklogItem").and.returnValue(get_request.promise);
        });

        it("Given a left click event and an item to edit, when I show the edit modal, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback, and the callback will be called", function() {
            $scope.showEditModal(fakeEvent, item);

            expect(fakeEvent.preventDefault).toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showEdition).toHaveBeenCalledWith(102, 30, 651, jasmine.any(Function));
            expect($scope.refreshBacklogItem).toHaveBeenCalledWith(8541);
        });

        it("Given a middle click event and an item to edit, when I show the edit modal, then the event's default action will NOT be prevented and the NewTuleapArtifactModalService won't be called.", function() {
            fakeEvent.which = 2;

            $scope.showEditModal(fakeEvent, item);

            expect(fakeEvent.preventDefault).not.toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showEdition).not.toHaveBeenCalled();
        });

        describe("callback -", function() {
            it("Given a milestone, when the artifact modal calls its callback, then the milestone's initial effort will be updated", function() {
                var milestone = {
                    id: 38,
                    label: "Release v1.0"
                };

                $scope.showEditModal(fakeEvent, item, milestone);
                get_request.resolve();
                $scope.$apply();

                expect(MilestoneService.updateInitialEffort).toHaveBeenCalledWith(milestone);
            });
        });
    });

    describe("showEditSubmilestoneModal() -", function() {
        var fakeEvent, fakeItem;
        beforeEach(function() {
            fakeEvent = jasmine.createSpyObj("Click event", ["preventDefault"]);
            NewTuleapArtifactModalService.showEdition.and.callFake(function(c, a, b, callback) {
                callback(9040);
            });
        });

        it("Given a left click event and a submilestone to edit, when I show the edit modal, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback, and the callback will be called", function() {
            fakeEvent.which = 1;
            fakeItem = {
                artifact: {
                    id: 9040,
                    tracker: {
                        id: 12
                    }
                }
            };
            spyOn($scope, "refreshSubmilestone");

            $scope.showEditSubmilestoneModal(fakeEvent, fakeItem);

            expect(fakeEvent.preventDefault).toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showEdition).toHaveBeenCalledWith(102, 12, 9040, jasmine.any(Function));
            expect($scope.refreshSubmilestone).toHaveBeenCalledWith(9040);
        });

        it("Given a middle click event and a submilestone to edit, when I show the edit modal, then the event's default action will NOT be prevented and the NewTuleapArtifactModalService won't be called.", function() {
            fakeEvent.which = 2;

            $scope.showEditSubmilestoneModal(fakeEvent, fakeItem);

            expect(fakeEvent.preventDefault).not.toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showEdition).not.toHaveBeenCalled();
        });
    });

    describe("showAddSubmilestoneModal() -", function() {
        var fakeEvent, submilestone_type;
        beforeEach(function() {
            submilestone_type = { id: 82 };
            fakeEvent = jasmine.createSpyObj("Click event", ["preventDefault"]);
            NewTuleapArtifactModalService.showCreation.and.callFake(function(a, b, callback) {
                callback(1668);
            });
        });

        it("Given any click event and a submilestone_type object, when I show the artifact modal, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback", function() {
            $scope.showAddSubmilestoneModal(fakeEvent, submilestone_type);

            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(82, undefined, jasmine.any(Function));
        });

        describe("callback -", function() {
            var get_request;
            beforeEach(function() {
                get_request = $q.defer();
                MilestoneService.getMilestone.and.returnValue(get_request.promise);
            });

            it("Given that we were in a milestone context, when the artifact modal calls its callback, then the MilestoneService will be called and the scope will be updated", function() {
                var put_request = $q.defer();
                MilestoneService.putSubMilestones.and.returnValue(put_request.promise);
                $scope.backlog.rest_route_id = 736;
                $scope.milestones.content = [
                    {
                        id: 3118,
                        label: "Sprint 2015-38"
                    }
                ];

                $scope.showAddSubmilestoneModal(fakeEvent, submilestone_type);
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
                expect($scope.milestones.content).toEqual([
                    {
                        id: 1668,
                        label: "Sprint 2015-20"
                    }, {
                        id: 3118,
                        label: "Sprint 2015-38"
                    }
                ]);
            });

            it("Given that we were in a project context (Top Backlog), when the artifact modal calls its callback, then the MilestoneService will be called and the scope will be updated", function() {
                spyOn(PlanningCtrl, "isMilestoneContext").and.returnValue(false);
                $scope.milestones.content = [
                    {
                        id: 3118,
                        label: "Sprint 2015-38"
                    }
                ];

                $scope.showAddSubmilestoneModal(fakeEvent, submilestone_type);
                get_request.resolve({
                    results: {
                        id: 1668,
                        label: "Sprint 2015-20"
                    }
                });
                $scope.$apply();

                expect(MilestoneService.getMilestone).toHaveBeenCalledWith(1668, 50, 0, jasmine.any(Object));
                expect($scope.milestones.content).toEqual([
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
        var fakeItemType, fakeArtifact, fakeSubmilestone;
        beforeEach(function() {
            MilestoneService.updateInitialEffort.and.callThrough();
            BacklogItemService.getBacklogItem.and.returnValue(deferred.promise);
            NewTuleapArtifactModalService.showCreation.and.callFake(function(a, b, callback) {
                callback(7488);
            });
            fakeArtifact = {
                backlog_item: {
                    id: 7488
                }
            };
        });

        it("Given an item_type object and a milestone object, when I show the new artifact modal, then the NewTuleapArtifactModalService will be called with a callback", function() {
            fakeItemType = { id: 94 };
            fakeSubmilestone = { id: 196 };

            $scope.showAddItemToSubMilestoneModal(fakeItemType, fakeSubmilestone);

            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(94, fakeSubmilestone, jasmine.any(Function));
        });

        describe("callback - Given a submilestone object and an item id,", function() {
            beforeEach(function() {
                fakeItemType = { id: 413 };
                fakeSubmilestone = {
                    id: 92,
                    content: []
                };
            });

            it("when the new artifact modal calls its callback, then the artifact will be prepended to the submilestone using the REST route and will be prepended to its content attribute", function() {
                fakeSubmilestone.content = [
                    { id: 9402 }
                ];
                MilestoneService.addReorderToContent.and.returnValue(second_deferred.promise);

                $scope.showAddItemToSubMilestoneModal(fakeItemType, fakeSubmilestone);
                deferred.resolve(fakeArtifact);
                second_deferred.resolve();
                $scope.$apply();

                expect(MilestoneService.addReorderToContent).toHaveBeenCalledWith(92, 7488, {
                    direction: "before",
                    item_id: 9402
                });
                expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(7488);
                expect(fakeSubmilestone.content).toEqual([
                    { id: 7488 },
                    { id: 9402 }
                ]);
            });

            it("and given that the submilestone's content was empty, when the new artifact modal calls its callback, then the artifact will be prepended to the submilestone using the REST route and will be prepended to its content attribute", function() {
                MilestoneService.addToContent.and.returnValue(second_deferred.promise);

                $scope.showAddItemToSubMilestoneModal(fakeItemType, fakeSubmilestone);
                deferred.resolve(fakeArtifact);
                second_deferred.resolve();
                $scope.$apply();

                expect(MilestoneService.addToContent).toHaveBeenCalledWith(92, 7488);
                expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(7488);
                expect(fakeSubmilestone.content).toEqual([
                    { id: 7488 }
                ]);
            });
        });
    });

    describe("refreshBacklogItem() -", function() {
        it("Given an existing backlog item, when I refresh it, it gets the item from the server and publishes it to the scope", function() {
            $scope.backlog_items = [
                { id: 7088 }
            ];
            $scope.items = {
                7088: { id: 7088 }
            };
            BacklogItemService.getBacklogItem.and.returnValue(deferred.promise);

            $scope.refreshBacklogItem(7088);

            expect($scope.items[7088].updating).toBeTruthy();
            deferred.resolve({
                backlog_item: { id: 7088 }
            });
            $scope.$apply();

            expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(7088);
            expect($scope.items[7088]).toEqual(
                jasmine.objectContaining({ id: 7088, updating: false })
            );
            expect($scope.backlog_items).toEqual([
                { id: 7088 }
            ]);
        });
    });

    describe("refreshSubmilestone() -", function() {
        it("Given an existing submilestone, when I refresh it, it gets the submilestone from the server and publishes it to the scope", function() {
            $scope.milestones.content = [
                { id: 9040 }
            ];
            MilestoneService.getMilestone.and.returnValue(deferred.promise);

            $scope.refreshSubmilestone(9040);

            deferred.resolve({
                results: { id: 9040 }
            });
            expect($scope.milestones.content).toEqual([
                jasmine.objectContaining({ id: 9040, updating: true })
            ]);
            $scope.$apply();

            expect(MilestoneService.getMilestone).toHaveBeenCalledWith(9040);
            expect($scope.milestones.content).toEqual([
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
                var result = $scope.treeOptions.accept(sourceNodeScope, destNodeScope);

                expect(result).toBeTruthy();
            });

            it("and given the source element's type wasn't in the destination's accepted types, when I check whether the source element is droppable, then it will return false", function() {
                destNodeScope.$element.attr = function() {
                    return 'trackerId10';
                };

                var result = $scope.treeOptions.accept(sourceNodeScope, destNodeScope);

                expect(result).toBeFalsy();
            });

            it("and given the destination had a 'data-nodrag' attribute set to true, when I check whether the source element is droppable, then it will return undefined", function() {
                destNodeScope.$element.attr = function(attr_name) {
                    if (attr_name === 'data-nodrag')  {
                        return 'true';
                    }
                };

                var result = $scope.treeOptions.accept(sourceNodeScope, destNodeScope);

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

                    $scope.treeOptions.dropped(event);
                    reorder_request.resolve();
                    $scope.$apply();

                    expect(DroppedService.reorderBacklog).toHaveBeenCalledWith(dropped_item_id, compared_to, jasmine.any(Object));
                });

                it("when I reorder an item in a submilestone (e.g. Sprint), then the item will be reordered using DroppedService", function() {
                    function hasClass(name) {
                        return (name === 'submilestone');
                    }

                    event.source.nodesScope.$element.hasClass = hasClass;
                    event.dest.nodesScope.$element.hasClass   = hasClass;
                    event.dest.nodesScope.$element.attr = function() { return 34; };
                    DroppedService.reorderSubmilestone.and.returnValue(reorder_request.promise);

                    $scope.treeOptions.dropped(event);
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

                    $scope.treeOptions.dropped(event);
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
                $scope.backlog_items.content = [
                    { id: 17 },
                    { id: dropped_item_id }
                ];
                var destination_milestone = { id: 80 };
                $scope.milestones.content = [
                    destination_milestone
                ];

                $scope.treeOptions.dropped(event);
                move_request.resolve();
                $scope.$apply();

                expect(DroppedService.moveFromBacklogToSubmilestone).toHaveBeenCalledWith(dropped_item_id, compared_to, 80);
                expect($scope.backlog_items.content).toEqual([
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
                    $scope.items[54] = { updating: false };
                    $scope.items[21] = { updating: false };
                    DroppedService.moveFromChildrenToChildren.and.returnValue(move_request.promise);
                });

                it("then the item will be moved using DroppedService and the source parent will be collapsed", function() {
                    spyOn($scope, 'refreshBacklogItem');

                    $scope.treeOptions.dropped(event);

                    expect($scope.items[54].updating).toBeTruthy();
                    expect($scope.items[21].updating).toBeTruthy();
                    move_request.resolve();
                    $scope.$apply();

                    expect(DroppedService.moveFromChildrenToChildren).toHaveBeenCalledWith(dropped_item_id, compared_to, 54, 21);
                    expect($scope.refreshBacklogItem).toHaveBeenCalledWith(54);
                    expect($scope.refreshBacklogItem).toHaveBeenCalledWith(21);
                });

                it("then the source parent will be collapsed", function() {
                    event.sourceParent = {
                        hasChild: function() { return false; },
                        collapse: jasmine.createSpy("collapse")
                    };

                    $scope.treeOptions.dropped(event);

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

                    $scope.treeOptions.dropped(event);

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
                $scope.milestones.content = [
                    source_milestone
                ];

                $scope.treeOptions.dropped(event);
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
                $scope.milestones.content = [
                    source_milestone,
                    destination_milestone
                ];

                $scope.treeOptions.dropped(event);
                move_request.resolve();
                $scope.$apply();

                expect(DroppedService.moveFromSubmilestoneToSubmilestone).toHaveBeenCalledWith(dropped_item_id, compared_to, 56, 74);
                expect(MilestoneService.updateInitialEffort).toHaveBeenCalledWith(source_milestone);
                expect(MilestoneService.updateInitialEffort).toHaveBeenCalledWith(destination_milestone);
            });

            it("and given the server was unreachable, when I move an item, then the scope will be updated with the error message", function() {
                function hasClass(name) {
                    return (name === 'submilestone');
                }
                event.source.nodesScope.$element.hasClass = hasClass;
                event.dest.nodesScope.$element.hasClass   = hasClass;
                event.source.nodesScope.$element.attr = function() { return 32; };
                event.dest.nodesScope.$element.attr   = function() { return 77; };
                DroppedService.moveFromSubmilestoneToSubmilestone.and.returnValue(move_request.promise);

                $scope.treeOptions.dropped(event);
                move_request.reject({
                    data: {
                        error: {
                            code: 404,
                            message: 'Not Found'
                        }
                    }
                });
                $scope.$apply();

                expect($scope.rest_error_occured).toBeTruthy();
                expect($scope.rest_error).toEqual('404 Not Found');
            });
        });
    });
});
