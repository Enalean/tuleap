describe('MilestoneCtrl', function () {

    beforeEach(module('tuleap.planningApp'));

    it('defines an init method', inject(function($controller) {
        var scope = {},
            ctrl = $controller('MilestoneCtrl', {$scope:scope});

        expect(scope.init).toBeTruthy();
    }));

    it('defines an update method', inject(function($controller) {
        var scope = {},
            ctrl = $controller('MilestoneCtrl', {$scope:scope});

        expect(scope.update).toBeTruthy();
    }));
});
