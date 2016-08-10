describe("ColumnCollectionService -", function() {
    var ColumnCollectionService,
        SharedPropertiesService;

    beforeEach(function() {
        module('kanban', function($provide) {
            $provide.decorator('SharedPropertiesService', function($delegate) {
                spyOn($delegate, "getKanban");

                return $delegate;
            });
        });

        inject(function(
            _ColumnCollectionService_,
            _SharedPropertiesService_
        ) {
            ColumnCollectionService = _ColumnCollectionService_;
            SharedPropertiesService = _SharedPropertiesService_;
        });
    });

    describe("getColumn() -", function() {
        it("Given the id 'archive', when I get this column, then the archive model object will be returned", function() {
            var archive = {
                id     : 'archive',
                content: []
            };
            SharedPropertiesService.getKanban.and.returnValue({
                archive: archive
            });

            var result = ColumnCollectionService.getColumn('archive');

            expect(result).toBe(archive);
        });

        it("Given the id 'backlog', when I get this column, then the backlog model object will be returned", function() {
            var backlog = {
                id     : 'backlog',
                content: []
            };
            SharedPropertiesService.getKanban.and.returnValue({
                backlog: backlog
            });

            var result = ColumnCollectionService.getColumn('backlog');

            expect(result).toBe(backlog);
        });

        it("Given a numeric id of a column, when I get this column, then the column's model object will be returned", function() {
            var column = {
                id     : 68,
                content: []
            };
            SharedPropertiesService.getKanban.and.returnValue({
                columns: [column]
            });

            var result = ColumnCollectionService.getColumn(68);

            expect(result).toBe(column);
        });
    });

    describe("cancelWipEditionOnAllColumns", function() {
        it("when I cancel the wip edition on all columns, then the 'wip_in_edit' property will be set to false for all columns except archive and backlog", function() {
            var first_column = {
                wip_in_edit: true
            };
            var second_column = {
                wip_in_edit: true
            };
            SharedPropertiesService.getKanban.and.returnValue({
                columns: [first_column, second_column]
            });

            ColumnCollectionService.cancelWipEditionOnAllColumns();

            expect(first_column.wip_in_edit).toBe(false);
            expect(second_column.wip_in_edit).toBe(false);
        });
    });
});
