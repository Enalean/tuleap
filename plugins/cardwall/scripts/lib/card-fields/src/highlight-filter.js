/*
 * Inspired from highlight filter in ui-utils
 * https://github.com/angular-ui/ui-utils/tree/d16cd00d129479eb1cde362bea034781b5bd1ff0/modules/highlight
 *
 * @license MIT
 */

import { highlightFilterElements } from "./highlight-filter-template";

export default TuleapHighlightFilter;

TuleapHighlightFilter.$inject = [];

/**
 * @param text {string} haystack to search through
 * @param search {string} needle to search for
 * @returns HTML-encoded string
 */
function TuleapHighlightFilter() {
    function getHTMLStringFromTemplate(template) {
        const element = document.createElement("div");
        template({}, element);

        return element.innerHTML;
    }

    return function (text, search) {
        if (text === null) {
            return null;
        }
        return getHTMLStringFromTemplate(highlightFilterElements(text, search));
    };
}
