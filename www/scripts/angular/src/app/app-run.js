angular
    .module('testing')
    .run(TestingRun);

TestingRun.$inject = ['$rootScope', '$state', 'SharedPropertiesService'];

function TestingRun($rootScope, $state, SharedPropertiesService) {
    $rootScope.$on("$stateChangeStart", function(event, toState, toParams, fromState, fromParams) {
        if (toState.authenticate && ! SharedPropertiesService.getCurrentUser()) {
            $state.go('login');
            event.preventDefault();
        }
    });
}