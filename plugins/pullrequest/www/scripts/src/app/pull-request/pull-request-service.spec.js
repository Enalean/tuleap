import angular from "angular";
import tuleap_pullrequest_module from "tuleap-pullrequest-module";

import "angular-mocks";

describe("PullRequestService -", function() {
    var PullRequestService;

    beforeEach(function() {
        angular.mock.module(tuleap_pullrequest_module);

        // eslint-disable-next-line angular/di
        angular.mock.inject(function(_PullRequestService_) {
            PullRequestService = _PullRequestService_;
        });
    });

    describe("isPullRequestClosed()", function() {
        it("Given a pull request with the 'merge' status, then it will return true", function() {
            var pull_request = {
                status: "merge"
            };

            var result = PullRequestService.isPullRequestClosed(pull_request);

            expect(result).toBe(true);
        });

        it("Given a pull request with the 'abandon' status, then it will return true", function() {
            var pull_request = {
                status: "abandon"
            };

            var result = PullRequestService.isPullRequestClosed(pull_request);

            expect(result).toBe(true);
        });

        it("Given a pull request with the 'review' status, then it will return false", function() {
            var pull_request = {
                status: "review"
            };

            var result = PullRequestService.isPullRequestClosed(pull_request);

            expect(result).toBe(false);
        });
    });
});
