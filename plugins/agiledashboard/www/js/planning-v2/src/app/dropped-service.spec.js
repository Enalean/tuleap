describe('DroppedServiceTest:', function() {
    var DroppedService,
        ProjectService,
        MilestoneService,
        BacklogItemService;

    beforeEach(module('planning'));
    beforeEach(inject(function(_DroppedService_, _ProjectService_, _MilestoneService_, _BacklogItemService_) {
        DroppedService     = _DroppedService_;
        ProjectService     = _ProjectService_;
        MilestoneService   = _MilestoneService_;
        BacklogItemService = _BacklogItemService_;

        spyOn(ProjectService, 'reorderBacklog').and.callThrough();
        spyOn(ProjectService, 'removeAddReorderToBacklog').and.callThrough();
        spyOn(ProjectService, 'removeAddToBacklog').and.callThrough();
        spyOn(MilestoneService, 'reorderBacklog').and.callThrough();
        spyOn(MilestoneService, 'reorderContent').and.callThrough();
        spyOn(MilestoneService, 'addReorderToContent').and.callThrough();
        spyOn(MilestoneService, 'addToContent').and.callThrough();
        spyOn(MilestoneService, 'removeAddReorderToBacklog').and.callThrough();
        spyOn(MilestoneService, 'removeAddToBacklog').and.callThrough();
        spyOn(MilestoneService, 'removeAddReorderToContent').and.callThrough();
        spyOn(MilestoneService, 'removeAddToContent').and.callThrough();
        spyOn(BacklogItemService, 'reorderBacklogItemChildren').and.callThrough();
        spyOn(BacklogItemService, 'removeAddReorderBacklogItemChildren').and.callThrough();
        spyOn(BacklogItemService, 'removeAddBacklogItemChildren').and.callThrough();
    }));

    describe('defineComparedTo:', function() {
        var item_list = [{ id: 1 }, { id: 2 }, { id: 3 }];

        it('should return before the second item', function () {
            expect(DroppedService.defineComparedTo(item_list, 0)).toEqual({direction: 'before', item_id: 2});
        });

        it('should return after the first item', function () {
            expect(DroppedService.defineComparedTo(item_list, 1)).toEqual({direction: 'after', item_id: 1});
        });

        it('should return after the second item', function () {
            expect(DroppedService.defineComparedTo(item_list, 2)).toEqual({direction: 'after', item_id: 2});
        });
    });

    describe('reorderBacklog:', function() {
        it('should call the REST route that reorder project backlog', function () {
            DroppedService.reorderBacklog(1, {}, { rest_base_route: 'projects', rest_route_id: 2 });
            expect(ProjectService.reorderBacklog).toHaveBeenCalledWith(2, 1, {});
        });

        it('should call the REST route that reorder milestone backlog', function () {
            DroppedService.reorderBacklog(1, {}, { rest_base_route: 'milestones', rest_route_id: 2 });
            expect(MilestoneService.reorderBacklog).toHaveBeenCalledWith(2, 1, {});
        });
    });

    describe('reorderSubmilestone:', function() {
        it('should call the REST route that reorder milestone content', function () {
            DroppedService.reorderSubmilestone(1, {}, 2);
            expect(MilestoneService.reorderContent).toHaveBeenCalledWith(2, 1, {});
        });
    });

    describe('reorderBacklogItemChildren:', function() {
        it('should call the REST route that reorder milestone content', function () {
            DroppedService.reorderBacklogItemChildren(1, {}, 2);
            expect(BacklogItemService.reorderBacklogItemChildren).toHaveBeenCalledWith(2, 1, {});
        });
    });

    describe('moveFromBacklogToSubmilestone:', function() {
        it('should call the REST route that add an item in milestone and reorder its content', function () {
            DroppedService.moveFromBacklogToSubmilestone(1, {}, 2);
            expect(MilestoneService.addReorderToContent).toHaveBeenCalledWith(2, 1, {});
        });

        it('should call the REST route that add an item in milestone without reorder it', function () {
            DroppedService.moveFromBacklogToSubmilestone(1, undefined, 2);
            expect(MilestoneService.addToContent).toHaveBeenCalledWith(2, 1);
        });
    });

    describe('moveFromChildrenToChildren:', function() {
        it('should call the REST route that remove a child from a BI, add it to another BI and reorder the new parent BI', function () {
            DroppedService.moveFromChildrenToChildren(1, {}, 2, 3);
            expect(BacklogItemService.removeAddReorderBacklogItemChildren).toHaveBeenCalledWith(2, 3, 1, {});
        });

        it('should call the REST route that remove a child from a BI, add it to another empty BI', function () {
            DroppedService.moveFromChildrenToChildren(1, undefined, 2, 3);
            expect(BacklogItemService.removeAddBacklogItemChildren).toHaveBeenCalledWith(2, 3, 1);
        });
    });

    describe('moveFromSubmilestoneToBacklog:', function() {
        it('should call the REST route that remove a BI from a milestone and add it to the project backlog and reorder it', function () {
            DroppedService.moveFromSubmilestoneToBacklog(1, {}, 2, { rest_base_route: 'projects', rest_route_id: 3 });
            expect(ProjectService.removeAddReorderToBacklog).toHaveBeenCalledWith(2, 3, 1, {});
        });

        it('should call the REST route that remove a BI from a milestone and add it to the project backlog', function () {
            DroppedService.moveFromSubmilestoneToBacklog(1, undefined, 2, { rest_base_route: 'projects', rest_route_id: 3 });
            expect(ProjectService.removeAddToBacklog).toHaveBeenCalledWith(2, 3, 1);
        });

        it('should call the REST route that remove a BI from a milestone and add it to the project backlog and reorder it', function () {
            DroppedService.moveFromSubmilestoneToBacklog(1, {}, 2, { rest_base_route: 'milestones', rest_route_id: 3 });
            expect(MilestoneService.removeAddReorderToBacklog).toHaveBeenCalledWith(2, 3, 1, {});
        });

        it('should call the REST route that remove a BI from a milestone and add it to the project backlog', function () {
            DroppedService.moveFromSubmilestoneToBacklog(1, undefined, 2, { rest_base_route: 'milestones', rest_route_id: 3 });
            expect(MilestoneService.removeAddToBacklog).toHaveBeenCalledWith(2, 3, 1);
        });
    });

    describe('moveFromSubmilestoneToSubmilestone:', function() {
        it('should call the REST route that remove a BI from a milestone and add it to another milestone backlog and reorder it', function () {
            DroppedService.moveFromSubmilestoneToSubmilestone(1, {}, 2, 3);
            expect(MilestoneService.removeAddReorderToContent).toHaveBeenCalledWith(2, 3, 1, {});
        });

        it('should call the REST route that remove a BI from a milestone and add it to another milestone backlog', function () {
            DroppedService.moveFromSubmilestoneToSubmilestone(1, undefined, 2, 3);
            expect(MilestoneService.removeAddToContent).toHaveBeenCalledWith(2, 3, 1);
        });
    });
});
