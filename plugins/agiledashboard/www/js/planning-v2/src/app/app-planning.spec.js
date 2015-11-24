describe('PlanningCtrl', function() {
    var $scope,
        itemService,
        milestoneService,
        projectService,
        PlanningCtrl,
        project_id = 123,
        milestone_id = 1;
        milestone = {
            resources: {
                backlog: {
                    accept: {
                        trackers: [
                            { id: 99, label: 'story'}
                        ]
                    }
                },
                content: {
                    accept: {
                        trackers: [
                            { id: 99, label: 'story'}
                        ]
                    }
                }
            }
        };

    beforeEach(module('project'));
    beforeEach(module('planning'));
    beforeEach(module('backlog-item'));

    describe('in top backlog', function() {
        beforeEach(inject(function($controller, $rootScope, BacklogItemService, MilestoneService, ProjectService) {
            $scope           = $rootScope.$new();
            itemService      = BacklogItemService;
            milestoneService = MilestoneService;
            projectService   = ProjectService;

            spyOn(itemService, 'getProjectBacklogItems').and.callThrough();
            spyOn(milestoneService, 'getMilestones').and.callThrough();

            PlanningCtrl = $controller('PlanningCtrl', {
                $scope: $scope,
                BacklogItemService: itemService,
                MilestoneService: milestoneService
            });

            spyOn(PlanningCtrl, 'isMilestoneContext').and.returnValue(false);
            $scope.init(102, project_id, milestone_id, 'en', true, 'compact-view', milestone);
        }));

        describe('backlog items', function() {
            it('asks backlog items of current project', inject(function() {
                expect(itemService.getProjectBacklogItems).toHaveBeenCalledWith(project_id, jasmine.any(Number), jasmine.any(Number));
            }));
        });

        describe('milestones', function() {
            it('asks top milestones of current project', inject(function() {
                expect(milestoneService.getMilestones).toHaveBeenCalledWith(project_id, jasmine.any(Number), jasmine.any(Number), jasmine.any(Object));
            }));
        });
    });

    describe('in milestone', function() {
        beforeEach(inject(function($controller, $rootScope, BacklogItemService, MilestoneService) {
            $scope           = $rootScope.$new();
            itemService      = BacklogItemService;
            milestoneService = MilestoneService;

            spyOn(itemService, 'getMilestoneBacklogItems').and.callThrough();
            spyOn(milestoneService, 'getSubMilestones').and.callThrough();

            $controller('PlanningCtrl', {
                $scope: $scope,
                BacklogItemService: itemService,
                MilestoneService: milestoneService
            });
            $scope.init(102, project_id, milestone_id, 'en', true, 'compact-view', milestone);
        }));

        describe('backlog items', function() {
            it('asks backlog items of current milestone', inject(function() {
                expect(itemService.getMilestoneBacklogItems).toHaveBeenCalledWith(milestone_id, jasmine.any(Number), jasmine.any(Number));
            }));
        });

        describe('milestones', function() {
            it('asks top milestones of current project', inject(function() {
                expect(milestoneService.getSubMilestones).toHaveBeenCalledWith(milestone_id, jasmine.any(Number), jasmine.any(Number), jasmine.any(Object));
            }));
        });
    });
});
