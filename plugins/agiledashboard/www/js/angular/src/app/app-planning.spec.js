describe('PlanningCtrl', function() {
    var $scope,
        itemService,
        milestoneService,
        project_id = 123,
        milestone_id = 1;

    beforeEach(module('planning'));
    beforeEach(module('backlog-item'));
    beforeEach(module('shared-properties'));

    describe('in top backlog', function() {
        beforeEach(inject(function($controller, $rootScope, BacklogItemService, MilestoneService, SharedPropertiesService) {
            $scope           = $rootScope.$new();
            itemService      = BacklogItemService;
            milestoneService = MilestoneService;

            spyOn(itemService, 'getProjectBacklogItems').andCallThrough();
            spyOn(milestoneService, 'getMilestones').andCallThrough();
            SharedPropertiesService.setProjectId(project_id);

            $controller('PlanningCtrl', {
                $scope: $scope,
                BacklogItemService: itemService,
                MilestoneService: milestoneService
            });
        }));

        describe('backlog items', function() {
            it('asks backlog items of current project', inject(function() {
                expect(itemService.getProjectBacklogItems).toHaveBeenCalledWith(project_id, jasmine.any(Number), jasmine.any(Number));
            }));
        });

        describe('milestones', function() {
            it('asks top milestones of current project', inject(function() {
                expect(milestoneService.getMilestones).toHaveBeenCalledWith(project_id, jasmine.any(Number), jasmine.any(Number));
            }));
        });
    });

    describe('in milestone', function() {
        beforeEach(inject(function($controller, $rootScope, BacklogItemService, MilestoneService, SharedPropertiesService) {
            $scope           = $rootScope.$new();
            itemService      = BacklogItemService;
            milestoneService = MilestoneService;

            spyOn(itemService, 'getMilestoneBacklogItems').andCallThrough();
            spyOn(milestoneService, 'getSubMilestones').andCallThrough();
            SharedPropertiesService.setMilestoneId(milestone_id);

            $controller('PlanningCtrl', {
                $scope: $scope,
                BacklogItemService: itemService,
                MilestoneService: milestoneService
            });
        }));

        describe('backlog items', function() {
            it('asks backlog items of current milestone', inject(function() {
                expect(itemService.getMilestoneBacklogItems).toHaveBeenCalledWith(milestone_id, jasmine.any(Number), jasmine.any(Number));
            }));
        });

        describe('milestones', function() {
            it('asks top milestones of current project', inject(function() {
                expect(milestoneService.getSubMilestones).toHaveBeenCalledWith(milestone_id, jasmine.any(Number), jasmine.any(Number));
            }));
        });
    });
});
