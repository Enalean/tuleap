import "./card-text-field.tpl.html";
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
        templateUrl: "card-text-field.tpl.html",
        link,
    };

    function link(scope) {
        scope.getDisplayableValue = getDisplayableValue;
    }

    function getDisplayableValue({ format, value }) {
        return format === HTML_FORMAT ? striptags(decode(value)) : value;
    }
};
