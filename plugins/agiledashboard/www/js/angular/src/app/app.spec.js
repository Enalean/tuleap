describe('MainCtrl', function() {
  var TestingCtrl, $location, $scope;

  beforeEach(module('planning'));

  beforeEach(inject(function($controller, _$location_, $rootScope) {
    $location   = _$location_;
    $scope      = $rootScope.$new();
    TestingCtrl = $controller('MainCtrl', {$location: $location, $scope: $scope});
  }));

  it('has an init method', inject(function() {
    expect($scope.init).toBeTruthy();
  }));
});
