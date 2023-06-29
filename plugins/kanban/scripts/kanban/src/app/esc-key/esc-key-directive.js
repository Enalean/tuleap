import angular from "angular";

export default EscKeyDirective;

EscKeyDirective.$inject = [];

function EscKeyDirective() {
    return function (scope, element, attrs) {
        angular.element("body").bind("keydown keypress", function (event) {
            var ESC_KEY_CODE = 27;

            if (event.which === ESC_KEY_CODE) {
                scope.$apply(function () {
                    scope.$eval(attrs.escKey);
                });
            }
        });
    };
}
