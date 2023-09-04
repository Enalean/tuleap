import angular from "angular";

export default BacklogItemSelect;

BacklogItemSelect.$inject = ["$timeout", "BacklogItemSelectedService"];

function BacklogItemSelect($timeout, BacklogItemSelectedService) {
    return {
        restrict: "A",
        scope: {
            backlog_item: "=backlogItemSelect",
            backlog_item_index: "=backlogItemIndex",
        },
        link: link,
    };

    function link(scope, element) {
        element.bind("click", function (event) {
            if (event.ctrlKey || event.metaKey) {
                event.stopPropagation();
                backlogItemCtrlClicked(element);
            }
        });

        function backlogItemCtrlClicked(backlog_item_li) {
            if (backlogItemAlreadySelected()) {
                BacklogItemSelectedService.removeSelectedItem(scope.backlog_item_index);
                unhighlightBacklogItem();
            } else if (canItemBeSelectedDependingOnTheCurrentSelection(backlog_item_li)) {
                BacklogItemSelectedService.addSelectedItem(
                    scope.backlog_item,
                    scope.backlog_item_index,
                );
                highlightBacklogItem();
            } else {
                shakeBacklogItem();
            }

            scope.$apply();
        }

        function canItemBeSelectedDependingOnTheCurrentSelection(backlog_item_li) {
            var first_selected_item = BacklogItemSelectedService.getFirstSelectedItem();

            if (first_selected_item) {
                var first_selected_item_element = angular.element(
                    'div[data-item-id="' + first_selected_item.id + '"]',
                );

                return checkIfTwoElementsAreSiblings(first_selected_item_element, backlog_item_li);
            }

            return true;
        }

        function checkIfTwoElementsAreSiblings(first_element, second_element) {
            var first_element_parent = angular.element(first_element).parent()[0],
                second_element_parent = angular.element(second_element).parent()[0];

            return first_element_parent === second_element_parent;
        }

        function shakeBacklogItem() {
            var help_text = angular.element("#backlog-items-selected-bar > p:last-child");

            scope.backlog_item.shaking = true;
            help_text.addClass("focus");

            $timeout(function () {
                scope.backlog_item.shaking = false;
                help_text.removeClass("focus");
            }, 750);
        }

        function highlightBacklogItem() {
            scope.backlog_item.selected = true;
        }

        function unhighlightBacklogItem() {
            scope.backlog_item.selected = false;
        }

        function backlogItemAlreadySelected() {
            return scope.backlog_item.selected;
        }
    }
}
