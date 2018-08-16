export default ResizeDirective;

ResizeDirective.$inject = ["$window"];

function ResizeDirective($window) {
    return {
        restrict: "A",
        link: function(scope, board_element, attr) {
            angular.element($window).bind("resize", function() {
                scope.$broadcast("rebuild:kustom-scroll");
            });
        }
    };
}
