describe("PullRequestController -", function() {
    var $rootScope,
        $state,
        $q,
        PullRequestController,
        PullRequestRestService,
        PullRequestCollectionService,
        SharedPropertiesService;

    beforeEach(function() {
        var $controller;

        module('tuleap.pull-request');

        // eslint-disable-next-line angular/di
        inject(function(
            _$controller_,
            _$q_,
            _$rootScope_,
            _$state_,
            _PullRequestRestService_,
            _PullRequestCollectionService_,
            _SharedPropertiesService_
        ) {
            $controller                  = _$controller_;
            $q                           = _$q_;
            $rootScope                   = _$rootScope_;
            $state                       = _$state_;
            PullRequestRestService       = _PullRequestRestService_;
            PullRequestCollectionService = _PullRequestCollectionService_;
            SharedPropertiesService      = _SharedPropertiesService_;
        });

        spyOn(SharedPropertiesService, "setPullRequest");
        spyOn(SharedPropertiesService, "setReadyPromise");
        spyOn(PullRequestRestService, "getPullRequest").and.returnValue($q.when());
        spyOn(PullRequestCollectionService, "search");
        PullRequestCollectionService.pull_requests_fully_loaded = false;

        PullRequestController = $controller('PullRequestController');
        $rootScope.$apply();

        installPromiseMatchers();
    });

    describe("init()", function() {
        it("Given I have a pull request id in $state.params.id and the pull requests had not been initially loaded, when I create the controller, then the pull_request will be loaded using the REST service and set in SharedPropertiesService", function() {
            var pull_request_id = 20;
            $state.params.id   = pull_request_id;
            var pull_request    = {
                id: pull_request_id
            };

            var promise = $q.when(pull_request);
            PullRequestRestService.getPullRequest.and.returnValue(promise);
            PullRequestCollectionService.pull_requests_fully_loaded = false;

            PullRequestController.init();
            $rootScope.$apply();

            expect(PullRequestRestService.getPullRequest).toHaveBeenCalledWith(pull_request_id);
            expect(SharedPropertiesService.setReadyPromise).toHaveBeenCalledWith(promise);
            expect(SharedPropertiesService.setPullRequest).toHaveBeenCalledWith(pull_request);
        });

        it("Given the pull requests had already been loaded, when I create the controller, then the pull_request will be searched in the collection of loaded pull_requests and set in SharedPropertiesService", function() {
            var pull_request_id = 98;
            $state.params.id = pull_request_id;
            var pull_request = {
                id: pull_request_id
            };
            PullRequestCollectionService.search.and.returnValue(pull_request);
            SharedPropertiesService.setReadyPromise.and.callThrough();
            PullRequestCollectionService.pull_requests_fully_loaded = true;

            PullRequestController.init();
            $rootScope.$apply();

            expect(SharedPropertiesService.whenReady()).toBeResolved();
            expect(PullRequestCollectionService.search).toHaveBeenCalledWith(pull_request_id);
            expect(SharedPropertiesService.setPullRequest).toHaveBeenCalledWith(pull_request);
        });

        it("Given the pull requests had already been loaded but the pull request id in $state.params.id was not among them, when I create the controller, then the ready promise in SharedPropertiesService will be rejected", function() {
            SharedPropertiesService.setPullRequest.calls.reset();
            var pull_request_id = 34;
            $state.params.id = pull_request_id;
            PullRequestCollectionService.search.and.returnValue();
            SharedPropertiesService.setReadyPromise.and.callThrough();
            PullRequestCollectionService.pull_requests_fully_loaded = true;

            PullRequestController.init();
            $rootScope.$apply();

            expect(SharedPropertiesService.whenReady()).toBeRejected();
            expect(PullRequestCollectionService.search).toHaveBeenCalledWith(pull_request_id);
            expect(SharedPropertiesService.setPullRequest.calls.count()).toEqual(0);
        });
    });
});
