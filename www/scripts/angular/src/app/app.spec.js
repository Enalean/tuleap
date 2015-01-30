describe('TestingCtrl', function() {
  var TestingCtrl, $location, $scope;

  beforeEach(module('testing'));
  beforeEach(module('user'));

  beforeEach(inject(function($controller, _$location_, $rootScope) {
    $location   = _$location_;
    $scope      = $rootScope.$new();
    TestingCtrl = $controller('TestingCtrl', {$location: $location, $scope: $scope});
  }));

  it('has an init method', inject(function() {
    expect($scope.init).toBeTruthy();
  }));
});
