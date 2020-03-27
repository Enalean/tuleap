import { select2 } from "tlp";

export default function () {
    return {
        restrict: "A",
        link: function (scope, element, attributes) {
            var options = scope.$eval(attributes.tlpSelect2);
            select2(element[0], options);
        },
    };
}
