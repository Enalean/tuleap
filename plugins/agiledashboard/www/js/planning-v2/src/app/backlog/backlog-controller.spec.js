import angular from "angular";
import "angular-mocks";

import backlog_module from "./backlog.js";
import BaseBacklogController from "./backlog-controller.js";
import BacklogFilterValue from "../backlog-filter-terms.js";

describe("BacklogController - ", () => {
    let $q,
        $scope,
        $document,
        $controller,
        dragularService,
        BacklogController,
        BacklogService,
        MilestoneService,
        BacklogItemService,
        DroppedService,
        MilestoneCollectionService,
        BacklogItemSelectedService,
        BacklogItemCollectionService,
        ProjectService,
        SharedPropertiesService,
        NewTuleapArtifactModalService,
        ItemAnimatorService;

    const milestone = {
            id: 592,
            resources: {
                backlog: {
                    accept: {
                        trackers: [{ id: 99, label: "story" }]
                    }
                },
                content: {
                    accept: {
                        trackers: [{ id: 99, label: "story" }]
                    }
                }
            },
            sub_milestone_type: { id: 66, label: "sprints" }
        },
        initial_backlog_items = {
            backlog_items_representations: [{ id: 7 }],
            total_size: 104
        };

    beforeEach(() => {
        angular.mock.module(backlog_module);

        angular.mock.inject(function(
            _$q_,
            _$document_,
            $rootScope,
            _$controller_,
            _dragularService_,
            _BacklogService_,
            _MilestoneService_,
            _BacklogItemService_,
            _BacklogItemCollectionService_,
            _ProjectService_,
            _DroppedService_,
            _MilestoneCollectionService_,
            _BacklogItemSelectedService_,
            _SharedPropertiesService_,
            _NewTuleapArtifactModalService_,
            _ItemAnimatorService_
        ) {
            $q = _$q_;
            $document = _$document_;
            $scope = $rootScope.$new();
            dragularService = _dragularService_;

            var returnPromise = function(method) {
                var self = this;
                spyOn(self, method).and.returnValue($q.defer().promise);
            };

            BacklogService = _BacklogService_;
            spyOn(BacklogService, "removeBacklogItemsFromBacklog");
            spyOn(BacklogService, "appendBacklogItems");
            spyOn(BacklogService, "filterItems");
            spyOn(BacklogService, "loadProjectBacklog");
            spyOn(BacklogService, "loadMilestoneBacklog");
            spyOn(BacklogService, "addOrReorderBacklogItemsInBacklog");

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

            BacklogItemService = _BacklogItemService_;
            _([
                "getBacklogItemChildren",
                "getMilestoneBacklogItems",
                "getProjectBacklogItems",
                "getBacklogItem",
                "removeAddBacklogItemChildren"
            ]).forEach(returnPromise, BacklogItemService);

            BacklogItemCollectionService = _BacklogItemCollectionService_;
            spyOn(BacklogItemCollectionService, "refreshBacklogItem");

            ProjectService = _ProjectService_;
            _([
                "getProjectBacklog",
                "getProject",
                "removeAddToBacklog",
                "removeAddReorderToBacklog"
            ]).forEach(returnPromise, ProjectService);

            DroppedService = _DroppedService_;
            spyOn(DroppedService, "moveFromBacklogToSubmilestone");
            spyOn(DroppedService, "defineComparedToBeFirstItem").and.callThrough();
            spyOn(DroppedService, "defineComparedToBeLastItem").and.callThrough();
            spyOn(DroppedService, "reorderBacklog");

            MilestoneCollectionService = _MilestoneCollectionService_;
            spyOn(MilestoneCollectionService, "refreshMilestone");
            spyOn(MilestoneCollectionService, "removeBacklogItemsFromMilestoneContent");
            spyOn(MilestoneCollectionService, "addOrReorderBacklogItemsInMilestoneContent");

            BacklogItemSelectedService = _BacklogItemSelectedService_;
            spyOn(BacklogItemSelectedService, "areThereMultipleSelectedBaklogItems");
            spyOn(BacklogItemSelectedService, "getCompactedSelectedBacklogItem");

            SharedPropertiesService = _SharedPropertiesService_;
            spyOn(SharedPropertiesService, "getProjectId").and.returnValue(736);
            spyOn(SharedPropertiesService, "getMilestoneId").and.returnValue(592);
            spyOn(SharedPropertiesService, "getMilestone").and.returnValue(milestone);
            spyOn(SharedPropertiesService, "getInitialBacklogItems").and.returnValue(
                initial_backlog_items
            );

            NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
            spyOn(NewTuleapArtifactModalService, "showCreation");
            spyOn(NewTuleapArtifactModalService, "showEdition");

            ItemAnimatorService = _ItemAnimatorService_;
            $controller = _$controller_;
        });

        spyOn(ItemAnimatorService, "animateCreated");

        BacklogFilterValue.terms = "";

        BacklogController = $controller(BaseBacklogController, {
            $q,
            $scope,
            $document,
            dragularService,
            BacklogService,
            MilestoneService,
            BacklogItemService,
            ProjectService,
            BacklogItemCollectionService,
            DroppedService,
            MilestoneCollectionService,
            BacklogItemSelectedService,
            SharedPropertiesService,
            NewTuleapArtifactModalService,
            ItemAnimatorService
        });
        BacklogController.$onInit();

        installPromiseMatchers();
    });

    describe("$onInit() - ", function() {
        describe("Given we are in a top backlog context", function() {
            it("When I load the controller, then the project's backlog will be retrieved and the backlog updated", function() {
                BacklogController.milestone_id = undefined;
                BacklogController.$onInit();

                expect(BacklogService.loadProjectBacklog).toHaveBeenCalledWith(736);
            });
        });

        describe("Given we are in a milestone context", function() {
            it("If a milestone has been injected, then use it", inject(function() {
                spyOn(BacklogController, "loadBacklog").and.callThrough();

                BacklogController.$onInit();

                expect(BacklogController.loadBacklog).toHaveBeenCalledWith(milestone);
                expect(MilestoneService.defineAllowedBacklogItemTypes).toHaveBeenCalledWith(
                    milestone
                );
                expect(BacklogService.loadMilestoneBacklog).toHaveBeenCalledWith(milestone);
            }));

            it("If no milestone has been injected, then it will be retrived", inject(function() {
                var milestone_request = $q.defer();

                SharedPropertiesService.getMilestone.and.stub();
                MilestoneService.getMilestone.and.returnValue(milestone_request.promise);
                spyOn(BacklogController, "loadBacklog").and.callThrough();

                BacklogController.$onInit();
                milestone_request.resolve({
                    results: milestone
                });
                $scope.$apply();

                expect(BacklogService.loadMilestoneBacklog).toHaveBeenCalledWith(milestone);
            }));
        });

        it("Load injected backlog items", inject(function() {
            SharedPropertiesService.getMilestoneId.and.stub();
            SharedPropertiesService.getInitialBacklogItems.and.returnValue(initial_backlog_items);
            spyOn(BacklogController, "loadInitialBacklogItems").and.callThrough();

            BacklogController.$onInit();

            expect(BacklogController.loadInitialBacklogItems).toHaveBeenCalledWith(
                initial_backlog_items
            );
            expect(BacklogController.all_backlog_items).toEqual({
                7: { id: 7 }
            });
            expect(BacklogService.appendBacklogItems).toHaveBeenCalledWith([{ id: 7 }]);
            expect(BacklogService.filterItems).toHaveBeenCalledWith("");
        }));
    });

    describe("displayBacklogItems() -", function() {
        var fetch_backlog_items_request;

        beforeEach(function() {
            fetch_backlog_items_request = $q.defer();
            spyOn(BacklogController, "fetchBacklogItems").and.returnValue(
                fetch_backlog_items_request.promise
            );
            BacklogController.backlog_items = {
                loading: false,
                fully_loaded: false,
                pagination: { limit: 50, offset: 50 }
            };
        });

        it("Given that we aren't already loading backlog_items and all backlog_items have not yet been loaded, when I display the backlog items, then the REST route will be called and a promise will be resolved", function() {
            var promise = BacklogController.displayBacklogItems();
            fetch_backlog_items_request.resolve(86);

            expect(BacklogController.fetchBacklogItems).toHaveBeenCalledWith(50, 50);
            expect(promise).toBeResolved();
        });

        it("Given that we were already loading backlog_items, when I display the backlog items then the REST route won't be called again and a promise will be resolved", function() {
            BacklogController.backlog_items.loading = true;

            var promise = BacklogController.displayBacklogItems();

            expect(BacklogController.fetchBacklogItems).not.toHaveBeenCalled();
            expect(promise).toBeResolved();
        });

        it("Given that all the backlog_items had been loaded, when I display the backlog items, then the REST route won't be called again and a promise will be resolved", function() {
            BacklogController.backlog_items.fully_loaded = true;

            var promise = BacklogController.displayBacklogItems();

            expect(BacklogController.fetchBacklogItems).not.toHaveBeenCalled();
            expect(promise).toBeResolved();
        });
    });

    describe("fetchAllBacklogItems() -", function() {
        var fetch_backlog_items_request;

        beforeEach(function() {
            fetch_backlog_items_request = $q.defer();
            spyOn(BacklogController, "fetchBacklogItems").and.returnValue(
                fetch_backlog_items_request.promise
            );
            BacklogController.backlog_items = {
                loading: false,
                fully_loaded: false
            };
        });

        it("Given that we aren't already loading backlog_items and all backlog_items have not yet been loaded, when I fetch all the backlog items, then the REST route will be called and a promise will be resolved", function() {
            var promise = BacklogController.fetchAllBacklogItems(50, 50);
            fetch_backlog_items_request.resolve(40);

            expect(BacklogController.fetchBacklogItems).toHaveBeenCalledWith(50, 50);
            expect(promise).toBeResolved();
        });

        it("Given that there were more items than the current offset and limit, when I fetch all the backlog items, then the REST route will be called twice and a promise will be resolved", function() {
            var promise = BacklogController.fetchAllBacklogItems(50, 50);
            fetch_backlog_items_request.resolve(134);

            expect(promise).toBeResolved();
            expect(BacklogController.fetchBacklogItems).toHaveBeenCalledWith(50, 50);
            expect(BacklogController.fetchBacklogItems).toHaveBeenCalledWith(50, 100);
            expect(BacklogController.fetchBacklogItems.calls.count()).toEqual(2);
        });

        it("Given that we were already loading backlog_items, when I fetch all the backlog items, then the REST route won't be called again and a promise will be rejected", function() {
            BacklogController.backlog_items.loading = true;

            var promise = BacklogController.fetchAllBacklogItems(50, 50);

            expect(BacklogController.fetchBacklogItems).not.toHaveBeenCalled();
            expect(promise).toBeRejected();
        });

        it("Given that all the backlog_items had been loaded, when I fetch all the backlog items, then the REST route won't be called again and a promise will be resolved", function() {
            BacklogController.backlog_items.fully_loaded = true;

            var promise = BacklogController.fetchAllBacklogItems(50, 50);

            expect(BacklogController.fetchBacklogItems).not.toHaveBeenCalled();
            expect(promise).toBeRejected();
        });
    });

    describe("fetchBacklogItems() -", () => {
        it("Given that we were in a project's context and given a limit and an offset, when I fetch backlog items, then the backlog will be marked as loading, BacklogItemService's Project route will be queried, its result will be appended to the backlog items and its promise will be returned", () => {
            spyOn(BacklogController, "isMilestoneContext").and.returnValue(false);
            BacklogItemService.getProjectBacklogItems.and.returnValue(
                $q.resolve({
                    results: [{ id: 734 }],
                    total: 34
                })
            );

            var promise = BacklogController.fetchBacklogItems(60, 25);
            expect(BacklogController.backlog_items.loading).toBeTruthy();

            expect(promise).toBeResolvedWith(34);
            expect(BacklogItemService.getProjectBacklogItems).toHaveBeenCalledWith(736, 60, 25);
            expect(BacklogController.all_backlog_items).toEqual({
                7: { id: 7 },
                734: { id: 734 }
            });
            expect(BacklogService.appendBacklogItems).toHaveBeenCalledWith([{ id: 734 }]);
            expect(BacklogService.filterItems).toHaveBeenCalledWith("");
        });

        it("Given that we were in a milestone's context and given a limit and an offset, when I fetch backlog items, then the backlog will be marked as loading, BacklogItemService's Milestone route will be queried, its result will be appended to the backlog items and its promise will be returned", () => {
            BacklogItemService.getMilestoneBacklogItems.and.returnValue(
                $q.resolve({
                    results: [{ id: 836 }],
                    total: 85
                })
            );

            var promise = BacklogController.fetchBacklogItems(60, 25);
            expect(BacklogController.backlog_items.loading).toBeTruthy();

            expect(promise).toBeResolvedWith(85);
            expect(BacklogItemService.getMilestoneBacklogItems).toHaveBeenCalledWith(592, 60, 25);
            expect(BacklogController.all_backlog_items).toEqual({
                7: { id: 7 },
                836: { id: 836 }
            });
            expect(BacklogService.appendBacklogItems).toHaveBeenCalledWith([{ id: 836 }]);
            expect(BacklogService.filterItems).toHaveBeenCalledWith("");
        });
    });

    describe("filterBacklog() -", () => {
        beforeEach(() => {
            spyOn(BacklogController, "fetchAllBacklogItems");
        });

        it("Given that all items had not been loaded, when I filter the backlog, then all the backlog items will be loaded and filtered", () => {
            BacklogController.fetchAllBacklogItems.and.returnValue($q.when(50));
            BacklogController.filter.terms = "flamboyantly";

            BacklogController.filterBacklog();
            $scope.$apply();

            expect(BacklogController.fetchAllBacklogItems).toHaveBeenCalledWith(50, 50);
            expect(BacklogService.filterItems).toHaveBeenCalledWith("flamboyantly");
        });

        it("Given that all items had already been loaded, when I filter the backlog, then all the backlog items will be filtered", () => {
            BacklogController.fetchAllBacklogItems.and.returnValue($q.reject(99));
            BacklogController.filter.terms = "Jeffersonianism";

            BacklogController.filterBacklog();
            $scope.$apply();

            expect(BacklogController.fetchAllBacklogItems).toHaveBeenCalledWith(50, 50);
            expect(BacklogService.filterItems).toHaveBeenCalledWith("Jeffersonianism");
        });
    });

    describe("showAddBacklogItemParentModal() -", () => {
        let item_type;
        beforeEach(() => {
            item_type = { id: 50 };
        });

        it("Given an event and an item_type, when I show the new artifact modal, then the NewTuleapArtifactModalService will be called with a callback", () => {
            SharedPropertiesService.getMilestone.and.returnValue(undefined);

            BacklogController.showAddBacklogItemParentModal(item_type);

            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(
                50,
                null,
                jasmine.any(Function)
            );
        });

        describe("callback -", () => {
            beforeEach(() => {
                NewTuleapArtifactModalService.showCreation.and.callFake((a, b, callback) =>
                    callback(5202)
                );
            });

            describe("Given an item id and given that we were in a project's context,", () => {
                it("when the new artifact modal calls its callback, then the created artifact will not be added to milestone content", () => {
                    spyOn(BacklogController, "isMilestoneContext").and.returnValue(false);

                    BacklogController.showAddBacklogItemParentModal(item_type);
                    $scope.$apply();

                    expect(MilestoneService.addToContent).not.toHaveBeenCalled();
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                });
            });

            describe("Given an item id and given that we were in a milestone's context,", () => {
                it("when the new artifact modal calls its callback, then the created artifact will be added to milestone content", () => {
                    spyOn(BacklogController, "isMilestoneContext").and.returnValue(true);

                    BacklogController.showAddBacklogItemParentModal(item_type);
                    $scope.$apply();

                    expect(MilestoneService.addToContent).toHaveBeenCalled();
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                });
            });
        });
    });

    describe("showAddBacklogItemModal() -", () => {
        let event, item_type;
        beforeEach(() => {
            event = jasmine.createSpyObj("Click event", ["preventDefault"]);
            item_type = { id: 50 };
        });

        it("Given an event and an item_type object, when I show the new artifact modal, then the event's default action will be prevented and the NewTuleapArtifactModalService will be called with a callback", () => {
            SharedPropertiesService.getMilestone.and.returnValue(undefined);

            BacklogController.showAddBacklogItemModal(event, item_type);

            expect(event.preventDefault).toHaveBeenCalled();
            expect(NewTuleapArtifactModalService.showCreation).toHaveBeenCalledWith(
                50,
                null,
                jasmine.any(Function)
            );
        });

        describe("callback -", () => {
            let artifact;
            beforeEach(() => {
                NewTuleapArtifactModalService.showCreation.and.callFake((a, b, callback) =>
                    callback(5202)
                );
                artifact = {
                    backlog_item: {
                        id: 5202
                    }
                };
            });

            describe("Given an item id and given that we were in a project's context,", () => {
                beforeEach(() => {
                    BacklogController.details = {
                        rest_route_id: 80,
                        rest_base_route: "projects"
                    };

                    spyOn(BacklogController, "isMilestoneContext").and.returnValue(false);
                });

                it("when the new artifact modal calls its callback, then the artifact will be prepended to the backlog using REST, it will be retrieved from the server, and the items and backlog_items collections will be updated", () => {
                    BacklogController.backlog_items.content = [{ id: 3894 }];
                    BacklogController.backlog_items.filtered_content = [{ id: 3894 }];
                    BacklogItemService.getBacklogItem.and.returnValue($q.when(artifact));
                    ProjectService.removeAddReorderToBacklog.and.returnValue($q.when());

                    BacklogController.showAddBacklogItemModal(event, item_type);
                    $scope.$apply();

                    expect(ProjectService.removeAddReorderToBacklog).toHaveBeenCalledWith(
                        undefined,
                        80,
                        [5202],
                        {
                            direction: "before",
                            item_id: 3894
                        }
                    );
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect(BacklogController.all_backlog_items[5202]).toEqual({ id: 5202 });
                    expect(BacklogController.backlog_items.content).toEqual([
                        { id: 5202 },
                        { id: 3894 }
                    ]);
                    expect(BacklogController.backlog_items.filtered_content).toEqual([
                        { id: 5202 },
                        { id: 3894 }
                    ]);
                });

                it("and given that the backlog was filtered, when the new artifact modal calls its callback, then the artifact will be prepended to the backlog's content but not its filtered content", () => {
                    BacklogController.filter.terms = "needle";
                    BacklogController.backlog_items.content = [{ id: 7453 }];
                    BacklogController.backlog_items.filtered_content = [];
                    BacklogItemService.getBacklogItem.and.returnValue($q.when(artifact));
                    ProjectService.removeAddReorderToBacklog.and.returnValue($q.when());

                    BacklogController.showAddBacklogItemModal(event, item_type);
                    $scope.$apply();

                    expect(BacklogController.backlog_items.content).toEqual([
                        { id: 5202 },
                        { id: 7453 }
                    ]);
                    expect(BacklogController.backlog_items.filtered_content).toEqual([]);
                });

                it("and given that the backlog_items collection was empty, when the new artifact modal calls its callback, then the artifact will be prepended to the backlog and prepended to the backlog_items collection", () => {
                    BacklogController.backlog_items.content = [];
                    BacklogItemService.getBacklogItem.and.returnValue($q.when(artifact));
                    ProjectService.removeAddToBacklog.and.returnValue($q.when());

                    BacklogController.showAddBacklogItemModal(event, item_type);
                    $scope.$apply();

                    expect(ProjectService.removeAddToBacklog).toHaveBeenCalledWith(undefined, 80, [
                        5202
                    ]);
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect(BacklogController.backlog_items.content).toEqual([{ id: 5202 }]);
                });
            });

            describe("Given an item id and given we were in a milestone's context", () => {
                beforeEach(() => {
                    BacklogController.details = {
                        rest_route_id: 26,
                        rest_base_route: "milestones"
                    };
                });

                it(", when the new artifact modal calls its callback, then the artifact will be prepended to the backlog, it will be retrieved from the server, and the items and backlog_items collections will be updated", () => {
                    BacklogController.backlog_items.content = [{ id: 6240 }];
                    BacklogItemService.getBacklogItem.and.returnValue($q.when(artifact));
                    MilestoneService.removeAddReorderToBacklog.and.returnValue($q.when());

                    BacklogController.showAddBacklogItemModal(event, item_type);
                    $scope.$apply();

                    expect(MilestoneService.removeAddReorderToBacklog).toHaveBeenCalledWith(
                        undefined,
                        26,
                        [5202],
                        {
                            direction: "before",
                            item_id: 6240
                        }
                    );
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect(BacklogController.all_backlog_items[5202]).toEqual({ id: 5202 });
                    expect(BacklogController.backlog_items.content).toEqual([
                        { id: 5202 },
                        { id: 6240 }
                    ]);
                });

                it("and given that the scope's backlog_items was empty, when the new artifact modal calls its callback, then the artifact will be prepended to the backlog and prepended to the backlog_items collection", () => {
                    BacklogController.backlog_items.content = [];
                    BacklogItemService.getBacklogItem.and.returnValue($q.when(artifact));
                    MilestoneService.removeAddToBacklog.and.returnValue($q.when());

                    BacklogController.showAddBacklogItemModal(event, item_type);
                    $scope.$apply();

                    expect(MilestoneService.removeAddToBacklog).toHaveBeenCalledWith(
                        undefined,
                        26,
                        [5202]
                    );
                    expect(BacklogItemService.getBacklogItem).toHaveBeenCalledWith(5202);
                    expect(BacklogController.backlog_items.content).toEqual([{ id: 5202 }]);
                });
            });
        });
    });

    describe("displayUserCantPrioritize() -", function() {
        it("Given that the user cannot move cards in the backlog and the backlog is empty, when I check, then it will return false", function() {
            BacklogService.backlog.user_can_move_cards = false;
            BacklogService.items.content = [];

            var result = BacklogController.displayUserCantPrioritize();

            expect(result).toBeFalsy();
        });

        it("Given that the user cannot move cards in the backlog and the backlog is not empty, when I check, then it will return true", function() {
            BacklogService.backlog.user_can_move_cards = false;
            BacklogService.items.content = [{ id: 448 }];

            var result = BacklogController.displayUserCantPrioritize();

            expect(result).toBeTruthy();
        });
    });

    describe("isBacklogLoadedAndEmpty() -", function() {
        it("Given that the backlog was loaded and had no children backlog items, when I check if the backlog is loaded and empty, then it will return true", function() {
            BacklogService.items.loading = false;
            BacklogService.items.fully_loaded = true;
            BacklogService.items.content = [];

            var result = BacklogController.isBacklogLoadedAndEmpty();

            expect(result).toBeTruthy();
        });

        it("Given that the backlog was loading, when I check if the backlog is loaded and empty, then it will return false", function() {
            BacklogService.items.loading = true;

            var result = BacklogController.isBacklogLoadedAndEmpty();

            expect(result).toBeFalsy();
        });

        it("Given that the backlog was loaded and had children backlog items, when I check if the backlog is loaded and empty, then it will return false", function() {
            BacklogService.items.loading = false;
            BacklogService.items.fully_loaded = true;
            BacklogService.items.content = [{ id: 92 }];

            var result = BacklogController.isBacklogLoadedAndEmpty();

            expect(result).toBeFalsy();
        });
    });

    describe("dragularDrop() -", function() {
        var $dropped_item_element,
            dropped_item_ids,
            dropped_items,
            $target_element,
            $source_element,
            source_model,
            target_model,
            initial_index,
            target_index,
            compared_to,
            move_request;

        beforeEach(function() {
            dropped_item_ids = [78];
            dropped_items = [{ id: 78 }];
            $dropped_item_element = affix("li");
            angular.element($dropped_item_element).data("item-id", dropped_item_ids[0]);
            $source_element = affix("ul.backlog");
            initial_index = 0;
            target_index = 0;
            compared_to = {
                direction: "before",
                item_id: 53
            };

            move_request = $q.defer();
        });

        describe("Given an event, the dropped element, the target element, the source element, the source model, the initial index, the target model and the target index", function() {
            it("when I reorder an item in the backlog, then the item will be reordered using DroppedService", function() {
                DroppedService.reorderBacklog.and.returnValue(move_request.promise);
                $target_element = $source_element;
                source_model = [{ id: dropped_item_ids[0] }, { id: 53 }];
                target_model = undefined;

                $scope.$emit(
                    "dragulardrop",
                    $dropped_item_element,
                    $target_element,
                    $source_element,
                    source_model,
                    initial_index,
                    target_model,
                    target_index
                );

                expect(DroppedService.reorderBacklog).toHaveBeenCalledWith(
                    dropped_item_ids,
                    compared_to,
                    BacklogService.backlog
                );
            });

            it("when I move an item from the backlog to a submilestone (e.g. to a Sprint), then the item will be moved using DroppedService and the submilestone's initial effort will be updated", function() {
                DroppedService.moveFromBacklogToSubmilestone.and.returnValue(move_request.promise);
                var destination_milestone_id = 80;
                $target_element = affix("ul.submilestone");
                angular.element($target_element).data("submilestone-id", destination_milestone_id);
                source_model = [];
                target_model = [{ id: dropped_item_ids[0] }, { id: 53 }];

                $scope.$emit(
                    "dragulardrop",
                    $dropped_item_element,
                    $target_element,
                    $source_element,
                    source_model,
                    initial_index,
                    target_model,
                    target_index
                );
                move_request.resolve();
                $scope.$apply();

                expect(DroppedService.moveFromBacklogToSubmilestone).toHaveBeenCalledWith(
                    dropped_item_ids,
                    compared_to,
                    destination_milestone_id
                );
                expect(BacklogService.removeBacklogItemsFromBacklog).toHaveBeenCalledWith(
                    dropped_items
                );
                expect(MilestoneCollectionService.refreshMilestone).toHaveBeenCalledWith(
                    destination_milestone_id
                );
            });
        });
    });

    describe("reorderBacklogItems() - ", function() {
        it("reorder the content of a milestone", function() {
            var dropped_request = $q.defer(),
                backlog_items = [{ id: 1 }, { id: 2 }],
                compared_to = { item_id: 3, direction: "before" };

            DroppedService.reorderBacklog.and.returnValue(dropped_request.promise);

            BacklogController.reorderBacklogItems(backlog_items, compared_to);
            dropped_request.resolve();
            $scope.$apply();

            expect(BacklogService.addOrReorderBacklogItemsInBacklog).toHaveBeenCalledWith(
                backlog_items,
                compared_to
            );
            expect(DroppedService.reorderBacklog).toHaveBeenCalledWith(
                [1, 2],
                compared_to,
                BacklogService.backlog
            );
        });
    });

    describe("moveToTop() -", function() {
        beforeEach(function() {
            spyOn(BacklogController, "reorderBacklogItems").and.returnValue($q.defer().promise);
        });

        it("move one item to the top of the backlog", function() {
            var moved_backlog_item = { id: 69 };

            BacklogController.backlog_items = {
                content: [{ id: 50 }, { id: 61 }, moved_backlog_item, { id: 88 }]
            };

            BacklogController.moveToTop(moved_backlog_item);

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeFirstItem).toHaveBeenCalled();
            expect(BacklogController.reorderBacklogItems).toHaveBeenCalledWith(
                [moved_backlog_item],
                { direction: "before", item_id: 50 }
            );
        });

        it("move multiple items to the top of the backlog", function() {
            var moved_backlog_item = { id: 50 };
            var selected_backlog_items = [{ id: 50 }, { id: 69 }];

            BacklogItemSelectedService.areThereMultipleSelectedBaklogItems.and.returnValue(true);
            BacklogItemSelectedService.getCompactedSelectedBacklogItem.and.returnValue(
                selected_backlog_items
            );

            BacklogController.backlog_items = {
                content: [
                    selected_backlog_items[0],
                    { id: 61 },
                    selected_backlog_items[1],
                    { id: 88 }
                ]
            };

            BacklogController.moveToTop(moved_backlog_item);

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeFirstItem).toHaveBeenCalled();
            expect(BacklogController.reorderBacklogItems).toHaveBeenCalledWith(
                selected_backlog_items,
                { direction: "before", item_id: 61 }
            );
        });

        it("move multiple items to the top of the backlog, even if the backlog is filtered", function() {
            var moved_backlog_item = { id: 50 };
            var selected_backlog_items = [{ id: 50 }, { id: 69 }];

            BacklogItemSelectedService.areThereMultipleSelectedBaklogItems.and.returnValue(true);
            BacklogItemSelectedService.getCompactedSelectedBacklogItem.and.returnValue(
                selected_backlog_items
            );

            BacklogController.backlog_items = {
                content: [
                    selected_backlog_items[0],
                    { id: 61 },
                    selected_backlog_items[1],
                    { id: 88 }
                ],
                filtered_content: [selected_backlog_items[0], selected_backlog_items[1], { id: 88 }]
            };

            BacklogController.moveToTop(moved_backlog_item);

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeFirstItem).toHaveBeenCalled();
            expect(BacklogController.reorderBacklogItems).toHaveBeenCalledWith(
                selected_backlog_items,
                { direction: "before", item_id: 61 }
            );
        });
    });

    describe("moveToBottom() -", function() {
        var fetch_all_backlog_items_request;

        beforeEach(function() {
            fetch_all_backlog_items_request = $q.defer();
            spyOn(BacklogController, "reorderBacklogItems").and.returnValue($q.defer().promise);
            spyOn(BacklogController, "fetchAllBacklogItems").and.returnValue(
                fetch_all_backlog_items_request.promise
            );
        });

        it("move one item to the bottom of the fully loaded backlog", function() {
            var moved_backlog_item = { id: 69 };

            BacklogController.backlog_items = {
                content: [{ id: 50 }, { id: 61 }, moved_backlog_item, { id: 88 }],
                pagination: {
                    limit: 50,
                    offset: 0
                }
            };

            BacklogController.moveToBottom(moved_backlog_item);

            fetch_all_backlog_items_request.resolve();
            $scope.$apply();

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeLastItem).toHaveBeenCalled();
            expect(BacklogController.reorderBacklogItems).toHaveBeenCalledWith(
                [moved_backlog_item],
                { direction: "after", item_id: 88 }
            );
        });

        it("move multiple items to the bottom of the fully loaded backlog, even if the backlog is filtered", function() {
            var moved_backlog_item = { id: 50 };
            var selected_backlog_items = [{ id: 50 }, { id: 69 }];

            BacklogItemSelectedService.areThereMultipleSelectedBaklogItems.and.returnValue(true);
            BacklogItemSelectedService.getCompactedSelectedBacklogItem.and.returnValue(
                selected_backlog_items
            );

            BacklogController.backlog_items = {
                content: [{ id: 50 }, { id: 61 }, moved_backlog_item, { id: 88 }],
                filtered_content: [
                    selected_backlog_items[0],
                    selected_backlog_items[1],
                    { id: 88 }
                ],
                pagination: {
                    limit: 50,
                    offset: 0
                }
            };

            BacklogController.moveToBottom(moved_backlog_item);

            fetch_all_backlog_items_request.resolve();
            $scope.$apply();

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeLastItem).toHaveBeenCalled();
            expect(BacklogController.reorderBacklogItems).toHaveBeenCalledWith(
                selected_backlog_items,
                { direction: "after", item_id: 88 }
            );
        });

        it("move multiple items to the bottom of the not fully loaded backlog", function() {
            var moved_backlog_item = { id: 50 };
            var selected_backlog_items = [{ id: 50 }, { id: 69 }];

            BacklogItemSelectedService.areThereMultipleSelectedBaklogItems.and.returnValue(true);
            BacklogItemSelectedService.getCompactedSelectedBacklogItem.and.returnValue(
                selected_backlog_items
            );

            BacklogController.backlog_items = {
                content: [{ id: 50 }, { id: 61 }, moved_backlog_item, { id: 88 }],
                pagination: {
                    limit: 2,
                    offset: 0
                }
            };

            BacklogController.moveToBottom(moved_backlog_item);

            fetch_all_backlog_items_request.resolve();
            $scope.$apply();

            expect(
                BacklogItemSelectedService.areThereMultipleSelectedBaklogItems
            ).toHaveBeenCalled();
            expect(DroppedService.defineComparedToBeLastItem).toHaveBeenCalled();
            expect(BacklogController.reorderBacklogItems).toHaveBeenCalledWith(
                selected_backlog_items,
                { direction: "after", item_id: 88 }
            );
        });
    });

    describe("dragularOptionsForBacklog() -", function() {
        describe("accepts() -", function() {
            var $element_to_drop, $target_container_element;
            beforeEach(function() {
                $element_to_drop = affix("li");
                $target_container_element = affix("ul");
            });

            describe("Given an element to drop and a target container element", function() {
                it("and given that the element's type was in the container's accepted types, when I check if the element can be dropped, then it will return true", function() {
                    angular.element($element_to_drop).data("type", "trackerId49");
                    angular
                        .element($target_container_element)
                        .data("accept", "trackerId38|trackerId49");

                    var result = BacklogController.dragularOptionsForBacklog().accepts(
                        $element_to_drop,
                        $target_container_element
                    );

                    expect(result).toBeTruthy();
                });

                it("and given that the element's type was not in the container's accepted types, when I check if the element can be dropped, then it will return false", function() {
                    angular.element($element_to_drop).data("type", "trackerId49");
                    angular.element($target_container_element).data("accept", "trackerId38");

                    var result = BacklogController.dragularOptionsForBacklog().accepts(
                        $element_to_drop,
                        $target_container_element
                    );

                    expect(result).toBeFalsy();
                });

                it("and given that the container had nodrop data, when I check if the element can be dropped, then it will return false", function() {
                    angular.element($target_container_element).data("nodrop", true);

                    var result = BacklogController.dragularOptionsForBacklog().accepts(
                        $element_to_drop,
                        $target_container_element
                    );

                    expect(result).toBeFalsy();
                });
            });
        });

        describe("moves() -", function() {
            var $element_to_drag, $container, $handle_element;
            beforeEach(function() {
                $element_to_drag = affix("li");
                $container = undefined;
            });

            describe("Given an element to drag and its child handle element", function() {
                it("and given that the handle has an ancestor with the 'dragular-handle' class and the element didn't have nodrag data, when I check if the element can be dragged, then it will return true", function() {
                    var $handle_element = $element_to_drag
                        .affix("div.dragular-handle")
                        .affix("span");

                    var result = BacklogController.dragularOptionsForBacklog().moves(
                        $element_to_drag,
                        $container,
                        $handle_element
                    );

                    expect(result).toBeTruthy();
                });

                it("and given that the handle didn't have any ancestor with the 'dragular-handle' class and the element didn't have nodrag data, when I check if the element can be dragged, then it will return false", function() {
                    var $handle_element = $element_to_drag.affix("span");

                    var result = BacklogController.dragularOptionsForBacklog().moves(
                        $element_to_drag,
                        $container,
                        $handle_element
                    );

                    expect(result).toBeFalsy();
                });

                it("and given that the element had nodrag data, when I check if the element can be dragged, then it will return false", function() {
                    angular.element($element_to_drag).data("nodrag", true);

                    var result = BacklogController.dragularOptionsForBacklog().moves(
                        $element_to_drag,
                        $container,
                        $handle_element
                    );

                    expect(result).toBeFalsy();
                });
            });
        });
    });
});
