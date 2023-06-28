/*
 * Inspired from highlight filter in ui-utils
 * https://github.com/angular-ui/ui-utils/tree/d16cd00d129479eb1cde362bea034781b5bd1ff0/modules/highlight
 *
 * @license MIT
 */
import angular from "angular";
import "angular-mocks";

import card_fields_module from "./index.js";

describe("tuleapHighlight", () => {
    let highlightFilter, test_phrase;

    beforeEach(() => {
        angular.mock.module(card_fields_module);

        angular.mock.inject(function ($filter) {
            highlightFilter = $filter("tuleapHighlight");
        });

        test_phrase = "Prefix Highlight Suffix";
    });

    it("should highlight a matching phrase", () => {
        expect(highlightFilter(test_phrase, "highlight")).toBe(
            'Prefix <span class="highlight">Highlight</span> Suffix'
        );
    });

    it("should highlight nothing if no match found", () => {
        expect(highlightFilter(test_phrase, "no match")).toEqual(test_phrase);
    });

    it("should highlight nothing for the undefined filter", () => {
        expect(highlightFilter(test_phrase, undefined)).toEqual(test_phrase);
    });

    it("should work correctly if text is null", () => {
        expect(highlightFilter(null, "highlight")).toBeNull();
    });

    it("should work correctly for number filters", () => {
        expect(highlightFilter("3210123", 0)).toBe('321<span class="highlight">0</span>123');
    });

    it("should work correctly for number text", () => {
        expect(highlightFilter(3210123, "0")).toBe('321<span class="highlight">0</span>123');
    });

    it("should highlight nothing if empty filter string passed", () => {
        expect(highlightFilter(test_phrase, "")).toEqual(test_phrase);
    });

    it("should highlight more that one element", () => {
        expect(highlightFilter(test_phrase, "gh")).toBe(
            'Prefix Hi<span class="highlight">gh</span>li<span class="highlight">gh</span>t Suffix'
        );
    });

    it("highlights each matching search terms", () => {
        expect(highlightFilter(test_phrase, "suffix highlight")).toBe(
            'Prefix <span class="highlight">Highlight</span> <span class="highlight">Suffix</span>'
        );
    });

    it("should escape regexp search terms", () => {
        expect(highlightFilter("Prefix (Highlight) Suffix", "(Highlight)")).toBe(
            'Prefix <span class="highlight">(Highlight)</span> Suffix'
        );
    });
});
