describe('MainController', function() {
    var $scope;

    beforeEach(function() {
        module('tuleap.pull-request');

        var $controller, $rootScope;

        // eslint-disable-next-line angular/di
        inject(function(_$controller_, _$rootScope_) {
            $controller = _$controller_;
            $rootScope  = _$rootScope_;
        });

        $scope = $rootScope.$new();
        $controller('MainController', {
            $scope: $scope
        });
    });

    it('has an init method', function() {
        expect($scope.init).toBeTruthy();
    });
});
