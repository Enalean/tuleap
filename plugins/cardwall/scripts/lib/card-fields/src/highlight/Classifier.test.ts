/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { Classifier } from "./Classifier";
import { HighlightedText } from "./HighlightedText";

const countBackgrounds = (array_of_texts: ReadonlyArray<HighlightedText>): number =>
    array_of_texts.reduce(
        (accumulator, text) => (HighlightedText.isHighlight(text) ? accumulator : accumulator + 1),
        0,
    );

describe(`Classifier`, () => {
    it(`given an empty search string, it will always return an array
        with a single Background text containing the whole content`, () => {
        const classifier = Classifier("");
        const content = "resell";
        const result = classifier.classify(content);

        expect(result).toHaveLength(1);
        expect(HighlightedText.isHighlight(result[0])).toBe(false);
        expect(result[0].content).toBe(content);

        const other_content = "feetless";
        const second_result = classifier.classify(other_content);

        expect(second_result).toHaveLength(1);
        expect(HighlightedText.isHighlight(second_result[0])).toBe(false);
        expect(second_result[0].content).toBe(other_content);
    });

    describe(`given a search string of only one word`, () => {
        const SEARCH = "sear";
        const runClassify = (content: string): ReadonlyArray<HighlightedText> => {
            const classifier = Classifier(SEARCH);
            return classifier.classify(content);
        };

        it(`will split the content and return an array of Background text
            with one Highlight matching the search string`, () => {
            const result = runClassify("researcher");

            expect(result).toHaveLength(3);
            const [start, highlight, end] = result;
            expect(HighlightedText.isHighlight(start)).toBe(false);
            expect(start.content).toBe("re");

            expect(HighlightedText.isHighlight(highlight)).toBe(true);
            expect(highlight.content).toBe(SEARCH);

            expect(HighlightedText.isHighlight(end)).toBe(false);
            expect(end.content).toBe("cher");
        });

        it(`when the search string does not match anything in the content,
            it will return an array with a single Background containing the whole content`, () => {
            const content = "fliting perpetualness";
            const result = runClassify(content);

            expect(result).toHaveLength(1);
            expect(HighlightedText.isHighlight(result[0])).toBe(false);
            expect(result[0].content).toBe(content);
        });

        it.each([
            ["sear", 0],
            ["search", 1],
            ["unsear", 1],
        ])(
            `will not return Backgrounds with empty strings`,
            (content: string, expected_number_of_backgrounds: number) => {
                const result = runClassify(content);
                expect(countBackgrounds(result)).toBe(expected_number_of_backgrounds);
            },
        );

        it(`will ignore case when matching`, () => {
            const result = runClassify("SEAR");

            expect(result).toHaveLength(1);
            expect(HighlightedText.isHighlight(result[0])).toBe(true);
            expect(result[0].content).toBe("SEAR");
        });

        it(`will highlight all matching fragments of content`, () => {
            const result = runClassify("Searching the seared wall with a searchlight");

            expect(result).toHaveLength(6);
            expect(countBackgrounds(result)).toBe(3); // 6 - 3 = 3 highlights
        });

        it(`will escape special RegExp characters in search string`, () => {
            const classifier = Classifier("(term)");
            const result = classifier.classify("search (term)");

            expect(result).toHaveLength(2);
            expect(HighlightedText.isHighlight(result[1])).toBe(true);
            expect(result[1].content).toBe("(term)");
        });
    });

    it(`given a search string of multiple words separated by spaces,
        it will return highlights for each matching search term`, () => {
        const classifier = Classifier("first second third");

        const result = classifier.classify("third second first");
        expect(result).toHaveLength(5);

        const first_highlight = result[0];
        expect(HighlightedText.isHighlight(first_highlight)).toBe(true);
        expect(first_highlight.content).toBe("third");

        const second_highlight = result[2];
        expect(HighlightedText.isHighlight(second_highlight)).toBe(true);
        expect(second_highlight.content).toBe("second");

        const third_highlight = result[4];
        expect(HighlightedText.isHighlight(third_highlight)).toBe(true);
        expect(third_highlight.content).toBe("first");
    });

    it(`given a search string ending with a space it should not highlight everything`, () => {
        const classifier = Classifier("search ");

        const result = classifier.classify("researcher");

        expect(result).toHaveLength(3);
        const [start, highlight, end] = result;
        expect(HighlightedText.isHighlight(start)).toBe(false);
        expect(start.content).toBe("re");

        expect(HighlightedText.isHighlight(highlight)).toBe(true);
        expect(highlight.content).toBe("search");

        expect(HighlightedText.isHighlight(end)).toBe(false);
        expect(end.content).toBe("er");
    });
});
