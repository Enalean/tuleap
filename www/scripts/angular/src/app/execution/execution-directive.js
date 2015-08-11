(function () {
    angular
        .module('trafficlights')
        .directive('adaptHeight', AdaptHeight);

    AdaptHeight.$inject = ['$timeout'];

    function AdaptHeight($timeout) {
        return {
            link: {
                pre: link
            }
        };

        function link(scope, element, attrs) {
            $timeout(function () {
                var execution_height = angular.element('#current-execution').outerHeight(),
                    title_height = angular.element('#current-execution-title').outerHeight(),
                    last_execution = angular.element('#current-execution-last-execution').outerHeight(),
                    result = angular.element('#current-execution-result').outerHeight();

                element.css('height',  execution_height - title_height - last_execution - result);
            });
        }
    }
})();