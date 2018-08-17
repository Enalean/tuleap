export default AutoFocusInput;

AutoFocusInput.$inject = ["$timeout"];

function AutoFocusInput($timeout) {
    return {
        restrict: "A",
        scope: {},
        link: function(scope, element, attrs) {
            $timeout(autoFocusInput);

            function autoFocusInput() {
                element.focus();
            }
        }
    };
}
