/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import { isWildCardAndNotQuestionMark } from "./handle-wildcards";
import type { Shortcut } from "../type";

describe("isWildCardAndNotQuestionMark()", () => {
    const question_mark_keyboard_event: KeyboardEvent = new KeyboardEvent("question_mark", {
        key: "?",
    });
    const not_question_mark_keyboard_event: KeyboardEvent = new KeyboardEvent("not_question_mark");

    const wildcard_shortcut = {
        keyboard_inputs: "*",
        displayed_inputs: "?",
    } as Shortcut;

    const not_wildcard_shortcut = {
        keyboard_inputs: "a",
        displayed_inputs: "?",
    } as Shortcut;

    it(`returns true if required keyboard input is wildcard * key but pressed key is not ?`, () => {
        const is_wildcard_and_not_question_mark = isWildCardAndNotQuestionMark(
            wildcard_shortcut,
            not_question_mark_keyboard_event,
        );
        expect(is_wildcard_and_not_question_mark).toBe(true);
    });

    it(`returns false if required keyboard input is not wildcard * key`, () => {
        const is_wildcard_and_not_question_mark = isWildCardAndNotQuestionMark(
            not_wildcard_shortcut,
            not_question_mark_keyboard_event,
        );
        expect(is_wildcard_and_not_question_mark).toBe(false);
    });

    it(`returns false if pressed key is ?`, () => {
        const is_wildcard_and_not_question_mark = isWildCardAndNotQuestionMark(
            wildcard_shortcut,
            question_mark_keyboard_event,
        );
        expect(is_wildcard_and_not_question_mark).toBe(false);
    });
});
