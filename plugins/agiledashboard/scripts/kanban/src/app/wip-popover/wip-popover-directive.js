import "./wip-popover.tpl.html";
import angular from "angular";
import { createDropdown } from "@tuleap/tlp-dropdown";

export default WipPopover;

WipPopover.$inject = ["$timeout"];

function WipPopover($timeout) {
    return {
        restrict: "E",
        templateUrl: "wip-popover.tpl.html",
        scope: {
            column: "=",
            setWipLimit: "&setWipLimit",
        },
        link: function (scope) {
            $timeout(function () {
                createDropdown(
                    document.getElementById("kanban-column-header-wip-limit-" + scope.column.id)
                );
                toggleWipPopover();
            });

            function toggleWipPopover() {
                angular.element("body").click(function (event) {
                    var clicked_element = angular.element(event.target),
                        clicked_column_id = clicked_element
                            .parents(".column")
                            .attr("data-column-id");

                    if (!relatesTo("wip-limit-form")) {
                        $timeout(function () {
                            scope.column.wip_in_edit = false;
                        });

                        if (relatesTo("wip-limit") && clicked_column_id === scope.column.id) {
                            $timeout(function () {
                                scope.column.limit_input = scope.column.limit;
                                scope.column.wip_in_edit = true;

                                $timeout(function () {
                                    if (
                                        angular.element("#wip-limit-input-" + clicked_column_id)[0]
                                    ) {
                                        angular
                                            .element("#wip-limit-input-" + clicked_column_id)[0]
                                            .focus();
                                    }
                                });
                            });
                        }
                    }

                    function relatesTo(classname) {
                        return (
                            clicked_element.hasClass(classname) ||
                            clicked_element.parents("." + classname).length > 0
                        );
                    }
                });
            }
        },
    };
}
