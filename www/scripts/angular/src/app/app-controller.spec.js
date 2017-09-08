import angular from 'angular';
import 'angular-mocks';
import testmanagement_module from './app.js';

describe('TestManagementCtrl', function() {
  var TestManagementCtrl, $location, $scope;

  beforeEach(function() {
      angular.mock.module(testmanagement_module);
      angular.mock.inject(function($controller, _$location_, $rootScope) {
        $location   = _$location_;
        $scope      = $rootScope.$new();
        TestManagementCtrl = $controller('TestManagementCtrl', {$location: $location, $scope: $scope});
      });
  });

  it('has an init method', function() {
    expect($scope.init).toBeTruthy();
  });
});
