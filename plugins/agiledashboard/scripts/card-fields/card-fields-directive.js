import "./card-fields.tpl.html";
import controller from "./card-fields-controller.js";

export default () => {
    return {
        restrict: "AE",
        controller,
        controllerAs: "card_ctrl",
        bindToController: true,
        templateUrl: "card-fields.tpl.html",
        scope: {
            item: "=item",
            filter_terms: "=filterTerms",
        },
    };
};
