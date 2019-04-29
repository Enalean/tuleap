import * as tlp from "tlp";

export default EnableTlpTableFilter;

EnableTlpTableFilter.$inject = ["$timeout"];

function EnableTlpTableFilter($timeout) {
    function preventSubmit(event) {
        var event = event || window.event;
        var key_code = event.charCode;

        if (event.keyCode) {
            key_code = event.keyCode;
        } else if (event.which) {
            key_code = event.which;
        }

        if (key_code === 13) {
            event.cancelBubble = true;
            event.returnValue = false;

            if (event.stopPropagation) {
                event.stopPropagation();
                event.preventDefault();
            }
        }
    }

    return {
        restrict: "A",
        link: function(scope, element) {
            $timeout(function() {
                var filterField = element[0];
                tlp.filterInlineTable(filterField);
                filterField.addEventListener("keydown", preventSubmit);
            });
        }
    };
}
