/* eslint-disable max-nested-callbacks */
describe('MainController', function() {
    var $scope,
        SharedPropertiesService,
        repoId,
        userId;

    beforeEach(function() {
        module('tuleap.pull-request');

        var $controller, $rootScope;

        // eslint-disable-next-line angular/di
        inject(function(
            _$controller_,
            _$rootScope_,
            _SharedPropertiesService_
        ) {
            $controller             = _$controller_;
            $rootScope              = _$rootScope_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        repoId = 1;
        userId = 101;

        $scope = $rootScope.$new();
        $controller('MainController', {
            $scope: $scope
        });
    });

    describe('init()', function() {
        it('sets some shared properties', function() {
            $scope.init(repoId, userId, 'fr');

            expect(SharedPropertiesService.getRepositoryId()).toEqual(repoId);
            expect(SharedPropertiesService.getUserId()).toEqual(userId);
        });
    });
});
