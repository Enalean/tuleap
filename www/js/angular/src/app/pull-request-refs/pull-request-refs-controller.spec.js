describe("PullRequestRefsController -", function() {
    var PullRequestRefsController,
        SharedPropertiesService;

    beforeEach(function() {
        var $controller;

        module('tuleap.pull-request');

        // eslint-disable-next-line angular/di
        inject(function(
            _$controller_,
            _SharedPropertiesService_
        ) {
            $controller             = _$controller_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        PullRequestRefsController = $controller('PullRequestRefsController', {
            SharedPropertiesService: SharedPropertiesService
        });

        spyOn(SharedPropertiesService, "getRepositoryId");
    });

    describe("isCurrentRepository()", function() {
        it("Given the current repository id in SharedPropertiesService and given a repository object with the same id, when I check if it is the current repository, then it will return true", function() {
            SharedPropertiesService.getRepositoryId.and.returnValue(14);

            var repository = {
                id: 14
            };

            var result = PullRequestRefsController.isCurrentRepository(repository);

            expect(result).toBe(true);
        });
    });

    describe("isRepositoryAFork()", function() {
        it("Given a pull request with a repository id different of its repository_dest id, when I check if the repository is a fork, then it will return true", function() {
            PullRequestRefsController.pull_request = {
                repository: {
                    id: 22
                },
                repository_dest: {
                    id: 61
                }
            };

            var result = PullRequestRefsController.isRepositoryAFork();

            expect(result).toBe(true);
        });
    });
});
