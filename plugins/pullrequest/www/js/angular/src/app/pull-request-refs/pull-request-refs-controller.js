angular
    .module('tuleap.pull-request')
    .controller('PullRequestRefsController', PullRequestRefsController);

PullRequestRefsController.$inject = [
    'lodash',
    'SharedPropertiesService'
];

function PullRequestRefsController(
    _,
    SharedPropertiesService
) {
    var self = this;

    _.extend(self, {
        isCurrentRepository: isCurrentRepository,
        isRepositoryAFork  : isRepositoryAFork
    });


    function isCurrentRepository(repository) {
        return (_.get(repository, 'id') === SharedPropertiesService.getRepositoryId());
    }

    function isRepositoryAFork() {
        return (_.get(self.pull_request.repository, 'id') !== _.get(self.pull_request.repository_dest, 'id'));
    }
}
