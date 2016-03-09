describe('KanbanCtrl - ', function() {

    var $scope, KanbanCtrl, $filter, $modal, $sce, $q, gettextCatalog, amCalendarFilter,
        SharedPropertiesService, KanbanService, KanbanItemService, CardFieldsService,
        NewTuleapArtifactModalService, TuleapArtifactModalLoading, deferred;
    beforeEach(function() {
        module('kanban');

        $filter = {};
        $modal = {};
        $sce = {};
        gettextCatalog = {};
        amCalendarFilter = {};

        SharedPropertiesService = jasmine.createSpyObj('SharedPropertiesService', [
            'getKanban',
            'getUserId',
            'doesUserPrefersCompactCards'
        ]);

        SharedPropertiesService.getKanban.and.returnValue({
            id: 38,
            label: '',
            archive: {},
            backlog: {},
            columns: [
                { id: 230 },
                { id: 530 }
            ],
            tracker_id: 56
        });

        KanbanService = jasmine.createSpyObj('KanbanService', [
            'getArchive',
            'getBacklog',
            'moveInArchive',
            'moveInBacklog',
            'moveInColumn',
            'getColumnContentSize',
            'getArchiveSize',
            'getBacklogSize'
        ]);

        KanbanItemService = jasmine.createSpyObj('KanbanItemService', [
            'getItem'
        ]);

        CardFieldsService = {};

        NewTuleapArtifactModalService = jasmine.createSpyObj('NewTuleapArtifactModalService', [
            'showEdition'
        ]);

        TuleapArtifactModalLoading = {};

        inject(function($controller, $rootScope, _$q_) {
            $scope = $rootScope.$new();
            $q = _$q_;
            deferred = $q.defer();

            _(KanbanService).map('and').invoke('returnValue', $q.defer().promise);

            KanbanCtrl = $controller('KanbanCtrl', {
                $scope                       : $scope,
                $filter                      : $filter,
                $modal                       : $modal,
                $sce                         : $sce,
                $q                           : $q,
                gettextCatalog               : gettextCatalog,
                amCalendarFilter             : amCalendarFilter,
                SharedPropertiesService      : SharedPropertiesService,
                KanbanService                : KanbanService,
                KanbanItemService            : KanbanItemService,
                CardFieldsService            : CardFieldsService,
                NewTuleapArtifactModalService: NewTuleapArtifactModalService,
                TuleapArtifactModalLoading   : TuleapArtifactModalLoading
            });
        });

        installPromiseMatchers();
    });

    describe("showEditModal() -", function() {

        var fake_event;
        beforeEach(function() {
            SharedPropertiesService.getUserId.and.returnValue(102);
            fake_event = {
                which: 1,
                preventDefault: jasmine.createSpy("preventDefault")
            };
        });

        it("Given a left mouse click event, when I show the edition modal, then the default event will be prevented", function() {
            KanbanCtrl.showEditModal(fake_event, {
                id: 55,
                color: 'infaust'
            });

            expect(fake_event.preventDefault).toHaveBeenCalled();
        });

        it("Given an item, when I show the edition modal, then the Tuleap Artifact Modal service will be called", function() {
            KanbanCtrl.showEditModal(fake_event, {
                id: 4288,
                color: 'Indianhood'
            });

            expect(NewTuleapArtifactModalService.showEdition).toHaveBeenCalledWith(102, 56, 4288, jasmine.any(Function));
        });

        describe("callback -", function() {
            var fake_updated_item;

            beforeEach(function() {
                NewTuleapArtifactModalService.showEdition.and.callFake(function(c, a, b, callback) {
                    callback();
                });
                KanbanItemService.getItem.and.returnValue(deferred.promise);
                spyOn(KanbanCtrl, "moveItemAtTheEnd");

                KanbanCtrl.archive = {
                    content: []
                };
                KanbanCtrl.board.columns = [{
                    id: 252,
                    content: []
                }];

                fake_updated_item = {
                    id: 108,
                    color: 'relapse',
                    card_fields: [
                        {
                            field_id: 27,
                            type: 'string',
                            label: 'title',
                            value: 'omnigenous'
                        }
                    ],
                    in_column: 'archive',
                    label: 'omnigenous'
                };
            });

            it("Given an item and given I changed its column during edition, when the new artifact modal calls its callback, then the kanban-item service will be called, the item will be refreshed with the new values and it will be moved at the end of its new column", function() {
                KanbanCtrl.showEditModal(fake_event, {
                    id: 108,
                    color: 'nainsel',
                    in_column: 252
                });
                deferred.resolve(fake_updated_item);
                $scope.$apply();

                // It should be called with the object returned by KanbanItemService (the one with color: 'nainsel' )
                // but jasmine (at least in version 1.3) seems to only register the object ref and not
                // make a deep copy of it, so when we update the object later with _.extend, it biases the test...
                // see https://github.com/jasmine/jasmine/issues/872
                // I'd rather have an imprecise test than a misleading one, so I used jasmine.any(Object)
                expect(KanbanCtrl.moveItemAtTheEnd).toHaveBeenCalledWith(
                    jasmine.any(Object),
                    'archive'
                );
            });

            it("Given an item and given that I did not change its column during edition, when the new artifact modal calls its callback, then the item will not be moved at the end of its new column", function() {
                KanbanCtrl.showEditModal(fake_event, {
                    id: 108,
                    color: 'unpracticably',
                    in_column: 'archive'
                });
                deferred.resolve(fake_updated_item);
                $scope.$apply();

                expect(KanbanCtrl.moveItemAtTheEnd).not.toHaveBeenCalled();
            });
        });
    });

    describe("moveItemAtTheEnd() -", function() {

        it("Given a kanban item in a column and another empty kanban column, when I move the item to the column, then the item will be marked as updating, will be removed from the previous column's content, will be appended to the given column's content, the REST backend will be called to move the item in the new column and a resolved promise will be returned", function() {
            KanbanService.moveInColumn.and.returnValue(deferred.promise);
            var item =  {
                id: 1654,
                updating: false,
                in_column: 7249
            };
            KanbanCtrl.board.columns = [
                {
                    id: 7249,
                    content: [item]
                }, {
                    id: 6030,
                    content: []
                }
            ];

            var promise = KanbanCtrl.moveItemAtTheEnd(item, 6030);

            expect(item.updating).toBeTruthy();

            deferred.resolve();
            $scope.$apply();

            expect(item.updating).toBeFalsy();
            expect(KanbanService.moveInColumn).toHaveBeenCalledWith(38, 6030, 1654, null);
            var previous_column    = KanbanCtrl.board.columns[0];
            var destination_column = KanbanCtrl.board.columns[1];
            expect(previous_column.content).toEqual([]);
            expect(destination_column.content).toEqual([{
                id: 1654,
                updating: false,
                in_column: 6030
            }]);
            expect(promise).toBeResolved();
        });

        it("Given a kanban item in a column and a backlog column, when I move the item to the backlog, then the baclog REST backend will be called", function() {
            KanbanCtrl.backlog = {
                content: [{
                    id: 8515,
                    in_column: 'backlog'
                }]
            };
            var item =  {
                id: 7533,
                updating: false,
                in_column: 3765
            };
            KanbanCtrl.board.columns = [
                {
                    id: 3765,
                    content : [item]
                }
            ];

            KanbanCtrl.moveItemAtTheEnd(item, 'backlog');

            expect(KanbanService.moveInBacklog).toHaveBeenCalledWith(38, 7533, {
                direction: 'after',
                item_id: 8515
            });
        });

        it("Given a kanban item in a column and an archive column, when I move the item to the archive, then the archive REST backend will be called", function() {
            KanbanCtrl.archive = {
                content: [{
                    id: 1440,
                    in_column: 'archive'
                }]
            };
            var item = {
                id: 4906,
                updating: false,
                in_column: 1944
            };
            KanbanCtrl.board.columns = [
                {
                    id: 1944,
                    content : [item]
                }
            ];

            KanbanCtrl.moveItemAtTheEnd(item, 'archive');

            expect(KanbanService.moveInArchive).toHaveBeenCalledWith(38, 4906, {
                direction: 'after',
                item_id: 1440
            });
        });
    });
});
