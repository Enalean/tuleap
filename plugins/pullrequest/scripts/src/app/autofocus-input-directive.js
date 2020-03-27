export default AutoFocusInput;

AutoFocusInput.$inject = ["$timeout"];

function AutoFocusInput($timeout) {
    return {
        restrict: "A",
        link(scope, element) {
            $timeout(() => {
                element.focus();
            });
        },
    };
}
