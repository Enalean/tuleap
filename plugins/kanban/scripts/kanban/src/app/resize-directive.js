import angular from "angular";

export default ResizeDirective;

ResizeDirective.$inject = ["$window"];

function ResizeDirective($window) {
    return {
        restrict: "A",
        link: function (scope) {
            angular.element($window).bind("resize", function () {
                scope.$broadcast("rebuild:kustom-scroll");
            });
        },
    };
}
