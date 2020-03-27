/*
 * Inspired from highlight filter in ui-utils
 * https://github.com/angular-ui/ui-utils/tree/d16cd00d129479eb1cde362bea034781b5bd1ff0/modules/highlight
 *
 * @license MIT
 */

import { isNumber } from "angular";
import escapeStringRegexp from "escape-string-regexp";
import { encode } from "he";

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
            return text !== null ? encode(text.toString()) : text;
        }

        const regexp_escaped_search = escapeStringRegexp(search.toString());
        const or_search = regexp_escaped_search.replace(" ", "|");

        const regex = new RegExp("(" + or_search + ")", "gi");

        const split_html = text.toString().split(regex);
        const encoded_parts = split_html.map((part) => {
            if (regex.test(part)) {
                return `<span class="highlight">${encode(part)}</span>`;
            }

            return encode(part);
        });
        return encoded_parts.join("");
    };
}
