import angular from "angular";

export default focusOnClickDirective;

focusOnClickDirective.$inject = ["$timeout"];

function focusOnClickDirective($timeout) {
    return {
        restrict: "A",
        link: linkFunction,
    };

    function linkFunction(scope, element, attributes) {
        element.on("click", function () {
            focus(attributes.tuleapFocusOnClick);
        });

        scope.$on("$destroy", function () {
            element.off("click");
        });
    }

    function focus(id) {
        // Timeout ensures that other events have run, e.g. the click event that
        // triggered it
        $timeout(function () {
            var element = angular.element("#" + id);
            if (element) {
                element.focus();
            }
        });
    }
}
