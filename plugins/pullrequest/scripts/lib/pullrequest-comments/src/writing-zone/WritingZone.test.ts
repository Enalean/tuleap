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

import { describe, it, expect, beforeEach, vi } from "vitest";
import type { SpyInstance } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import type { ElementContainingAWritingZone } from "../types";
import type { ControlWritingZone } from "./WritingZoneController";
import { WritingZoneController } from "./WritingZoneController";
import { getWritingZoneElement, isWritingZoneElement } from "./WritingZone";
import type { InternalWritingZone } from "./WritingZone";

type ElementNeedingAWritingZone = ElementContainingAWritingZone<{
    _some_attribute: never;
}>;

describe("WritingZone", () => {
    let doc: Document,
        focus_writing_zone_when_connected: boolean,
        focusWritingZone: SpyInstance,
        blurWritingZone: SpyInstance,
        resetWritingZone: SpyInstance,
        onTextareaInput: SpyInstance,
        writing_zone_controller: ControlWritingZone;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        focus_writing_zone_when_connected = true;
    });

    const getBuiltWritingZoneElement = (): HTMLElement & InternalWritingZone => {
        const shouldFocusWritingZoneOnceRendered = (): boolean => focus_writing_zone_when_connected;
        writing_zone_controller = WritingZoneController({
            focus_writing_zone_when_connected: shouldFocusWritingZoneOnceRendered(),
        });

        focusWritingZone = vi.spyOn(writing_zone_controller, "focusWritingZone");
        blurWritingZone = vi.spyOn(writing_zone_controller, "blurWritingZone");
        resetWritingZone = vi.spyOn(writing_zone_controller, "resetWritingZone");
        onTextareaInput = vi.spyOn(writing_zone_controller, "onTextareaInput");

        const component_needing_a_writing_zone: ElementNeedingAWritingZone = {
            controller: {
                handleWritingZoneContentChange: vi.fn(),
                shouldFocusWritingZoneOnceRendered,
            },
            writing_zone_controller,
        };

        const writing_zone = getWritingZoneElement(component_needing_a_writing_zone);
        if (!isWritingZoneElement(writing_zone)) {
            throw new Error("Expected a WritingZone element.");
        }

        return writing_zone;
    };

    describe("getWritingZoneElement()", () => {
        it(`Given a component needing to display a WritingZone
            Then it should create a WritingZone element
            Assign it the component's writing_zone_controller
            Make the component's own controller react on writing zone inputs
            And finally return the WritingZone element`, () => {
            const shouldFocusWritingZoneOnceRendered = (): boolean => true;
            const component_needing_a_writing_zone: ElementNeedingAWritingZone = {
                controller: {
                    handleWritingZoneContentChange: vi.fn(),
                    shouldFocusWritingZoneOnceRendered,
                },
                writing_zone_controller: WritingZoneController({
                    focus_writing_zone_when_connected: shouldFocusWritingZoneOnceRendered(),
                }),
            };

            const writing_zone = getWritingZoneElement(component_needing_a_writing_zone);
            if (!isWritingZoneElement(writing_zone)) {
                throw new Error("Expected a WritingZone element.");
            }

            expect(writing_zone.controller).toBe(
                component_needing_a_writing_zone.writing_zone_controller
            );

            const writing_zone_content = "Please rebase!";
            writing_zone.dispatchEvent(
                new CustomEvent("writing-zone-input", {
                    detail: {
                        content: writing_zone_content,
                    },
                })
            );

            expect(
                component_needing_a_writing_zone.controller.handleWritingZoneContentChange
            ).toHaveBeenCalledOnce();
            expect(
                component_needing_a_writing_zone.controller.handleWritingZoneContentChange
            ).toHaveBeenCalledWith(component_needing_a_writing_zone, writing_zone_content);
        });
    });

    describe("Connect/Disconnect", () => {
        it("When focus_writing_zone_when_connected is true, and the WritingZone is added the DOM tree, then it should be focused", async () => {
            focus_writing_zone_when_connected = true;

            const writing_zone = getBuiltWritingZoneElement();

            expect(focusWritingZone).not.toHaveBeenCalled();

            await doc.body.append(writing_zone);

            expect(focusWritingZone).toHaveBeenCalled();
        });

        it("When focus_writing_zone_when_connected is false, and the WritingZone is added the DOM tree, then it should not be focused", async () => {
            focus_writing_zone_when_connected = false;

            const writing_zone = getBuiltWritingZoneElement();

            await doc.body.append(writing_zone);

            expect(focusWritingZone).not.toHaveBeenCalledOnce();
        });

        it("When the WritingZone is added the DOM tree, then its textarea should be filled with its current value", async () => {
            const writing_zone = getBuiltWritingZoneElement();
            const content = "This is a description comment.";

            writing_zone_controller.setWritingZoneContent(writing_zone, content);

            await doc.body.append(writing_zone);

            expect(writing_zone.textarea.value).toBe(content);
        });

        it("When the WritingZone element is removed from the DOM tree, then it should reset the textarea", async () => {
            const writing_zone = getBuiltWritingZoneElement();

            await doc.body.append(writing_zone);
            await doc.body.removeChild(writing_zone);

            expect(resetWritingZone).toHaveBeenCalledOnce();
        });
    });

    describe("The <textarea/> element", () => {
        it("When some content is typed into the textarea, then the onTextAreaChange callback should be triggered", async () => {
            const writing_zone = getBuiltWritingZoneElement();

            await doc.body.append(writing_zone);

            const textarea = selectOrThrow(
                writing_zone,
                "[data-test=writing-zone-textarea]",
                HTMLTextAreaElement
            );

            textarea.value = "This is a description comment for bug #123";
            textarea.dispatchEvent(new Event("input"));

            expect(onTextareaInput).toHaveBeenCalledOnce();
        });

        it("When the textarea gets or looses the focus, then the onFocusChange callback should be triggered", async () => {
            focus_writing_zone_when_connected = false;

            const writing_zone = getBuiltWritingZoneElement();

            await doc.body.append(writing_zone);

            const textarea = selectOrThrow(
                writing_zone,
                "[data-test=writing-zone-textarea]",
                HTMLTextAreaElement
            );

            textarea.dispatchEvent(new Event("focus"));
            expect(focusWritingZone).toHaveBeenCalledOnce();

            textarea.dispatchEvent(new Event("blur"));
            expect(blurWritingZone).toHaveBeenCalledOnce();
        });
    });
});
