angular.module('tuleap.pull-request')
    .directive('labels', Labels);

Labels.$inject = ['$window', 'SharedPropertiesService'];

function Labels(
    $window,
    SharedPropertiesService
) {
    return {
        restrict: 'E',
        link: function (scope, element) {
            SharedPropertiesService.whenReady().then(function () {
                var pull_request = SharedPropertiesService.getPullRequest();

                $window.LabelsBox.create(
                    element[0],
                    '/api/v1/pull_requests/' + pull_request.id + '/labels',
                    '/api/v1/projects/' + pull_request.repository_dest.project.id + '/labels',
                    pull_request.user_can_update_labels
                );
            });
        }
    };
}
