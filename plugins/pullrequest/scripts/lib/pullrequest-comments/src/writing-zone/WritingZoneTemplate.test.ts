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
import { getWritingZoneTemplate } from "./WritingZoneTemplate";
import { GettextProviderStub } from "../../tests/stubs/GettextProviderStub";
import type { HostElement } from "./WritingZone";
import { WritingZonePresenter } from "./WritingZonePresenter";
import type { ControlWritingZone } from "./WritingZoneController";
import { WritingZoneController } from "./WritingZoneController";

describe("WritingZoneTemplate", () => {
    let controller: ControlWritingZone;

    beforeEach(() => {
        controller = WritingZoneController({
            focus_writing_zone_when_connected: false,
        });
    });

    const renderWritingZone = (host: HostElement): ShadowRoot => {
        const doc = document.implementation.createHTMLDocument();
        const target = doc.createElement("div") as unknown as ShadowRoot;

        const render = getWritingZoneTemplate(host, GettextProviderStub);

        render(host, target);

        return target;
    };

    it("When the writing zone is focused, then the [Writing] tab should be active", () => {
        const writing_zone = renderWritingZone({
            controller,
            presenter: WritingZonePresenter.buildFocused(WritingZonePresenter.buildInitial()),
        } as HostElement);

        expect(
            selectOrThrow(writing_zone, "[data-test=writing-tab]").classList.contains(
                "tlp-tab-active"
            )
        ).toBe(true);
    });

    it("should display tabs", () => {
        const writing_zone = renderWritingZone({
            controller,
            presenter: WritingZonePresenter.buildInitial(),
        } as HostElement);

        const writing_tab = selectOrThrow(writing_zone, "[data-test=writing-tab]");

        expect(writing_tab).toBeDefined();
    });

    it("When some content is typed into the textarea, then the onTextAreaChange callback should be triggered", () => {
        const onTextareaInput = vi.spyOn(controller, "onTextareaInput");
        const writing_zone = renderWritingZone({
            controller,
            presenter: WritingZonePresenter.buildWithContent(
                WritingZonePresenter.buildInitial(),
                "This is a description comment"
            ),
        } as HostElement);

        const textarea = selectOrThrow(
            writing_zone,
            "[data-test=writing-zone-textarea]",
            HTMLTextAreaElement
        );

        textarea.value = "This is a description comment for bug #123";
        textarea.dispatchEvent(new Event("input"));

        expect(onTextareaInput).toHaveBeenCalledOnce();
    });

    it("When the textarea gets or looses the focus, then the onFocusChange callback should be triggered", () => {
        const focusTextArea = vi.spyOn(controller, "focusTextArea");
        const blurTextArea = vi.spyOn(controller, "blurTextArea");
        const writing_zone = renderWritingZone({
            controller,
            presenter: WritingZonePresenter.buildBlurred(WritingZonePresenter.buildInitial()),
        } as HostElement);

        const textarea = selectOrThrow(
            writing_zone,
            "[data-test=writing-zone-textarea]",
            HTMLTextAreaElement
        );

        textarea.dispatchEvent(new Event("focus"));
        expect(focusTextArea).toHaveBeenCalledOnce();

        textarea.dispatchEvent(new Event("blur"));
        expect(blurTextArea).toHaveBeenCalledOnce();
    });
});
