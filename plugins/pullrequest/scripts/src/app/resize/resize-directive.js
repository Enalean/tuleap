import { element as angularElement } from "angular";

export default resize;

resize.$inject = ["$timeout", "$window"];

function resize($timeout, $window) {
    return {
        restrict: "AE",
        scope: false,
        link: link,
    };

    function link(scope, element) {
        scope.$watch(watchExpression, listener);
        scope.$on("code_mirror_initialized", listener);

        bindWindowResizeEvent();

        function watchExpression() {
            return $window.document.body.clientHeight;
        }

        function bindWindowResizeEvent() {
            return angularElement($window).bind("resize", function () {
                scope.$apply();
            });
        }

        function listener() {
            $timeout(function () {
                var children = element.children();
                if (children.length === 0) {
                    return;
                }
                var code_mirror_div = children[0];
                code_mirror_div.style.height = element[0].clientHeight + "px";
            });
        }
    }
}
