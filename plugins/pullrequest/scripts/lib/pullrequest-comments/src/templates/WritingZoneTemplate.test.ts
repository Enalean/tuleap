/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, beforeEach, it, expect, vi } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import type {
    onWritingZoneContentChangeCallbackType,
    onWritingZoneStateChangeCallbackType,
    WritingZoneState,
} from "./WritingZoneTemplate";
import { getWritingZoneTemplate } from "./WritingZoneTemplate";
import { GettextProviderStub } from "../../tests/stubs/GettextProviderStub";
import { FocusTextareaStub } from "../../tests/stubs/FocusTextareaStub";
import type { FocusTextArea } from "../helpers/textarea-focus-helper";

describe("WritingZoneTemplate", () => {
    let onTextAreaChange: onWritingZoneContentChangeCallbackType,
        onFocusChange: onWritingZoneStateChangeCallbackType,
        focus_helper: FocusTextArea;

    beforeEach(() => {
        onTextAreaChange = vi.fn();
        onFocusChange = vi.fn();
        focus_helper = FocusTextareaStub();
    });

    const renderWritingZone = (state: WritingZoneState): ShadowRoot => {
        const doc = document.implementation.createHTMLDocument();
        const host_element = doc.createElement("div");
        const target = doc.createElement("div") as unknown as ShadowRoot;

        const render = getWritingZoneTemplate(
            state,
            focus_helper,
            onTextAreaChange,
            onFocusChange,
            GettextProviderStub
        );

        render(host_element, target);

        return target;
    };

    it("When the writing zone is focused, then the [Writing] tab should be active", () => {
        const writing_zone = renderWritingZone({
            initial_content: "",
            is_focused: true,
        });

        expect(
            selectOrThrow(writing_zone, "[data-test=writing-tab]").classList.contains(
                "tlp-tab-active"
            )
        ).toBe(true);
    });

    it("When the writing tab is clicked, then the textarea should be focused", () => {
        const writing_zone = renderWritingZone({
            initial_content: "",
            is_focused: false,
        });

        selectOrThrow(writing_zone, "[data-test=writing-tab]").click();

        expect(focus_helper.focusTextArea).toHaveBeenCalledOnce();
    });

    it("Should put the initial content into the textarea when some is provided", () => {
        const writing_zone = renderWritingZone({
            initial_content: "This is a description comment",
            is_focused: false,
        });

        expect(
            selectOrThrow(writing_zone, "[data-test=writing-zone-textarea]").textContent?.trim()
        ).toBe("This is a description comment");
    });

    it("When some content is typed into the textarea, then the onTextAreaChange callback should be triggered", () => {
        const writing_zone = renderWritingZone({
            initial_content: "This is a description comment",
            is_focused: true,
        });

        const textarea = selectOrThrow(
            writing_zone,
            "[data-test=writing-zone-textarea]",
            HTMLTextAreaElement
        );
        const new_content = "This is a description comment for bug #123";

        textarea.value = new_content;
        textarea.dispatchEvent(new Event("input"));

        expect(onTextAreaChange).toHaveBeenCalledWith(new_content);
    });

    it("When the textarea gets or looses the focus, then the onFocusChange callback should be triggered", () => {
        const writing_zone = renderWritingZone({
            initial_content: "This is a description comment",
            is_focused: false,
        });

        const textarea = selectOrThrow(
            writing_zone,
            "[data-test=writing-zone-textarea]",
            HTMLTextAreaElement
        );

        textarea.dispatchEvent(new Event("focus"));
        expect(onFocusChange).toHaveBeenNthCalledWith(1, true);

        textarea.dispatchEvent(new Event("blur"));
        expect(onFocusChange).toHaveBeenNthCalledWith(2, false);
    });
});
