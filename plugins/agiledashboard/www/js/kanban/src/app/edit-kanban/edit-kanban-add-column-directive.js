angular
    .module('kanban')
    .directive('addColumnInput', AddColumnInput);

AddColumnInput.$inject = ['$timeout'];

function AddColumnInput($timeout) {
    return {
        restrict: 'A',
        link: function (scope, element, attrs) {
            scope.$watch('adding_column', function(adding_column) {
                if (adding_column) {
                    $timeout(autoFocusInput);
                }
            });

            function autoFocusInput() {
                element.focus();
            }
        }
    };
}
