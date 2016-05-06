describe("ReleaseRestService -", function() {
    var $httpBackend, ReleaseRestService;

    beforeEach(function() {
        module('tuleap.frs');

        inject(function( // eslint-disable-line angular/di
            _$httpBackend_,
            _ReleaseRestService_
        ) {
            $httpBackend       = _$httpBackend_;
            ReleaseRestService = _ReleaseRestService_;
        });

        installPromiseMatchers();
    });

    afterEach(function() {
        $httpBackend.verifyNoOutstandingExpectation();
        $httpBackend.verifyNoOutstandingRequest();
    });

    it("Given a release id, when I get the release, then a GET request will be sent to Tuleap and a release object will be returned", function() {
        var release = {
            id     : 5,
            name   : "v1.0.4 iliopelvic misfortuned",
            package: {
                id  : 9,
                name: "preadvocate"
            }
        };

        $httpBackend.expectGET('/api/v1/frs_release/5').respond(angular.toJson(release));

        var promise = ReleaseRestService.getRelease(5);
        $httpBackend.flush();

        expect(promise).toBeResolvedWith(release);
    });
});
