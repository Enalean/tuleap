import { createDropdown } from "tlp";

export default OpenTlpDropdown;

OpenTlpDropdown.$inject = [];

function OpenTlpDropdown() {
    return {
        restrict: "A",
        link: function (scope, element) {
            createDropdown(element[0]);
        },
    };
}
