/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { describe, expect, it, vi } from "vitest";
import type { EditorState } from "prosemirror-state";
import { convertUrlToLinkAfterASpace } from "./convert-url-to-link-after-a-space";
import * as getWordOrUrlJustBeforeCursorModule from "../../helpers/get-word-or-url-just-before-cursor";
import * as getLinkUrlFromTextModule from "./get-link-url-from-text";
import { custom_schema } from "../../custom_schema";

function getState(add_mark_mock: ReturnType<typeof vi.fn>): EditorState {
    return {
        schema: custom_schema,
        selection: {
            $from: {
                start: vi.fn().mockReturnValue(0),
                pos: 0,
            },
        },
        tr: {
            addMark: add_mark_mock,
        },
    } as unknown as EditorState;
}

describe("convertUrlToLinkAfterASpace", () => {
    describe("When the user input is a space", () => {
        describe("When the previous text is an url", () => {
            it("should create a link and insert space", () => {
                vi.spyOn(
                    getWordOrUrlJustBeforeCursorModule,
                    "getWordOrUrlJustBeforeCursor",
                ).mockReturnValue("https://an-url.com");
                vi.spyOn(getLinkUrlFromTextModule, "getLinkUrlFromText").mockReturnValue(
                    "https://an-url.com",
                );

                const add_mark_mock = vi.fn();
                const state = getState(add_mark_mock);

                const dispatch_mock = vi.fn();
                const false_if_the_input_has_been_inserted = convertUrlToLinkAfterASpace(
                    state,
                    dispatch_mock,
                    0,
                    " ",
                );

                expect(dispatch_mock).toHaveBeenCalledOnce();
                expect(add_mark_mock).toHaveBeenCalledOnce();

                // verify if user input has been inserted === return false for handleTextInput prose-mirror function
                expect(false_if_the_input_has_been_inserted).toBe(false);
            });
        });
        describe("When the previous text is not an url", () => {
            it("should insert space and not create a link", () => {
                vi.spyOn(
                    getWordOrUrlJustBeforeCursorModule,
                    "getWordOrUrlJustBeforeCursor",
                ).mockReturnValue("http");
                vi.spyOn(getLinkUrlFromTextModule, "getLinkUrlFromText").mockReturnValue(undefined);

                const add_mark_mock = vi.fn();
                const state = getState(add_mark_mock);

                const dispatch_mock = vi.fn();
                const false_if_the_input_has_been_inserted = convertUrlToLinkAfterASpace(
                    state,
                    dispatch_mock,
                    0,
                    "a",
                );

                expect(dispatch_mock).not.toHaveBeenCalledOnce();
                expect(add_mark_mock).not.toHaveBeenCalledOnce();
                expect(false_if_the_input_has_been_inserted).toBe(false);
            });
        });
    });

    describe("when the user input is not a space", () => {
        it("should insert the input", () => {
            const add_mark_mock = vi.fn();
            const state = getState(add_mark_mock);

            const dispatch_mock = vi.fn();

            const false_if_the_input_has_been_inserted = convertUrlToLinkAfterASpace(
                state,
                dispatch_mock,
                0,
                "a",
            );

            expect(dispatch_mock).not.toHaveBeenCalledOnce();
            expect(add_mark_mock).not.toHaveBeenCalledOnce();
            expect(false_if_the_input_has_been_inserted).toBe(false);
        });
    });
});
