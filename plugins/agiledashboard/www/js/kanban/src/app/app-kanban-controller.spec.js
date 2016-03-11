describe('KanbanCtrl - ', function() {
    var $rootScope, $scope, $controller, $q, $filter, inner_filter_spy,
        KanbanCtrl, SharedPropertiesService, KanbanService,
        KanbanItemService, NewTuleapArtifactModalService, kanban;

    function emptyArray(array) {
        array.length = 0;
    }

    beforeEach(function() {
        module('kanban');

        inject(function(
            _$controller_,
            _$q_,
            _$rootScope_,
            _KanbanItemService_,
            _KanbanService_,
            _NewTuleapArtifactModalService_,
            _SharedPropertiesService_
        ) {
            $controller                   = _$controller_;
            $q                            = _$q_;
            $rootScope                    = _$rootScope_;
            KanbanItemService             = _KanbanItemService_;
            KanbanService                 = _KanbanService_;
            NewTuleapArtifactModalService = _NewTuleapArtifactModalService_;
            SharedPropertiesService       = _SharedPropertiesService_;
        });

        kanban = {
            id     : 38,
            label  : '',
            archive: {},
            backlog: {},
            columns: [
                { id: 230 },
                { id: 530 }
            ],
            tracker_id: 56
        };

        spyOn(SharedPropertiesService, "getKanban").and.returnValue(kanban);

        spyOn(KanbanService, 'getBacklog').and.returnValue($q.defer().promise);
        spyOn(KanbanService, 'getBacklogSize').and.returnValue($q.defer().promise);
        spyOn(KanbanService, 'getArchive').and.returnValue($q.defer().promise);
        spyOn(KanbanService, 'getArchiveSize').and.returnValue($q.defer().promise);
        spyOn(KanbanService, 'getColumnContentSize').and.returnValue($q.defer().promise);

        inner_filter_spy = jasmine.createSpy("inner_filter_spy");
        $filter          = jasmine.createSpy("$filter").and.returnValue(inner_filter_spy);

        $scope = $rootScope.$new();

        KanbanCtrl = $controller('KanbanCtrl', {
            $scope                       : $scope,
            $q                           : $q,
            $filter                      : $filter,
            SharedPropertiesService      : SharedPropertiesService,
            KanbanService                : KanbanService,
            KanbanItemService            : KanbanItemService,
            NewTuleapArtifactModalService: NewTuleapArtifactModalService
        });

        installPromiseMatchers();
    });

    describe("init() -", function() {
        describe("loadArchive() -", function() {
            it("Given that the archive column was open, when I load it, then its content will be loaded and filtered", function() {
                KanbanCtrl.archive.is_open = true;
                var get_archive_request    = $q.defer();
                KanbanService.getArchive.and.returnValue(get_archive_request.promise);
                var filtered_items = [
                    { id: 88 }
                ];
                inner_filter_spy.and.returnValue(filtered_items);

                KanbanCtrl.init();

                expect(KanbanCtrl.archive.loading_items).toBeTruthy();
                expect(KanbanService.getArchive).toHaveBeenCalledWith(kanban.id, 50, 0);

                get_archive_request.resolve({
                    results: [
                        { id: 88 },
                        { id: 40 }
                    ]
                });
                $scope.$apply();

                expect(KanbanCtrl.archive.content).toEqual([
                    { id: 88 },
                    { id: 40 }
                ]);
                expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
                expect(inner_filter_spy).toHaveBeenCalledWith(KanbanCtrl.archive.content, KanbanCtrl.filter_terms);
                expect(KanbanCtrl.archive.filtered_content).toBe(filtered_items);
                expect(KanbanCtrl.archive.loading_items).toBeFalsy();
                expect(KanbanCtrl.archive.fully_loaded).toBeTruthy();
            });

            it("Given that the archive column was closed, when I load it, then only its total number of items will be loaded", function() {
                var get_archive_size_request = $q.defer();
                KanbanService.getArchiveSize.and.returnValue(get_archive_size_request.promise);

                KanbanCtrl.archive.is_open = false;

                KanbanCtrl.init();
                get_archive_size_request.resolve(6);
                $scope.$apply();

                expect($filter).not.toHaveBeenCalledWith('InPropertiesFilter');
                expect(KanbanService.getArchiveSize).toHaveBeenCalledWith(kanban.id);
                expect(KanbanCtrl.archive.loading_items).toBeFalsy();
                expect(KanbanCtrl.archive.nb_items_at_kanban_init).toEqual(6);
            });
        });

        describe("loadBacklog() -", function() {
            it("Given that the backlog column was open, when I load it, then its content will be loaded", function() {
                KanbanCtrl.backlog.is_open = true;
                var get_backlog_request    = $q.defer();
                KanbanService.getBacklog.and.returnValue(get_backlog_request.promise);
                var filtered_items = [
                    { id: 16 }
                ];
                inner_filter_spy.and.returnValue(filtered_items);

                KanbanCtrl.init();

                expect(KanbanCtrl.backlog.loading_items).toBeTruthy();
                expect(KanbanService.getBacklog).toHaveBeenCalledWith(kanban.id, 50, 0);

                get_backlog_request.resolve({
                    results: [
                        { id: 69 },
                        { id: 16 }
                    ]
                });
                $scope.$apply();

                expect(KanbanCtrl.backlog.content).toEqual([
                    { id: 69 },
                    { id: 16 }
                ]);
                expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
                expect(inner_filter_spy).toHaveBeenCalledWith(KanbanCtrl.backlog.content, KanbanCtrl.filter_terms);
                expect(KanbanCtrl.backlog.filtered_content).toBe(filtered_items);
                expect(KanbanCtrl.backlog.loading_items).toBeFalsy();
                expect(KanbanCtrl.backlog.fully_loaded).toBeTruthy();
            });

            it("Given that the backlog column was closed, when I load it, then only its total number of items will be loaded", function() {
                var get_backlog_size_request = $q.defer();
                KanbanService.getBacklogSize.and.returnValue(get_backlog_size_request.promise);

                KanbanCtrl.backlog.is_open = false;

                KanbanCtrl.init();
                get_backlog_size_request.resolve(28);
                $scope.$apply();

                expect($filter).not.toHaveBeenCalledWith('InPropertiesFilter');
                expect(KanbanService.getBacklogSize).toHaveBeenCalledWith(kanban.id);
                expect(KanbanCtrl.backlog.loading_items).toBeFalsy();
                expect(KanbanCtrl.backlog.nb_items_at_kanban_init).toEqual(28);
            });
        });

        describe("loadColumns() -", function() {
            it("Given a kanban column that was open, when I load it, then its content will be loaded", function() {
                var get_column_request = $q.defer();
                spyOn(KanbanService, 'getItems').and.returnValue(get_column_request.promise);
                kanban.columns    = [];
                kanban.columns[0] = {
                    id     : 10,
                    label  : 'palate',
                    limit  : 7,
                    is_open: true
                };
                var filtered_items = [
                    { id: 981 }
                ];
                inner_filter_spy.and.returnValue(filtered_items);

                KanbanCtrl.init();

                var column = kanban.columns[0];
                expect(column.content).toEqual([]);
                expect(column.filtered_content).toEqual([]);
                expect(column.filtered_content).not.toBe(column.content);
                expect(column.loading_items).toBeTruthy();
                expect(column.nb_items_at_kanban_init).toEqual(0);
                expect(column.fully_loaded).toBeFalsy();
                expect(column.resize_left).toEqual('');
                expect(column.resize_top).toEqual('');
                expect(column.resize_width).toEqual('');
                expect(column.wip_in_edit).toBeFalsy();
                expect(column.limit_input).toEqual(7);
                expect(column.saving_wip).toBeFalsy();
                expect(column.is_small_width).toBeFalsy();
                expect(column.is_defered).toBeFalsy();
                expect(column.original_label).toEqual('palate');

                expect(KanbanService.getItems).toHaveBeenCalledWith(kanban.id, column.id, 50, 0);
                get_column_request.resolve({
                    results: [
                        { id: 981 },
                        { id: 331 }
                    ]
                });
                $scope.$apply();

                expect(column.content).toEqual([
                    { id: 981 },
                    { id: 331 }
                ]);
                expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
                expect(inner_filter_spy).toHaveBeenCalledWith(column.content, KanbanCtrl.filter_terms);
                expect(column.filtered_content).toBe(filtered_items);
                expect(column.loading_items).toBeFalsy();
                expect(column.fully_loaded).toBeTruthy();
            });

            it("Given a kanban column that was closed, when I load it, then only its total number of items will be loaded", function() {
                var get_column_size_request = $q.defer();
                KanbanService.getColumnContentSize.and.returnValue(get_column_size_request.promise);
                kanban.columns    = [];
                kanban.columns[0] = {
                    id     : 75,
                    label  : 'undisfranchised',
                    limit  : 21,
                    is_open: false
                };

                KanbanCtrl.init();

                var column = kanban.columns[0];
                expect(column.content).toEqual([]);
                expect(column.filtered_content).toEqual([]);
                expect(column.filtered_content).not.toBe(column.content);
                expect(column.loading_items).toBeTruthy();
                expect(column.nb_items_at_kanban_init).toEqual(0);
                expect(column.fully_loaded).toBeFalsy();
                expect(column.resize_left).toEqual('');
                expect(column.resize_top).toEqual('');
                expect(column.resize_width).toEqual('');
                expect(column.wip_in_edit).toBeFalsy();
                expect(column.limit_input).toEqual(21);
                expect(column.saving_wip).toBeFalsy();
                expect(column.is_small_width).toBeFalsy();
                expect(column.is_defered).toBeTruthy();
                expect(column.original_label).toEqual('undisfranchised');

                KanbanCtrl.init();
                get_column_size_request.resolve(42);
                $scope.$apply();

                expect($filter).not.toHaveBeenCalledWith('InPropertiesFilter');
                expect(KanbanService.getColumnContentSize).toHaveBeenCalledWith(kanban.id, column.id);
                expect(column.loading_items).toBeFalsy();
                expect(column.nb_items_at_kanban_init).toEqual(42);
            });
        });
    });

    describe("toggleArchive() -", function() {
        it("Given that the archive column was open, when I toggle it, then it will be collapsed and its filtered content will be emptied", function() {
            spyOn(KanbanService, "collapseArchive");
            KanbanCtrl.archive.is_open          = true;
            KanbanCtrl.archive.filtered_content = [
                { id: 82 }
            ];

            KanbanCtrl.toggleArchive();

            expect(KanbanCtrl.archive.filtered_content).toEqual([]);
            expect(KanbanService.collapseArchive).toHaveBeenCalledWith(kanban.id);
            expect(KanbanCtrl.archive.is_open).toBeFalsy();
        });

        describe("Given that the archive column was closed", function() {
            beforeEach(function() {
                KanbanCtrl.archive.is_open = false;
            });

            it("and fully loaded, when I toggle it, then it will be expanded and filtered", function() {
                spyOn(KanbanService, "expandArchive");
                KanbanCtrl.archive.fully_loaded = true;
                KanbanCtrl.archive.content      = [
                    { id: 36 }
                ];
                var filtered_items = [
                    { id: 36 }
                ];
                inner_filter_spy.and.returnValue(filtered_items);

                KanbanCtrl.toggleArchive();

                expect(KanbanService.expandArchive).toHaveBeenCalledWith(kanban.id);
                expect(KanbanCtrl.archive.is_open).toBeTruthy();
                expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
                expect(inner_filter_spy).toHaveBeenCalledWith(KanbanCtrl.archive.content, KanbanCtrl.filter_terms);
                expect(KanbanCtrl.archive.filtered_content).toBe(filtered_items);
            });

            it("and not yet loaded, when I toggle it, then it will be expanded and loaded", function() {
                KanbanCtrl.archive.fully_loaded = false;

                KanbanCtrl.toggleArchive();

                expect(KanbanService.getArchive).toHaveBeenCalled();
            });
        });
    });

    describe("toggleBacklog() -", function() {
        it("Given that the backlog column was open, when I toggle it, then it will be collapsed and its filtered content will be emptied", function() {
            spyOn(KanbanService, "collapseBacklog");
            KanbanCtrl.backlog.is_open = true;
            KanbanCtrl.backlog.filtered_content = [
                { id: 70 }
            ];

            KanbanCtrl.toggleBacklog();

            expect(KanbanCtrl.backlog.filtered_content).toEqual([]);
            expect(KanbanService.collapseBacklog).toHaveBeenCalledWith(kanban.id);
            expect(KanbanCtrl.backlog.is_open).toBeFalsy();
        });

        describe("Given that the backlog column was closed", function() {
            beforeEach(function() {
                KanbanCtrl.backlog.is_open = false;
            });

            it("and fully loaded, when I toggle it, then it will be expanded and filtered", function() {
                spyOn(KanbanService, "expandBacklog");
                KanbanCtrl.backlog.fully_loaded = true;
                KanbanCtrl.backlog.content      = [
                    { id: 80 }
                ];
                var filtered_items = [
                    { id: 36 }
                ];
                inner_filter_spy.and.returnValue(filtered_items);

                KanbanCtrl.toggleBacklog();

                expect(KanbanService.expandBacklog).toHaveBeenCalledWith(kanban.id);
                expect(KanbanCtrl.backlog.is_open).toBeTruthy();
                expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
                expect(inner_filter_spy).toHaveBeenCalledWith(KanbanCtrl.backlog.content, KanbanCtrl.filter_terms);
                expect(KanbanCtrl.backlog.filtered_content).toBe(filtered_items);
            });

            it("and not yet loaded, when I toggle it, then it will be expanded and loaded", function() {
                KanbanCtrl.backlog.fully_loaded = false;

                KanbanCtrl.toggleBacklog();

                expect(KanbanService.getBacklog).toHaveBeenCalled();
            });
        });
    });

    describe("toggleColumn() -", function() {
        it("Given a kanban column that was open, when I toggle it, then it will be collapsed and its filtered content will be emptied", function() {
            spyOn(KanbanService, "collapseColumn");
            var column = {
                id              : 22,
                is_open         : true,
                filtered_content: [
                    { id: 25 }
                ]
            };

            KanbanCtrl.toggleColumn(column);

            expect(column.filtered_content).toEqual([]);
            expect(KanbanService.collapseColumn).toHaveBeenCalledWith(kanban.id, column.id);
            expect(column.is_open).toBeFalsy();
        });

        describe("Given a kanban column that was closed", function() {
            var column;
            beforeEach(function() {
                column = {
                    id     : 69,
                    is_open: false
                };
            });

            it("and fully loaded, when I toggle it, then it will be expanded and filtered", function() {
                spyOn(KanbanService, "expandColumn");
                column.fully_loaded = true;
                column.content      = [
                    { id: 81 }
                ];
                var filtered_items = [
                    { id: 81 }
                ];
                inner_filter_spy.and.returnValue(filtered_items);

                KanbanCtrl.toggleColumn(column);

                expect(KanbanService.expandColumn).toHaveBeenCalledWith(kanban.id, column.id);
                expect(column.is_open).toBeTruthy();
                expect(column.filtered_content).toEqual([
                    { id: 81 }
                ]);
                expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
                expect(inner_filter_spy).toHaveBeenCalledWith(column.content, KanbanCtrl.filter_terms);
                expect(column.filtered_content).toBe(filtered_items);
            });
        });
    });

    describe("createItemInPlace() -", function() {
        it("Given a label and a kanban column, when I create a new kanban item, then it will be created using KanbanItemService and will be appended to the given column", function() {
            var create_item_request = $q.defer();
            spyOn(SharedPropertiesService, "doesUserPrefersCompactCards").and.returnValue(true);
            spyOn(KanbanItemService, "createItem").and.returnValue(create_item_request.promise);
            var column = {
                id     : 5,
                content: [
                    { id: 97 },
                    { id: 69 }
                ],
                filtered_content: [
                    { id: 69 }
                ]
            };

            KanbanCtrl.createItemInPlace('photothermic', column);
            expect(column.content).toEqual([
                { id: 97 },
                { id: 69 },
                {
                    label       : 'photothermic',
                    updating    : true,
                    is_collapsed: true
                }
            ]);
            expect(column.filtered_content).toEqual([
                { id: 69 },
                {
                    label       : 'photothermic',
                    updating    : true,
                    is_collapsed: true
                }
            ]);
            expect(column.filtered_content).not.toBe(column.content);

            create_item_request.resolve({
                id   : 94,
                label: 'photothermic'
            });
            $scope.$apply();

            expect(column.content[2].updating).toBeFalsy();
            expect(KanbanItemService.createItem).toHaveBeenCalledWith(kanban.id, column.id, 'photothermic');
        });
    });

    describe("createItemInPlaceInBacklog() -", function() {
        it("Given a label, when I create a new kanban item in the backlog, then it will be created using KanbanItemService and will be appended to the backlog", function() {
            var create_item_request = $q.defer();
            spyOn(SharedPropertiesService, "doesUserPrefersCompactCards").and.returnValue(true);
            spyOn(KanbanItemService, "createItemInBacklog").and.returnValue(create_item_request.promise);
            KanbanCtrl.backlog.content = [
                { id: 91 },
                { id: 85 }
            ];
            KanbanCtrl.backlog.filtered_content = [
                { id: 91 }
            ];

            KanbanCtrl.createItemInPlaceInBacklog('unbeautifully');
            expect(KanbanCtrl.backlog.content).toEqual([
                { id: 91 },
                { id: 85 },
                {
                    label       : 'unbeautifully',
                    updating    : true,
                    is_collapsed: true
                }
            ]);
            expect(KanbanCtrl.backlog.filtered_content).toEqual([
                { id: 91 },
                {
                    label       : 'unbeautifully',
                    updating    : true,
                    is_collapsed: true
                }
            ]);
            expect(KanbanCtrl.backlog.filtered_content).not.toBe(KanbanCtrl.backlog.content);

            create_item_request.resolve({
                id   : 11,
                label: 'unbeautifully'
            });
            $scope.$apply();

            expect(KanbanCtrl.backlog.content[2].updating).toBeFalsy();
            expect(KanbanItemService.createItemInBacklog).toHaveBeenCalledWith(kanban.id, 'unbeautifully');
        });
    });

    describe("filterCards() -", function() {
        describe("Given that the backlog column", function() {
            beforeEach(function() {
                KanbanCtrl.backlog.content = [
                    { id: 87 },
                    { id: 18 }
                ];
                KanbanCtrl.backlog.filtered_content = angular.copy(KanbanCtrl.backlog.content);
            });

            it("was open, when I filter the kanban, then the backlog's filtered content will be updated", function() {
                KanbanCtrl.backlog.is_open = true;
                var filtered_items = [
                    { id: 18 }
                ];
                inner_filter_spy.and.returnValue(filtered_items);

                KanbanCtrl.treeFilter();

                expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
                expect(inner_filter_spy).toHaveBeenCalledWith(KanbanCtrl.backlog.content, KanbanCtrl.filter_terms);
                expect(KanbanCtrl.backlog.filtered_content).toBe(filtered_items);
            });

            it("was closed, when I filter the kanban, then the backlog won't be filtered", function() {
                KanbanCtrl.backlog.is_open = false;

                KanbanCtrl.treeFilter();

                expect(inner_filter_spy).not.toHaveBeenCalledWith(KanbanCtrl.backlog.content, KanbanCtrl.filter_terms);
                expect(KanbanCtrl.backlog.filtered_content).toEqual(KanbanCtrl.backlog.content);
            });
        });

        describe("Given that the archive column", function() {
            beforeEach(function() {
                KanbanCtrl.archive.content = [
                    { id: 66 },
                    { id: 30 }
                ];
                KanbanCtrl.archive.filtered_content = angular.copy(KanbanCtrl.archive.content);
            });

            it("was open, when I filter the kanban, then the archive's filtered content will be updated", function() {
                var filtered_items = [
                    { id: 66 }
                ];
                inner_filter_spy.and.returnValue(filtered_items);
                KanbanCtrl.archive.is_open = true;

                KanbanCtrl.treeFilter();

                expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
                expect(inner_filter_spy).toHaveBeenCalledWith(KanbanCtrl.archive.content, KanbanCtrl.filter_terms);
                expect(KanbanCtrl.archive.filtered_content).toBe(filtered_items);
            });

            it("was closed, when I filter the kanban, then the archive won't be filtered", function() {
                KanbanCtrl.archive.is_open = false;

                KanbanCtrl.treeFilter();

                expect(inner_filter_spy).not.toHaveBeenCalledWith(KanbanCtrl.archive.content, KanbanCtrl.filter_terms);
                expect(KanbanCtrl.archive.filtered_content).toEqual(KanbanCtrl.archive.content);
            });
        });

        describe("Given a kanban column", function() {
            var column;

            beforeEach(function() {
                emptyArray(kanban.columns);
                column = {
                    id     : 8,
                    content: [
                        { id: 49 },
                        { id: 27 }
                    ],
                    filtered_content: [
                        { id: 49 },
                        { id: 27 }
                    ]
                };
                kanban.columns[0] = column;
            });

            it("that was open, when I filter the kanban, then the column's filtered content will be updated", function() {
                column.is_open = true;
                var filtered_items = [
                    { id: 27 }
                ];
                inner_filter_spy.and.returnValue(filtered_items);

                KanbanCtrl.treeFilter();

                expect($filter).toHaveBeenCalledWith('InPropertiesFilter');
                expect(inner_filter_spy).toHaveBeenCalledWith(kanban.columns[0].content, KanbanCtrl.filter_terms);
                expect(kanban.columns[0].filtered_content).toBe(filtered_items);
            });

            it("that was closed, when I filter the kanban, then the column won't be filtered", function() {
                column.is_open = false;

                KanbanCtrl.treeFilter();

                expect(inner_filter_spy).not.toHaveBeenCalledWith(column.content, KanbanCtrl.filter_terms);
                expect(column.filtered_content).toEqual(column.content);
            });
        });
    });

    describe("showEditModal() -", function() {
        var fake_event;
        beforeEach(function() {
            spyOn(NewTuleapArtifactModalService, 'showEdition');
            spyOn(SharedPropertiesService, 'getUserId').and.returnValue(102);
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
            var get_request;

            beforeEach(function() {
                NewTuleapArtifactModalService.showEdition.and.callFake(function(c, a, b, callback) {
                    callback();
                });
                get_request = $q.defer();
                spyOn(KanbanItemService, 'getItem').and.returnValue(get_request.promise);
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
                get_request.resolve(fake_updated_item);
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
                get_request.resolve(fake_updated_item);
                $scope.$apply();

                expect(KanbanCtrl.moveItemAtTheEnd).not.toHaveBeenCalled();
            });
        });
    });

    describe("moveItemAtTheEnd() -", function() {
        it("Given a kanban item in a column and another empty kanban column, when I move the item to the column, then the item will be marked as updating, will be removed from the previous column's content, will be appended to the given column's content, the REST backend will be called to move the item in the new column and a resolved promise will be returned", function() {
            var deferred = $q.defer();
            spyOn(KanbanService, 'moveInColumn').and.returnValue(deferred.promise);
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
            spyOn(KanbanService, 'moveInBacklog').and.returnValue($q.defer().promise);
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
            spyOn(KanbanService, 'moveInArchive').and.returnValue($q.defer().promise);
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
