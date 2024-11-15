/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { InputRule } from "prosemirror-inputrules";
import type { MarkType } from "prosemirror-model";
import type { Transaction } from "prosemirror-state";

type TextPositions = {
    start: number;
    end: number;
};

export const getTextPositions = (match: RegExpMatchArray, start: number): TextPositions => {
    const full_match = match[0];
    const extracted_text = match[1];

    const text_start_position = start + full_match.indexOf(extracted_text);
    const text_end_position = text_start_position + extracted_text.length;

    return {
        start: text_start_position,
        end: text_end_position,
    };
};

export const removeMarkdownCharacters = (
    tr: Transaction,
    start: number,
    end: number,
    text_start_position: number,
    text_end_position: number,
): void => {
    if (text_end_position < end) {
        tr.delete(text_end_position, end);
    }
    if (text_start_position > start) {
        tr.delete(start, text_start_position);
    }
};

export const applyMark = (
    tr: Transaction,
    mark_type: MarkType,
    mark_start_position: number,
    mark_end_position: number,
): void => {
    tr.addMark(mark_start_position, mark_end_position, mark_type.create({}));
    tr.removeStoredMark(mark_type);
};

export function markInputRule(regexp: RegExp, markType: MarkType): InputRule {
    return new InputRule(regexp, (state, match, start, end) => {
        const tr = state.tr;

        const text_positions = getTextPositions(match, start);

        removeMarkdownCharacters(tr, start, end, text_positions.start, text_positions.end);

        const extracted_text = match[1];
        const end_of_mark = start + extracted_text.length;

        applyMark(tr, markType, start, end_of_mark);

        return tr;
    });
}
