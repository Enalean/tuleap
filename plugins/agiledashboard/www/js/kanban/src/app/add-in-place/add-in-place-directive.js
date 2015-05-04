angular
    .module('kanban')
    .directive('addInPlace', AddInPlace);

AddInPlace.$inject = ['$timeout'];

function AddInPlace($timeout) {
    return {
        restrict: 'E',
        controller: 'AddInPlaceCtrl',
        controllerAs: 'addInPlace',
        templateUrl: 'add-in-place/add-in-place.tpl.html',
        scope: {
            column: '=',
            createItem: '='
        },
        link: function (scope, element, attrs, addInPlaceCtrl) {

            addInPlaceCtrl.init(scope.column, scope.createItem);

            scope.$watch('addInPlace.isOpen()', function (is_open) {
                if (is_open) {
                    $timeout(autoFocusInput);
                }
            });

            function autoFocusInput() {
                element.find('input[type=text]').focus();
            }
        }
    };
}