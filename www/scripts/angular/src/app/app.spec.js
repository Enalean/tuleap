describe('TrafficlightsCtrl', function() {
  var TrafficlightsCtrl, $location, $scope;

  beforeEach(module('trafficlights'));

  beforeEach(inject(function($controller, _$location_, $rootScope) {
    $location   = _$location_;
    $scope      = $rootScope.$new();
    TrafficlightsCtrl = $controller('TrafficlightsCtrl', {$location: $location, $scope: $scope});
  }));

  it('has an init method', inject(function() {
    expect($scope.init).toBeTruthy();
  }));
});
