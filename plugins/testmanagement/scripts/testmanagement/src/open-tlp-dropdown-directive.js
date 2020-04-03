import * as tlp from "tlp";

export default OpenTlpDropdown;

OpenTlpDropdown.$inject = [];

function OpenTlpDropdown() {
    return {
        restrict: "A",
        link: function (scope, element) {
            tlp.dropdown(element[0]);
        },
    };
}
