export default TuleapHighlightDirective;

TuleapHighlightDirective.$inject = ["$timeout"];

function TuleapHighlightDirective($timeout) {
    var promise;

    return {
        restrict: "A",
        scope: {
            watched: "=*tuleapHighlightDirective",
        },
        link: function ($scope, element) {
            $scope.$watch(
                function () {
                    return $scope.watched;
                },
                function (new_value, old_value) {
                    if (new_value === old_value) {
                        return;
                    }

                    applyHighlight(element);
                }
            );

            $scope.$on("$destroy", function () {
                destroy();
            });
        },
    };

    function applyHighlight(element) {
        removeTransition(element);
        highlight(element);

        promise = $timeout(function () {
            addTransition(element);
            lowlight(element);
        });
    }

    function destroy() {
        $timeout.cancel(promise);
    }

    function addTransition(element) {
        element.addClass("tuleap-highlight-transition");
    }

    function removeTransition(element) {
        element.removeClass("tuleap-highlight-transition");
    }

    function highlight(element) {
        element.addClass("tuleap-highlight");
    }

    function lowlight(element) {
        element.removeClass("tuleap-highlight");
    }
}
