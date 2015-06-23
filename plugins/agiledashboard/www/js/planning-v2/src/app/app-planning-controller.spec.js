describe("PlanningCtrl", function() {
    var $scope, $q, PlanningCtrl, BacklogItemService, ProjectService, MilestoneService, SharedPropertiesService, TuleapArtifactModalService, NewTuleapArtifactModalService,
        deferred, second_deferred;
    beforeEach(function() {
        module('planning');

        inject(function ($controller, $rootScope, _$q_) {
            $scope = $rootScope.$new();
            $q = _$q_;

            BacklogItemService = jasmine.createSpyObj("BacklogItemService", [
                "addToMilestone",
                "getBacklogItem",
                "getProjectBacklogItems"
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
                "getMilestoneId",
                "getProjectId",
                "getUseAngularNewModal"
            ]);

            TuleapArtifactModalService = jasmine.createSpyObj("TuleapArtifactModalService", [
                "showCreateItemForm"
            ]);

            NewTuleapArtifactModalService = jasmine.createSpyObj("NewTuleapArtifactModalService", [
                "show"
            ]);
            NewTuleapArtifactModalService.show.andReturn({
                opened: $q.defer().promise
            });

            PlanningCtrl = $controller('PlanningCtrl', {
                $scope: $scope,
                BacklogItemService: BacklogItemService,
                MilestoneService: MilestoneService,
                NewTuleapArtifactModalService: NewTuleapArtifactModalService,
                ProjectService: ProjectService,
                SharedPropertiesService: SharedPropertiesService,
                TuleapArtifactModalService: TuleapArtifactModalService
            });
        });
        deferred = $q.defer();
        second_deferred = $q.defer();
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
            expect(NewTuleapArtifactModalService.show).toHaveBeenCalledWith(50, jasmine.any(Function));
        });

        describe("callback -", function() {
            var fakeBacklog, fakeArtifact;
            beforeEach(function() {
                BacklogItemService.getBacklogItem.andReturn(deferred.promise);
                TuleapArtifactModalService.showCreateItemForm.andCallFake(function (a, b, callback) {
                    callback(5202);
                });
                fakeArtifact = {
                    backlog_item: {
                        id: 5202
                    }
                };
            });

            describe("Given a project backlog object and an item id", function() {
                beforeEach(function() {
                    fakeBacklog = {
                        rest_route_id: 80,
                        rest_base_route: "projects"
                    };
                });

                it(", when the new artifact modal calls its callback, then the artifact will be prepended to the backlog, it will be retrieved from the server, published on the scope's items object and prepended to the backlog_items array", function() {
                    $scope.backlog_items = [
                        { id: 3894 }
                    ];
                    ProjectService.removeAddReorderToBacklog.andReturn(second_deferred.promise);

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
                    expect($scope.backlog_items).toEqual([
                        { id: 5202 },
                        { id: 3894 }
                    ]);
                });

                it("and given that the scope's backlog_items was empty, when the new artifact modal calls its callback, then the artifact will be prepended to the backlog and prepended to the scope's backlog_items array", function() {
                    $scope.backlog_items = [];
                    ProjectService.removeAddToBacklog.andReturn(second_deferred.promise);

                    $scope.showCreateNewModal(fakeEvent, fakeItemType, fakeBacklog);
                    deferred.resolve(fakeArtifact);
                    second_deferred.resolve();
                    $scope.$apply();

                    expect(ProjectService.removeAddToBacklog).toHaveBeenCalledWith(undefined, 80, 5202);
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect($scope.backlog_items).toEqual([
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
                    $scope.backlog_items = [
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
                    expect($scope.backlog_items).toEqual([
                        { id: 5202 },
                        { id: 6240 }
                    ]);
                });

                it("and given that the scope's backlog_items was empty, when the new artifact modal calls its callback, then the artifact will be prepended to the backlog and prepended to the scope's backlog_items array", function() {
                    $scope.backlog_items = [];
                    MilestoneService.removeAddToBacklog.andReturn(second_deferred.promise);

                    $scope.showCreateNewModal(fakeEvent, fakeItemType, fakeBacklog);
                    deferred.resolve(fakeArtifact);
                    second_deferred.resolve();
                    $scope.$apply();

                    expect(MilestoneService.removeAddToBacklog).toHaveBeenCalledWith(undefined, 26, 5202);
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect($scope.backlog_items).toEqual([
                        { id: 5202 }
                    ]);
                });
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
            NewTuleapArtifactModalService.show.andCallFake(function(a, callback) {
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
                },
                color: "stranding-pseudosophy"
            };
            spyOn($scope, "refreshBacklogItem");

            $scope.showEditModal(fakeEvent, fakeItem);

            expect(fakeEvent.preventDefault).toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.show).toHaveBeenCalledWith(30, jasmine.any(Function), 651, "stranding-pseudosophy");
            expect($scope.refreshBacklogItem).toHaveBeenCalledWith(8541);
        });

        it("Given a middle click event and an item to edit, when I show the edit modal, then the event's default action will NOT be prevented and the NewTuleapArtifactModalService won't be called.", function() {
            fakeEvent.which = 2;

            $scope.showEditModal(fakeEvent, fakeItem);

            expect(fakeEvent.preventDefault).not.toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.show).not.toHaveBeenCalled();
        });
    });

    describe("showAddItemToSubMilestoneModal() -", function() {
        var fakeItemType, fakeArtifact, fakeSubmilestone;
        beforeEach(function() {
            BacklogItemService.getBacklogItem.andReturn(deferred.promise);
            NewTuleapArtifactModalService.show.andCallFake(function(a, callback) {
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

            expect(NewTuleapArtifactModalService.show).toHaveBeenCalledWith(94, jasmine.any(Function));
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
