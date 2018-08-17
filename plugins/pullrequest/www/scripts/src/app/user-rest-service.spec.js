import angular from "angular";
import tuleap_pullrequest_module from "tuleap-pullrequest-module";

import "angular-mocks";

describe("UserRestService -", function() {
    var $httpBackend, UserRestService, ErrorModalService;

    beforeEach(function() {
        angular.mock.module(tuleap_pullrequest_module);

        angular.mock.inject(function(_$httpBackend_, _UserRestService_, _ErrorModalService_) {
            $httpBackend = _$httpBackend_;
            UserRestService = _UserRestService_;
            ErrorModalService = _ErrorModalService_;
        });

        spyOn(ErrorModalService, "showError");

        installPromiseMatchers();
    });

    afterEach(function() {
        $httpBackend.verifyNoOutstandingExpectation();
        $httpBackend.verifyNoOutstandingRequest();
    });

    describe("getUser()", function() {
        it("Given a user id, when I get it, then a GET request will be sent to Tuleap and a promise will be resolved with a user object", function() {
            var user_id = 123;

            var user = {
                id: user_id,
                display_name: "Susanne Tickle (stickle)",
                avatar_url:
                    "https://mazalgia.com/semicurl/epitonic?a=pneumomalacia&b=superfinical#receptaculitid",
                user_url: "http://mucinoid.com/freely/prestudy?a=hauranitic&b=summerproof#roadtrack"
            };

            $httpBackend.expectGET("/api/v1/users/" + user_id).respond(angular.toJson(user));

            var promise = UserRestService.getUser(user_id);
            $httpBackend.flush();

            expect(promise).toBeResolvedWith(user);
        });

        it("when the server responds with an error, then the error modal will be shown", function() {
            var user_id = 12;

            $httpBackend.expectGET("/api/v1/users/" + user_id).respond(403, "Forbidden");

            var promise = UserRestService.getUser(user_id);
            $httpBackend.flush();

            expect(promise).toBeRejected();
            expect(ErrorModalService.showError).toHaveBeenCalledWith(
                jasmine.objectContaining({
                    status: 403,
                    statusText: ""
                })
            );
        });
    });
});
