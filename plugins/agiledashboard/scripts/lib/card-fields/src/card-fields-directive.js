import tpl from "./card-fields.tpl.html?raw";
import controller from "./card-fields-controller.js";

export default () => {
    return {
        restrict: "AE",
        controller,
        controllerAs: "card_ctrl",
        bindToController: true,
        template: tpl,
        scope: {
            item: "=item",
            filter_terms: "=filterTerms",
        },
    };
};
