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

import escapeStringRegexp from "escape-string-regexp";
import { HighlightedText } from "./HighlightedText";

const space_regexp = new RegExp(" ", "g");

export interface ClassifierType {
    classify(content: string): ReadonlyArray<HighlightedText>;
}

export const Classifier = (search: string): ClassifierType => {
    let regexp = new RegExp("");
    if (search !== "") {
        const regexp_escaped_search = escapeStringRegexp(search);
        const or_search = regexp_escaped_search.replace(space_regexp, "|");

        regexp = new RegExp("(" + or_search + ")", "gi");
    }

    return {
        classify(content: string): ReadonlyArray<HighlightedText> {
            if (search === "") {
                return [HighlightedText.background(content)];
            }
            return content
                .split(regexp)
                .filter((part) => part !== "")
                .map((part) =>
                    regexp.test(part)
                        ? HighlightedText.highlight(part)
                        : HighlightedText.background(part)
                );
        },
    };
};
