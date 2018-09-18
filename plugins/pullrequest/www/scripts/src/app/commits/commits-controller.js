export default CommitsController;

CommitsController.$inject = ["$state", "$window", "CommitsRestService", "SharedPropertiesService"];

function CommitsController($state, $window, CommitsRestService, SharedPropertiesService) {
    const self = this;

    Object.assign(self, {
        $state,
        pull_request: {},
        list: [],
        is_loading_commits: true,
        shouldDisplayWarningMessage,
        shouldDisplayListOfCommits,
        $onInit: init
    });

    function init() {
        SharedPropertiesService.whenReady()
            .then(() => {
                self.pull_request = SharedPropertiesService.getPullRequest();
                return getCommits();
            })
            .catch(function() {
                //Do nothing
            })
            .finally(() => {
                self.is_loading_commits = false;
            });
    }

    function shouldDisplayWarningMessage() {
        if (self.is_loading_commits) {
            return false;
        }

        return self.list.length === 0;
    }

    function shouldDisplayListOfCommits() {
        if (self.is_loading_commits) {
            return false;
        }

        return self.list.length > 0;
    }

    function getCommits() {
        return CommitsRestService.getPaginatedCommits(self.pull_request.id, 50, 0, response => {
            self.list = self.list.concat(response.data.map(augmentMetadata));
        });
    }

    function augmentMetadata(commit) {
        let avatar_url = null;
        let display_name = commit.author_name;

        if (commit.author) {
            avatar_url = commit.author.avatar_url;
            display_name = commit.author.display_name;
        }

        const goToAuthor = $event => {
            $event.preventDefault();
            $window.location.href = commit.author.user_url;
        };

        return {
            short_id: commit.id.substring(0, 10),
            avatar_url,
            display_name,
            goToAuthor,
            ...commit
        };
    }
}
