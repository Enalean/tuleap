describe('PlanningCtrl', function() {
    var $scope,
        itemService,
        backlogItemFactory,
        milestoneService,
        projectService,
        PlanningCtrl,
        project_id = 123,
        milestone_id = 1,
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
        },
        initial_backlog_items = {
            backlog_items_representations: [
                { id: 7, artifact: { tracker: { id: 90 }}, accept: { trackers: [] } }
            ],
            total_size: 104
        };

    beforeEach(module('project'));
    beforeEach(module('planning'));
    beforeEach(module('backlog-item'));

    describe('in top backlog', function() {
        beforeEach(inject(function($controller, $rootScope, BacklogItemService, BacklogItemFactory, MilestoneService, ProjectService) {
            $scope             = $rootScope.$new();
            itemService        = BacklogItemService;
            backlogItemFactory = BacklogItemFactory;
            milestoneService   = MilestoneService;
            projectService     = ProjectService;

            spyOn(milestoneService, 'getMilestones').and.callThrough();
            spyOn(backlogItemFactory, 'augment');

            PlanningCtrl = $controller('PlanningCtrl', {
                $scope: $scope,
                BacklogItemService: itemService,
                BacklogItemFactory: backlogItemFactory,
                MilestoneService: milestoneService
            });

            spyOn(PlanningCtrl, 'isMilestoneContext').and.returnValue(false);
            spyOn(PlanningCtrl, 'loadInitialBacklogItems');
            $scope.init(102, project_id, milestone_id, 'en', true, 'compact-view', null, initial_backlog_items);
        }));

        describe('backlog items', function() {
            it('load initial backlog items of current project', inject(function() {
                expect(PlanningCtrl.loadInitialBacklogItems).toHaveBeenCalledWith(initial_backlog_items);
            }));
        });

        describe('milestones', function() {
            it('asks top milestones of current project', inject(function() {
                expect(milestoneService.getMilestones).toHaveBeenCalledWith(project_id, jasmine.any(Number), jasmine.any(Number), jasmine.any(Object));
            }));
        });
    });

    describe('in milestone', function() {
        beforeEach(inject(function($controller, $rootScope, BacklogItemService, BacklogItemFactory, MilestoneService) {
            $scope             = $rootScope.$new();
            itemService        = BacklogItemService;
            backlogItemFactory = BacklogItemFactory;
            milestoneService   = MilestoneService;

            spyOn(milestoneService, 'getSubMilestones').and.callThrough();
            spyOn(backlogItemFactory, 'augment');

            PlanningCtrl = $controller('PlanningCtrl', {
                $scope: $scope,
                BacklogItemService: itemService,
                BacklogItemFactory: backlogItemFactory,
                MilestoneService: milestoneService
            });
            spyOn(PlanningCtrl, 'loadInitialBacklogItems');
            $scope.init(102, project_id, milestone_id, 'en', true, 'compact-view', milestone, initial_backlog_items);
        }));

        describe('backlog items', function() {
            it('load initial backlog items of current milestone', inject(function() {
                expect(PlanningCtrl.loadInitialBacklogItems).toHaveBeenCalledWith(initial_backlog_items);
            }));
        });

        describe('milestones', function() {
            it('asks top milestones of current project', inject(function() {
                expect(milestoneService.getSubMilestones).toHaveBeenCalledWith(milestone_id, jasmine.any(Number), jasmine.any(Number), jasmine.any(Object));
            }));
        });
    });
});
