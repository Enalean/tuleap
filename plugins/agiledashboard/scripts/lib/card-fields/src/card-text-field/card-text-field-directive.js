import tpl from "./card-text-field.tpl.html?raw";
import striptags from "striptags";
import { decode } from "he";

export default () => {
    const HTML_FORMAT = "html";

    return {
        restrict: "AE",
        scope: {
            card_field: "=field",
            filter_terms: "@filterTerms",
        },
        template: tpl,
        link,
    };

    function link(scope) {
        scope.getDisplayableValue = getDisplayableValue;
    }

    function getDisplayableValue({ format, value }) {
        return format === HTML_FORMAT ? striptags(decode(value)) : value;
    }
};
