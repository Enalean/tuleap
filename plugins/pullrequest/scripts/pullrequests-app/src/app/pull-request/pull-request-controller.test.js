import angular from "angular";
import tuleap_pullrequest_module from "../app.js";
import pullrequest_controller from "./pull-request-controller.js";
import * as window_helpers from "../window-helper";
import * as overview_url_helper from "../helpers/overview-url-builder";

import "angular-mocks";

const noop = () => {
    // Do nothing
};

const overview_url = "url/to/overview";

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
            go: noop,
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
            jest.spyOn(SharedPropertiesService, "setPullRequest");
            jest.spyOn(SharedPropertiesService, "setReadyPromise").mockImplementation(noop);
            jest.spyOn(PullRequestRestService, "getPullRequest").mockReturnValue($q.when());
            jest.spyOn(window_helpers, "redirectToUrl").mockImplementation(noop);
            jest.spyOn(overview_url_helper, "buildOverviewURL").mockReturnValue(overview_url);
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

        it("Given I have a pull request with a broken git reference, then it should redirect to the overview page.", () => {
            const pull_request_id = 20;
            const project_id = 102;
            const repository_id = 1;
            const pull_request = {
                id: pull_request_id,
                is_git_reference_broken: true,
            };

            $state.params.id = pull_request_id;

            SharedPropertiesService.setProjectId(project_id);
            SharedPropertiesService.setRepositoryId(repository_id);

            const promise = $q.when(pull_request);
            PullRequestRestService.getPullRequest.mockReturnValue(promise);

            PullRequestController.$onInit();
            $rootScope.$apply();

            expect(overview_url_helper.buildOverviewURL).toHaveBeenCalledWith(
                expect.anything(),
                pull_request,
                project_id,
                repository_id,
            );
            expect(window_helpers.redirectToUrl).toHaveBeenCalledWith(overview_url);
        });
    });
});
