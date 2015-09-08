describe("PlanningCtrl", function() {
    var $scope, $filter, $q, PlanningCtrl, BacklogItemService, ProjectService, MilestoneService,
        SharedPropertiesService, TuleapArtifactModalService, NewTuleapArtifactModalService,
        UserPreferencesService, deferred, second_deferred;

    beforeEach(function() {
        module('planning');

        inject(function ($controller, $rootScope, _$q_) {
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
            _.invoke(BacklogItemService, "andReturn", $q.defer().promise);

            ProjectService = jasmine.createSpyObj("ProjectService", [
                "getProjectBacklog",
                "getProject",
                "removeAddToBacklog",
                "removeAddReorderToBacklog"
            ]);
            _.invoke(ProjectService, "andReturn", $q.defer().promise);

            MilestoneService = jasmine.createSpyObj("MilestoneService", [
                "addReorderToContent",
                "addToContent",
                "getMilestones",
                "removeAddToBacklog",
                "removeAddReorderToBacklog"
            ]);
            _.invoke(MilestoneService, "andReturn", $q.defer().promise);

            SharedPropertiesService = jasmine.createSpyObj("SharedPropertiesService", [
                "getUserId",
                "getMilestoneId",
                "getProjectId",
                "getViewMode",
                "getUseAngularNewModal"
            ]);

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
            _.invoke(UserPreferencesService, "andReturn", $q.defer().promise);

            $filter.andCallFake(function() {
                return function() {};
            });

            PlanningCtrl = $controller('PlanningCtrl', {
                $scope: $scope,
                $filter: $filter,
                $q: $q,
                BacklogItemService: BacklogItemService,
                MilestoneService: MilestoneService,
                NewTuleapArtifactModalService: NewTuleapArtifactModalService,
                ProjectService: ProjectService,
                SharedPropertiesService: SharedPropertiesService,
                TuleapArtifactModalService: TuleapArtifactModalService,
                UserPreferencesService: UserPreferencesService
            });
        });
        deferred = $q.defer();
        second_deferred = $q.defer();

        installPromiseMatchers();
    });

    describe("displayBacklogItems() -", function() {
        beforeEach(function() {
            spyOn($scope, "fetchBacklogItems").andReturn(deferred.promise);
            $scope.backlog_items = {
                loading: false,
                fully_loaded: false
            };
        });

        it("Given that we aren't already loading backlog_items and all backlog_items have not yet been loaded, when I display the backlog items, then the REST route will be called and a promise will be resolved", function() {
            var promise = $scope.displayBacklogItems();
            deferred.resolve(86);

            expect($scope.fetchBacklogItems).toHaveBeenCalledWith(50, 0);
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
            spyOn($scope, "fetchBacklogItems").andReturn(deferred.promise);
            $scope.backlog_items = {
                loading: false,
                fully_loaded: false
            };
        });

        it("Given that we aren't already loading backlog_items and all backlog_items have not yet been loaded, when I fetch all the backlog items, then the REST route will be called and a promise will be resolved", function() {
            var promise = $scope.fetchAllBacklogItems(50, 0);
            deferred.resolve(40);

            expect($scope.fetchBacklogItems).toHaveBeenCalledWith(50, 0);
            expect(promise).toBeResolved();
        });

        it("Given that there were more items than the current offset and limit, when I fetch all the backlog items, then the REST route will be called twice and a promise will be resolved", function() {
            var promise = $scope.fetchAllBacklogItems(50, 0);
            deferred.resolve(83);
            $scope.$apply();

            expect($scope.fetchBacklogItems).toHaveBeenCalledWith(50, 0);
            expect($scope.fetchBacklogItems).toHaveBeenCalledWith(50, 50);
            expect($scope.fetchBacklogItems.calls.length).toEqual(2);
            expect(promise).toBeResolved();
        });

        it("Given that we were already loading backlog_items, when I fetch all the backlog items, then the REST route won't be called again and a promise will be rejected", function() {
            $scope.backlog_items.loading = true;

            var promise = $scope.fetchAllBacklogItems(50, 0);

            expect($scope.fetchBacklogItems).not.toHaveBeenCalled();
            expect(promise).toBeRejected();
        });

        it("Given that all the backlog_items had been loaded, when I fetch all the backlog items, then the REST route won't be called again and a promise will be resolved", function() {
            $scope.backlog_items.fully_loaded = true;

            var promise = $scope.fetchAllBacklogItems(50, 0);

            expect($scope.fetchBacklogItems).not.toHaveBeenCalled();
            expect(promise).toBeRejected();
        });
    });

    describe("fetchBacklogItems() -", function() {
        beforeEach(function() {
            spyOn($scope, "appendBacklogItems");
        });

        it("Given that we are in a project's context and given a limit and an offset, when I fetch backlog items, then the backlog will be marked as loading, BacklogItemService's Project route will be queried, its result will be appended to the backlog items and its promise will be returned", function() {
            SharedPropertiesService.getProjectId.andReturn(736);
            BacklogItemService.getProjectBacklogItems.andReturn(deferred.promise);

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
            SharedPropertiesService.getMilestoneId.andReturn(592);
            BacklogItemService.getMilestoneBacklogItems.andReturn(deferred.promise);

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
                641: { id: 641 },
                136: { id: 136 }
            });
            expect($scope.backlog_items.content).toEqual([
                { id: 641 },
                { id: 136 }
            ]);
            expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
            expect($scope.backlog_items.loading).toBeFalsy();
        });
    });

    describe("filterBacklog() -", function() {
        beforeEach(function() {
            spyOn($scope, "fetchAllBacklogItems").andReturn(deferred.promise);
        });

        it("Given that all items had not been loaded, when I filter the backlog, then all the backlog items will be loaded and filtered", function() {
            var promise = $scope.filterBacklog();
            deferred.resolve(50);
            $scope.$apply();

            expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
            expect($scope.fetchAllBacklogItems).toHaveBeenCalledWith(50, 0);
        });

        it("Given that all items had already been loaded, when I filter the backlog, then all the backlog items will be loaded and filtered", function() {
            var promise = $scope.filterBacklog();
            deferred.reject(99);
            $scope.$apply();

            expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
            expect($scope.fetchAllBacklogItems).toHaveBeenCalledWith(50, 0);
        });
    });

    describe("fetchBacklogItemChildren() -", function() {
        beforeEach(function() {
            BacklogItemService.getBacklogItemChildren.andReturn(deferred.promise);
        });

        it("Given a backlog item and given there are 2 children, when I fetch the backlog item's children then the BacklogItemService will be queried, the children will be added to the item and the loader will be set to false", function() {
            var backlog_item = {
                id: 95,
                children: {
                    data: []
                }
            };
            $scope.fetchBacklogItemChildren(backlog_item, 50, 0);
            deferred.resolve({
                results: [
                    { id: 151 },
                    { id: 857 }
                ],
                total: 2
            });
            $scope.$apply();

            expect(BacklogItemService.getBacklogItemChildren).toHaveBeenCalledWith(95, 50, 0);
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
            BacklogItemService.getBacklogItem.andReturn(deferred.promise);
        });

        it("Given that we use the 'old' modal and given an event, an item_type object and a project backlog object, when I show the new artifact modal, then the event's default action will be prevented and the TuleapArtifactModal Service will be called with a callback", function() {
            fakeItemType = { id: 97 };
            fakeBacklog = { rest_route_id: 504 };

            $scope.showCreateNewModal(fakeEvent, fakeItemType, fakeBacklog);

            expect(fakeEvent.preventDefault).toHaveBeenCalled();
            expect(TuleapArtifactModalService.showCreateItemForm).toHaveBeenCalledWith(97, 504, jasmine.any(Function));
        });

        it("Given that we use the 'new' modal and given an event, an item_type object and a project backlog object, when I show the new artifact modal, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback", function() {
            SharedPropertiesService.getUseAngularNewModal.andReturn(true);
            fakeItemType = { id: 50 };

            $scope.showCreateNewModal(fakeEvent, fakeItemType, fakeBacklog);

            expect(fakeEvent.preventDefault).toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(50, undefined, jasmine.any(Function));
        });

        describe("callback -", function() {
            var fakeBacklog, fakeArtifact;
            beforeEach(function() {
                BacklogItemService.getBacklogItem.andReturn(deferred.promise);
                TuleapArtifactModalService.showCreateItemForm.andCallFake(function(a, b, callback) {
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
                    ProjectService.removeAddReorderToBacklog.andReturn(second_deferred.promise);
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
                    ProjectService.removeAddToBacklog.andReturn(second_deferred.promise);

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
                    MilestoneService.removeAddReorderToBacklog.andReturn(second_deferred.promise);

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
                    MilestoneService.removeAddToBacklog.andReturn(second_deferred.promise);

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
                NewTuleapArtifactModalService.showCreation.andCallFake(function(a, b, callback) {
                    callback(9268);
                });
                BacklogItemService.getBacklogItem.andReturn(deferred.promise);
                fake_artifact = {
                    backlog_item: {
                        id: 9268
                    }
                };
                BacklogItemService.removeAddBacklogItemChildren.andReturn(second_deferred.promise);
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
            NewTuleapArtifactModalService.showEdition.andCallFake(function(a, b, callback) {
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
            expect(NewTuleapArtifactModalService.showEdition).toHaveBeenCalledWith(30, 651, jasmine.any(Function));
            expect($scope.refreshBacklogItem).toHaveBeenCalledWith(8541);
        });

        it("Given a middle click event and an item to edit, when I show the edit modal, then the event's default action will NOT be prevented and the NewTuleapArtifactModalService won't be called.", function() {
            fakeEvent.which = 2;

            $scope.showEditModal(fakeEvent, fakeItem);

            expect(fakeEvent.preventDefault).not.toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showEdition).not.toHaveBeenCalled();
        });
    });

    describe("showAddItemToSubMilestoneModal() -", function() {
        var fakeItemType, fakeArtifact, fakeSubmilestone;
        beforeEach(function() {
            BacklogItemService.getBacklogItem.andReturn(deferred.promise);
            NewTuleapArtifactModalService.showCreation.andCallFake(function(a, b, callback) {
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
                MilestoneService.addReorderToContent.andReturn(second_deferred.promise);

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
                MilestoneService.addToContent.andReturn(second_deferred.promise);

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
            BacklogItemService.getBacklogItem.andReturn(deferred.promise);

            $scope.refreshBacklogItem(7088);
            deferred.resolve({
                backlog_item: { id: 7088 }
            });

            expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(7088);
            expect($scope.items[7088]).toEqual({ id: 7088, updating: true });
            expect($scope.backlog_items).toEqual([
                { id: 7088 }
            ]);
        });
    });
});
