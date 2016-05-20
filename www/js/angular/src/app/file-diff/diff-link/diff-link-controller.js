angular
    .module('tuleap.pull-request')
    .controller('DiffLinkController', DiffLinkController);

DiffLinkController.$inject = [
    '$state',
    'lodash',
    'SharedPropertiesService',
    'FilepathsService'
];

function DiffLinkController(
    $state,
    lodash,
    SharedPropertiesService,
    FilepathsService
) {
    var self = this;
    var file_path = $state.params.file_path;
    var pull_request = SharedPropertiesService.getPullRequest();
    var previousPath = FilepathsService.previous(file_path);
    var nextPath = FilepathsService.next(file_path);

    lodash.extend(self, {
        file_path   : file_path,
        pull_request: pull_request,
        nextPath    : nextPath,
        previousPath: previousPath,
        nextLink    : nextPath ? $state.href('diff', { id: pull_request.id, file_path: nextPath }) : '',
        previousLink: previousPath ? $state.href('diff', { id: pull_request.id, file_path: previousPath }) : ''
    });
}
