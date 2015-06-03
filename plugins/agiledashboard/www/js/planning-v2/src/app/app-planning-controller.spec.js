describe("PlanningCtrl", function() {
    var $scope, $q, PlanningCtrl, BacklogItemService, ProjectService, MilestoneService, SharedPropertiesService, TuleapArtifactModalService, ModalService,
        deferred;
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
                "removeAddToBacklog",
                "removeAddReorderToBacklog"
            ]);
            _.invoke(ProjectService, "andReturn", $q.defer().promise);

            MilestoneService = jasmine.createSpyObj("MilestoneService", [
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

            ModalService = jasmine.createSpyObj("ModalService", [
                "show"
            ]);
            ModalService.show.andReturn({
                opened: $q.defer().promise
            });

            PlanningCtrl = $controller('PlanningCtrl', {
                $scope: $scope,
                BacklogItemService: BacklogItemService,
                MilestoneService: MilestoneService,
                ModalService: ModalService,
                ProjectService: ProjectService,
                SharedPropertiesService: SharedPropertiesService,
                TuleapArtifactModalService: TuleapArtifactModalService
            });
        });
        deferred = $q.defer();
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

        it("Given that we use the 'new' modal and given an event, an item_type object and a project backlog object, when I show the new artifact modal, then the event's default action will be prevented and the TuleapArtifactModal Service will be called with a callback", function() {
            SharedPropertiesService.getUseAngularNewModal.andReturn(true);
            fakeItemType = { id: 50 };

            $scope.showCreateNewModal(fakeEvent, fakeItemType, fakeBacklog);

            expect(fakeEvent.preventDefault).toHaveBeenCalled();
            expect(ModalService.show).toHaveBeenCalledWith(50, jasmine.any(Function));
        });

        describe("callback -", function() {
            var fakeBacklog, fakeArtifact, second_deferred;
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
                second_deferred = $q.defer();
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
                    expect($scope.backlog_items).toEqual([
                        { id: 5202 }
                    ]);
                });
            });

        });
    });
});
