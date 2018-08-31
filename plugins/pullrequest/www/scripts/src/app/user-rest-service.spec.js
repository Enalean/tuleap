import angular from "angular";
import tuleap_pullrequest_module from "tuleap-pullrequest-module";

import "angular-mocks";

describe("UserRestService -", function() {
    let $httpBackend, UserRestService, ErrorModalService;

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
        it("Given an user id, when I get it, then a GET request will be sent to Tuleap and a promise will be resolved with a user object", function() {
            const user_id = 123;

            const user = {
                id: user_id,
                display_name: "Susanne Tickle (stickle)",
                avatar_url:
                    "https://mazalgia.com/semicurl/epitonic?a=pneumomalacia&b=superfinical#receptaculitid",
                user_url: "http://mucinoid.com/freely/prestudy?a=hauranitic&b=summerproof#roadtrack"
            };

            $httpBackend.expectGET("/api/v1/users/" + user_id).respond(angular.toJson(user));

            const promise = UserRestService.getUser(user_id);
            $httpBackend.flush();

            expect(promise).toBeResolvedWith(user);
        });

        it("when the server responds with an error, then the error modal will be shown", function() {
            const user_id = 12;

            $httpBackend.expectGET("/api/v1/users/" + user_id).respond(403, "Forbidden");

            const promise = UserRestService.getUser(user_id);

            expect(promise).toBeRejected();
            expect(ErrorModalService.showError).toHaveBeenCalledWith(
                jasmine.objectContaining({
                    status: 403,
                    statusText: ""
                })
            );
        });
    });

    describe("getPreference()", () => {
        it("Given an user id and an user preference, when I get it, then a GET request will be sent to Tuleap and a promise will be resolved with a user object", () => {
            const user_id = 123;

            const preference = {
                key: "preferred_color",
                value: "red"
            };

            $httpBackend
                .expectGET("/api/v1/users/123/preferences?key=preferred_color")
                .respond(angular.toJson(preference));

            const promise = UserRestService.getPreference(user_id, "preferred_color");
            $httpBackend.flush();

            expect(promise).toBeResolvedWith(preference);
        });

        it("when the server responds with an error, then the error modal will be shown", () => {
            const user_id = 12;

            $httpBackend
                .expectGET("/api/v1/users/12/preferences?key=preferred_color")
                .respond(403, "Forbidden");

            const promise = UserRestService.getPreference(user_id, "preferred_color");

            expect(promise).toBeRejected();
            expect(ErrorModalService.showError).toHaveBeenCalledWith(
                jasmine.objectContaining({
                    status: 403,
                    statusText: ""
                })
            );
        });
    });

    describe("setPreference", () => {
        it("Given an user id, an user_preference and a value, When it set it, Then a PATCH request will be sent to Tuleap.", () => {
            const user_id = 666;

            $httpBackend
                .expectPATCH("/api/v1/users/666/preferences", {
                    key: "religion",
                    value: "Satan"
                })
                .respond(200, "ok");

            const promise = UserRestService.setPreference(user_id, "religion", "Satan");

            expect(promise).toBeResolved();
        });
    });
});
