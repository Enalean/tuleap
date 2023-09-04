import angular from "angular";
import tuleap_pullrequest_module from "../app.js";
import pullrequest_controller from "./pull-request-controller.js";

import "angular-mocks";

describe("PullRequestController -", () => {
    let $rootScope,
        $state,
        $q,
        PullRequestController,
        PullRequestRestService,
        SharedPropertiesService;

    beforeEach(() => {
        let $controller;

        angular.mock.module(tuleap_pullrequest_module);

        angular.mock.inject(
            function (
                _$controller_,
                _$q_,
                _$rootScope_,
                _PullRequestRestService_,
                _SharedPropertiesService_,
            ) {
                $controller = _$controller_;
                $q = _$q_;
                $rootScope = _$rootScope_;
                PullRequestRestService = _PullRequestRestService_;
                SharedPropertiesService = _SharedPropertiesService_;
            },
        );

        $state = {
            go: () => {},
            params: {},
        };

        PullRequestController = $controller(pullrequest_controller, {
            $state,
            PullRequestRestService,
            SharedPropertiesService,
        });
    });

    describe("init()", () => {
        beforeEach(() => {
            jest.spyOn(SharedPropertiesService, "setPullRequest").mockImplementation(() => {});
            jest.spyOn(SharedPropertiesService, "setReadyPromise").mockImplementation(() => {});
            jest.spyOn(PullRequestRestService, "getPullRequest").mockReturnValue($q.when());
        });

        it("Given I have a pull request id in $state.params.id and the pull requests had not been initially loaded, then the pull_request will be loaded using the REST service and set in SharedPropertiesService", () => {
            const pull_request_id = 20;
            $state.params.id = pull_request_id;
            const pull_request = {
                id: pull_request_id,
            };

            const promise = $q.when(pull_request);
            PullRequestRestService.getPullRequest.mockReturnValue(promise);

            PullRequestController.$onInit();
            $rootScope.$apply();

            expect(PullRequestRestService.getPullRequest).toHaveBeenCalledWith(pull_request_id);
            expect(SharedPropertiesService.setReadyPromise).toHaveBeenCalledWith(promise);
            expect(SharedPropertiesService.setPullRequest).toHaveBeenCalledWith(pull_request);
        });
    });
});
