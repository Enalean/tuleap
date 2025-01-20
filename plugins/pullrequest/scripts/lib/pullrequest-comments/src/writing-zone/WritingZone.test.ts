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

import type { MockInstance } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import type { ControlWritingZone } from "./WritingZoneController";
import { WritingZoneController } from "./WritingZoneController";
import type { WritingZone } from "./WritingZone";
import { getWritingZoneElement } from "./WritingZone";
import "@tuleap/commonmark-popover/commonmark-popover-stub";

vi.mock("@tuleap/mention", () => ({
    initMentions(): void {
        // Mock @tuleap/mention because it needs jquery in tests
    },
}));
vi.useFakeTimers();

const project_id = 105;

describe("WritingZone", () => {
    let doc: Document,
        focus_writing_zone_when_connected: boolean,
        focusWritingZone: MockInstance,
        blurWritingZone: MockInstance,
        resetWritingZone: MockInstance,
        onTextareaInput: MockInstance,
        writing_zone_controller: ControlWritingZone;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        focus_writing_zone_when_connected = true;

        vi.useFakeTimers();
    });

    const getBuiltWritingZoneElement = (): HTMLElement & WritingZone => {
        const shouldFocusWritingZoneOnceRendered = (): boolean => focus_writing_zone_when_connected;
        writing_zone_controller = WritingZoneController({
            document: doc,
            focus_writing_zone_when_connected: shouldFocusWritingZoneOnceRendered(),
            project_id,
        });

        focusWritingZone = vi.spyOn(writing_zone_controller, "focusWritingZone");
        blurWritingZone = vi.spyOn(writing_zone_controller, "blurWritingZone");
        resetWritingZone = vi.spyOn(writing_zone_controller, "resetWritingZone");
        onTextareaInput = vi.spyOn(writing_zone_controller, "onTextareaInput");

        const element = getWritingZoneElement();
        element.controller = writing_zone_controller;
        return element;
    };

    describe("Connect/Disconnect", () => {
        it("When focus_writing_zone_when_connected is true, and the WritingZone is added the DOM tree, then it should be focused", async () => {
            focus_writing_zone_when_connected = true;

            const writing_zone = getBuiltWritingZoneElement();

            expect(focusWritingZone).not.toHaveBeenCalled();

            doc.body.append(writing_zone);
            await vi.runOnlyPendingTimersAsync();

            expect(focusWritingZone).toHaveBeenCalled();
        });

        it("When focus_writing_zone_when_connected is false, and the WritingZone is added the DOM tree, then it should not be focused", async () => {
            focus_writing_zone_when_connected = false;

            const writing_zone = getBuiltWritingZoneElement();

            doc.body.append(writing_zone);
            await vi.runOnlyPendingTimersAsync();

            expect(focusWritingZone).not.toHaveBeenCalledOnce();
        });

        it("When the WritingZone is added the DOM tree, then its textarea should be filled with its current value", async () => {
            const writing_zone = getBuiltWritingZoneElement();
            const content = "This is a description comment.";

            writing_zone.comment_content = content;
            doc.body.append(writing_zone);
            await vi.runOnlyPendingTimersAsync();

            expect(writing_zone.textarea.value).toBe(content);
        });

        it("When the WritingZone is added to the DOM tree, then it should add event listener", async () => {
            const writing_zone = getBuiltWritingZoneElement();
            const addEventListener = vi.spyOn(writing_zone, "addEventListener");

            doc.body.append(writing_zone);
            await vi.runOnlyPendingTimersAsync();

            expect(addEventListener).toHaveBeenCalledTimes(2);
        });

        it(`When the WritingZone element is removed from the DOM tree
            Then it should reset the textarea
            And remove the event listeners`, async () => {
            const writing_zone = getBuiltWritingZoneElement();
            const removeEventListener = vi.spyOn(writing_zone, "removeEventListener");

            doc.body.append(writing_zone);
            await vi.runOnlyPendingTimersAsync();
            doc.body.removeChild(writing_zone);
            await vi.runOnlyPendingTimersAsync();

            expect(resetWritingZone).toHaveBeenCalledOnce();
            expect(removeEventListener).toHaveBeenCalledTimes(2);
        });
    });

    describe("The <textarea/> element", () => {
        it("When some content is typed into the textarea, then the onTextAreaChange callback should be triggered", async () => {
            const writing_zone = getBuiltWritingZoneElement();

            doc.body.append(writing_zone);
            await vi.runOnlyPendingTimersAsync();

            const textarea = selectOrThrow(
                writing_zone,
                "[data-test=writing-zone-textarea]",
                HTMLTextAreaElement,
            );

            textarea.value = "This is a description comment for bug #123";
            textarea.dispatchEvent(new Event("input"));

            expect(onTextareaInput).toHaveBeenCalledOnce();
        });
    });

    describe("Focus management", () => {
        it("Should focus the WritingZone when it catches the focusin event", async () => {
            const writing_zone = getBuiltWritingZoneElement();

            doc.body.append(writing_zone);
            await vi.runOnlyPendingTimersAsync();

            writing_zone.dispatchEvent(new Event("focusin"));

            expect(focusWritingZone).toHaveBeenCalledTimes(2);
        });

        it("Should blur the WritingZone when it catches the focusout event", async () => {
            const writing_zone = getBuiltWritingZoneElement();

            doc.body.append(writing_zone);
            await vi.runOnlyPendingTimersAsync();

            writing_zone.dispatchEvent(new Event("focusout"));

            expect(blurWritingZone).toHaveBeenCalledOnce();
        });
    });
});
