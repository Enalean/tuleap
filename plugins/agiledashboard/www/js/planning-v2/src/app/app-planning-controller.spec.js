describe("PlanningCtrl", function() {
    var $scope, $filter, $q, PlanningCtrl, BacklogItemService, BacklogItemFactory, ProjectService, MilestoneService,
        TuleapArtifactModalService, NewTuleapArtifactModalService,
        UserPreferencesService, deferred, second_deferred;

    beforeEach(function() {
        module('planning');

        inject(function($controller, $rootScope, _$q_) {
            $scope = $rootScope.$new();
            $q = _$q_;
            $filter = jasmine.createSpy("$filter");

            BacklogItemService = jasmine.createSpyObj("BacklogItemService", [
                "addToMilestone",
                "getBacklogItemChildren",
                "getMilestoneBacklogItems",
                "getProjectBacklogItems",
                "getBacklogItem",
                "removeAddBacklogItemChildren"
            ]);
            _(BacklogItemService).map('and').invoke('returnValue', $q.defer().promise);

            BacklogItemFactory = jasmine.createSpyObj("BacklogItemFactory", [
                "augment"
            ]);

            ProjectService = jasmine.createSpyObj("ProjectService", [
                "getProjectBacklog",
                "getProject",
                "removeAddToBacklog",
                "removeAddReorderToBacklog"
            ]);
            _(ProjectService).map('and').invoke('returnValue', $q.defer().promise);

            MilestoneService = jasmine.createSpyObj("MilestoneService", [
                "addReorderToContent",
                "addToContent",
                "getMilestone",
                "getMilestones",
                "removeAddToBacklog",
                "removeAddReorderToBacklog",
                "defineAllowedBacklogItemTypes",
                "augmentMilestone",
                "getSubMilestones"
            ]);
            _(MilestoneService).map('and').invoke('returnValue', $q.defer().promise);

            TuleapArtifactModalService = jasmine.createSpyObj("TuleapArtifactModalService", [
                "showCreateItemForm"
            ]);

            NewTuleapArtifactModalService = jasmine.createSpyObj("NewTuleapArtifactModalService", [
                "showCreation",
                "showEdition"
            ]);

            UserPreferencesService = jasmine.createSpyObj("UserPreferencesService", [
                "setPreference"
            ]);
            _(UserPreferencesService).map('and').invoke('returnValue', $q.defer().promise);

            $filter.and.callFake(function() {
                return function() {};
            });

            PlanningCtrl = $controller('PlanningCtrl', {
                $scope: $scope,
                $filter: $filter,
                $q: $q,
                BacklogItemService: BacklogItemService,
                BacklogItemFactory: BacklogItemFactory,
                MilestoneService: MilestoneService,
                NewTuleapArtifactModalService: NewTuleapArtifactModalService,
                ProjectService: ProjectService,
                TuleapArtifactModalService: TuleapArtifactModalService,
                UserPreferencesService: UserPreferencesService
            });

            $scope.init(102, 736, 592, 'en', true, 'compact-view', {}, { backlog_items_representations: [ {id: 7} ], total_size: 104 }, {});
        });
        deferred = $q.defer();
        second_deferred = $q.defer();

        installPromiseMatchers();
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

        it("Given that we are in a project's context and given a limit and an offset, when I fetch backlog items, then the backlog will be marked as loading, BacklogItemService's Project route will be queried, its result will be appended to the backlog items and its promise will be returned", function() {
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

        it("Given that we are in a milestone's context and given a limit and an offset, when I fetch backlog items, then the backlog will be marked as loading, BacklogItemService's Milestone route will be queried, its result will be appended to the backlog items and its promise will be returned", function() {
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

    describe("displayUserCantPrioritizeForBacklog() -", function() {
        it("Given that the user cannot move cards in the backlog and the backlog is empty, when I display whether the user can prioritize the backlog, then it will return false", function() {
            $scope.backlog.user_can_move_cards = false;
            $scope.backlog_items.content = [];

            var result = $scope.displayUserCantPrioritizeForBacklog();

            expect(result).toBeFalsy();
        });

        it("Given that the user cannot move cards in the backlog and the backlog is not empty, when I display whether the user can prioritize the backlog, then it will return true", function() {
            $scope.backlog.user_can_move_cards = false;
            $scope.backlog_items.content = [
                { id: 448 }
            ];

            var result = $scope.displayUserCantPrioritizeForBacklog();

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

            fakeItemType = { id: 97 };
            fakeBacklog = { rest_route_id: 504 };

            $scope.showCreateNewModal(fakeEvent, fakeItemType, fakeBacklog);

            expect(fakeEvent.preventDefault).toHaveBeenCalled();
            expect(TuleapArtifactModalService.showCreateItemForm).toHaveBeenCalledWith(97, 504, jasmine.any(Function));
        });

        it("Given that we use the 'new' modal and given an event, an item_type object and a project backlog object, when I show the new artifact modal, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback", function() {
            fakeItemType = { id: 50 };

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
                        { id: 1 },
                        { id: 2 },
                        { id: 3 }
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
                        { id: 1 },
                        { id: 2 },
                        { id: 8 }
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
        var fakeEvent, fakeItem;
        beforeEach(function() {
            fakeEvent = jasmine.createSpyObj("Click event", ["preventDefault"]);
            NewTuleapArtifactModalService.showEdition.and.callFake(function(c, a, b, callback) {
                callback(8541);
            });
        });

        it("Given a left click event and an item to edit, when I show the edit modal, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback, and the callback will be called", function() {
            fakeEvent.which = 1;
            fakeItem = {
                artifact: {
                    id: 651,
                    tracker: {
                        id: 30
                    }
                }
            };
            spyOn($scope, "refreshBacklogItem");

            $scope.showEditModal(fakeEvent, fakeItem);

            expect(fakeEvent.preventDefault).toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showEdition).toHaveBeenCalledWith(102, 30, 651, jasmine.any(Function));
            expect($scope.refreshBacklogItem).toHaveBeenCalledWith(8541);
        });

        it("Given a middle click event and an item to edit, when I show the edit modal, then the event's default action will NOT be prevented and the NewTuleapArtifactModalService won't be called.", function() {
            fakeEvent.which = 2;

            $scope.showEditModal(fakeEvent, fakeItem);

            expect(fakeEvent.preventDefault).not.toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showEdition).not.toHaveBeenCalled();
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

    describe("showAddItemToSubMilestoneModal() -", function() {
        var fakeItemType, fakeArtifact, fakeSubmilestone;
        beforeEach(function() {
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
            $scope.milestones = [
                { id: 9040 }
            ];
            MilestoneService.getMilestone.and.returnValue(deferred.promise);

            $scope.refreshSubmilestone(9040);

            deferred.resolve({
                results: { id: 9040 }
            });
            expect($scope.milestones).toEqual([
                jasmine.objectContaining({ id: 9040, updating: true })
            ]);
            $scope.$apply();

            expect(MilestoneService.getMilestone).toHaveBeenCalledWith(9040);
            expect($scope.milestones).toEqual([
                jasmine.objectContaining({ id: 9040, updating: false })
            ]);
        });
    });
});
