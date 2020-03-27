import controller from "./item-progress-controller.js";

export default {
    template: '<div class="item-progress {{ $ctrl.color_name }}" ng-style="$ctrl.style"></div>',
    controller,
    bindings: {
        color_name: "@colorName",
        value: "<",
        max_value: "<maxValue",
    },
};
