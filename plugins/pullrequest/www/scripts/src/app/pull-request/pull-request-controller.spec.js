import angular from "angular";
import tuleap_pullrequest_module from "tuleap-pullrequest-module";
import pullrequest_controller from "./pull-request-controller.js";

import "angular-mocks";

describe("PullRequestController -", function() {
    var $rootScope,
        $state,
        $q,
        PullRequestController,
        PullRequestRestService,
        SharedPropertiesService;

    beforeEach(function() {
        var $controller;

        angular.mock.module(tuleap_pullrequest_module);

        // eslint-disable-next-line angular/di
        angular.mock.inject(function(
            _$controller_,
            _$q_,
            _$rootScope_,
            _$state_,
            _PullRequestRestService_,
            _SharedPropertiesService_
        ) {
            $controller = _$controller_;
            $q = _$q_;
            $rootScope = _$rootScope_;
            $state = _$state_;
            PullRequestRestService = _PullRequestRestService_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        spyOn(SharedPropertiesService, "setPullRequest");
        spyOn(SharedPropertiesService, "setReadyPromise");
        spyOn(PullRequestRestService, "getPullRequest").and.returnValue($q.when());

        PullRequestController = $controller(pullrequest_controller);
        $rootScope.$apply();

        installPromiseMatchers();
    });

    describe("init()", function() {
        it("Given I have a pull request id in $state.params.id and the pull requests had not been initially loaded, when I create the controller, then the pull_request will be loaded using the REST service and set in SharedPropertiesService", function() {
            var pull_request_id = 20;
            $state.params.id = pull_request_id;
            var pull_request = {
                id: pull_request_id
            };

            var promise = $q.when(pull_request);
            PullRequestRestService.getPullRequest.and.returnValue(promise);

            PullRequestController.init();
            $rootScope.$apply();

            expect(PullRequestRestService.getPullRequest).toHaveBeenCalledWith(pull_request_id);
            expect(SharedPropertiesService.setReadyPromise).toHaveBeenCalledWith(promise);
            expect(SharedPropertiesService.setPullRequest).toHaveBeenCalledWith(pull_request);
        });
    });
});
