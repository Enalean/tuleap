angular.module('tuleap.pull-request')
    .directive('labels', Labels);

Labels.$inject = ['$window', 'SharedPropertiesService'];

function Labels(
    $window,
    SharedPropertiesService
) {
    return {
        restrict: 'E',
        scope: {
            pullRequestId: '@',
            projectId: '@'
        },
        link: function (scope, element) {
            if (scope.pullRequestId && scope.projectId) {
                createLabelsBox(scope.pullRequestId, scope.projectId, 0);
                return;
            }

            SharedPropertiesService.whenReady().then(function () {
                var pull_request = SharedPropertiesService.getPullRequest();

                createLabelsBox(
                    pull_request.id,
                    pull_request.repository_dest.project.id,
                    pull_request.user_can_update_labels
                );
            });

            function createLabelsBox(pull_request_id, project_id, user_can_update_labels) {
                $window.LabelsBox.create(
                    element[0],
                    '/api/v1/pull_requests/' + pull_request_id + '/labels',
                    '/api/v1/projects/' + project_id + '/labels',
                    user_can_update_labels
                );
            }
        }
    };
}
