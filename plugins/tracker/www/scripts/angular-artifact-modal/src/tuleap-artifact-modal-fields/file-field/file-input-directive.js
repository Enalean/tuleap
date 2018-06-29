export default function() {
    return {
        restrict: "A",
        replace: false,
        require: "ngModel",
        link(scope, $element, attributes, ngModelController) {
            const element = $element[0];
            $element.on("input", () => {
                const [file] = element.files;
                ngModelController.$setViewValue(file);
            });
        }
    };
}
