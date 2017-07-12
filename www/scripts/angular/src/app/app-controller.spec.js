import angular from 'angular';
import 'angular-mocks';
import trafficlights_module from './app.js';

describe('TrafficlightsCtrl', function() {
  var TrafficlightsCtrl, $location, $scope;

  beforeEach(function() {
      angular.mock.module(trafficlights_module);
      angular.mock.inject(function($controller, _$location_, $rootScope) {
        $location   = _$location_;
        $scope      = $rootScope.$new();
        TrafficlightsCtrl = $controller('TrafficlightsCtrl', {$location: $location, $scope: $scope});
      });
  });

  it('has an init method', function() {
    expect($scope.init).toBeTruthy();
  });
});
