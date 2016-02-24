describe('MainController', function() {
  var MainController, $scope;

  beforeEach(module('tuleap.pull-request'));

  beforeEach(inject(function($controller, $rootScope) {
    $scope         = $rootScope.$new();
    MainController = $controller('MainController', {$scope: $scope});
  }));

  it('has an init method', inject(function() {
    expect($scope.init).toBeTruthy();
  }));
});
