import './add-in-place.tpl.html';
import AddInPlaceController from './add-in-place-controller.js';

export default AddInPlace;

AddInPlace.$inject = ['$timeout'];

function AddInPlace($timeout) {
    return {
        restrict    : 'E',
        controller  : AddInPlaceController,
        controllerAs: 'addInPlace',
        templateUrl : 'add-in-place.tpl.html',
        scope       : {
            column    : '=',
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
