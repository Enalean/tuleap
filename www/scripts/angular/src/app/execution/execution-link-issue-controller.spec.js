import execution_module from './execution.js';
import angular          from 'angular';
import 'angular-mocks';

import BaseController from './execution-link-issue-controller.js';

describe("ExecutionLinkIssueController -", () => {
    let $q,
        $scope,
        ExecutionLinkIssueController,
        ExecutionRestService,
        modal_instance,
        modal_model,
        modal_callback,
        SharedPropertiesService;

    const issue_tracker_id = 8;

    beforeEach(() => {
        angular.mock.module(execution_module);

        let $controller,
            $rootScope;

        angular.mock.inject(function(
            _$controller_,
            _$q_,
            _$rootScope_,
            _ExecutionRestService_,
            _SharedPropertiesService_,
        ) {
            $controller             = _$controller_;
            $q                      = _$q_;
            $rootScope              = _$rootScope_;
            ExecutionRestService    = _ExecutionRestService_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        $scope = $rootScope.$new();

        modal_instance = {
            tlp_modal: {
                hide: jasmine.createSpy("hide")
            }
        };

        modal_model = {
            test_execution: {
                id: 21,
                definition: {
                    summary: 'tempestuous'
                }
            }
        };

        modal_callback = jasmine.createSpy("modal_callback");

        spyOn(SharedPropertiesService, "getIssueTrackerId").and.returnValue(issue_tracker_id);
        spyOn(SharedPropertiesService, "getIssueTrackerConfig").and.returnValue({
            xref_color: 'flamingo_pink'
        });

        ExecutionLinkIssueController = $controller(BaseController, {
            $scope,
            ExecutionRestService,
            modal_instance,
            modal_model,
            modal_callback,
            SharedPropertiesService,
        });

        installPromiseMatchers();
    });

    describe("validateIssueId() -", () => {
        it("Given that the linking modal was initialized, when I enter a bug artifact id, then it will be valid and will be attached to the controller", () => {
            const artifact_id = 52;
            const artifact    = {
                id     : artifact_id,
                title  : 'nonreceipt aroxyl',
                xref   : 'bug #52',
                tracker: {
                    id: issue_tracker_id
                }
            };
            spyOn(ExecutionRestService, "getArtifactById").and.returnValue($q.when(artifact));

            var promise = ExecutionLinkIssueController.validateIssueId('', artifact_id);

            expect(promise).toBeResolvedWith(true);
            expect(ExecutionRestService.getArtifactById).toHaveBeenCalledWith(artifact_id);
            expect(ExecutionLinkIssueController.issue_artifact).toBe(artifact);
            expect(ExecutionLinkIssueController.issue_artifact.tracker.color_name).toBe('flamingo_pink');
        });

        it("Given that the linking modal was initialized, when I enter an artifact id that isn't a bug, then it will not be valid and the promise will be rejected", () => {
            const artifact_id = 17;
            const artifact    = {
                id     : artifact_id,
                title  : 'nonprejudicial Elodeaceae',
                xref   : 'story #17',
                tracker: {
                    id: 10
                }
            };
            spyOn(ExecutionRestService, "getArtifactById").and.returnValue($q.when(artifact));

            var promise = ExecutionLinkIssueController.validateIssueId('', artifact_id);

            expect(promise).toBeRejected();
            expect(ExecutionRestService.getArtifactById).toHaveBeenCalledWith(artifact_id);
            expect(ExecutionLinkIssueController.issue_artifact).toBe(null);
        });
    });

    describe("linkIssue() -", () => {
        it("Given I had selected an issue, when I link it, then ExecutionRestService will be called, the modal will be hidden and the modal's callback will be called with the issue", () => {
            const issue_artifact = {
                id     : 39,
                title  : 'chromatinic duvet',
                xref   : 'bug #39',
                tracker: {
                    id        : issue_tracker_id,
                    color_name: 'fiesta-red'
                }
            };
            ExecutionLinkIssueController.issue_artifact = issue_artifact;
            ExecutionLinkIssueController.issue.id       = issue_artifact.id;
            spyOn(ExecutionRestService, "linkIssue").and.returnValue($q.when());

            ExecutionLinkIssueController.linkIssue();
            expect(ExecutionLinkIssueController.linking_in_progress).toBe(true);
            $scope.$apply();

            expect(ExecutionRestService.linkIssue).toHaveBeenCalledWith(
                issue_artifact.id,
                modal_model.test_execution
            );
            expect(modal_instance.tlp_modal.hide).toHaveBeenCalled();
            expect(modal_callback).toHaveBeenCalledWith(issue_artifact);
            expect(ExecutionLinkIssueController.linking_in_progress).toBe(false);
        });
    });
});
