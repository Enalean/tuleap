/*
 * Inspired from highlight filter in ui-utils
 * https://github.com/angular-ui/ui-utils/tree/d16cd00d129479eb1cde362bea034781b5bd1ff0/modules/highlight
 *
 * @license MIT
 */

import { isNumber } from "angular";
import { Classifier } from "./highlight/Classifier";
import { HighlightedText } from "./highlight/HighlightedText";

export default TuleapHighlightFilter;

TuleapHighlightFilter.$inject = [];

/**
 * @param text {string} haystack to search through
 * @param search {string} needle to search for
 * @returns HTML-encoded string
 */
function TuleapHighlightFilter() {
    function isTextSearchable(text, search) {
        return text && (search || isNumber(search));
    }

    return function (text, search) {
        if (!isTextSearchable(text, search)) {
            return text ? text.toString() : text;
        }

        const classifier = Classifier(String(search));
        const parts = classifier.classify(String(text)).map((highlighted_text) => {
            if (!HighlightedText.isHighlight(highlighted_text)) {
                return highlighted_text.content;
            }
            return `<span class="highlight">${highlighted_text.content}</span>`;
        });
        return parts.join("");
    };
}
