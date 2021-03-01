import tpl from "./card-computed-field.tpl.html?raw";

export default cardComputedField;

function cardComputedField() {
    return {
        restrict: "AE",
        scope: {
            card_field: "=field",
            filter_terms: "=filterTerms",
        },
        template: tpl,
    };
}
