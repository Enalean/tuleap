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
import type { HostElement } from "./WritingZone";
import { WritingZoneController } from "./WritingZoneController";
import { WritingZonePresenter } from "./WritingZonePresenter";

describe("WritingZoneController", () => {
    let doc: Document, textarea: HTMLTextAreaElement;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        textarea = doc.createElement("textarea");
    });

    it("initWritingZone() should assign the WritingZone a default presenter", () => {
        const host = {
            presenter: undefined,
        } as unknown as HostElement;

        WritingZoneController({ focus_writing_zone_when_connected: false }).initWritingZone(host);

        expect(host.presenter).toStrictEqual({
            initial_content: "",
            is_focused: false,
            is_in_writing_mode: true,
        });
    });

    it(`onTextAreaInput() should dispatch a "writing-zone-input" containing the WritingZone's <textarea/> content`, () => {
        const host = Object.assign(doc.createElement("div"), {
            textarea,
        } as HostElement);

        textarea.value = "Please rebase!";

        const dispatchEvent = vi.spyOn(host, "dispatchEvent");

        WritingZoneController({ focus_writing_zone_when_connected: false }).onTextareaInput(host);

        expect(dispatchEvent).toHaveBeenCalledOnce();

        const event = dispatchEvent.mock.calls[0][0] as CustomEvent;

        expect(event.type).toBe("writing-zone-input");
        expect(event.detail).toStrictEqual({ content: textarea.value });
    });

    it("focusTextArea() should focus the <textarea/>, put the component in focused state and add the active class on its parent element", () => {
        const parent_element = doc.createElement("div");
        const host = {
            textarea,
            presenter: WritingZonePresenter.buildBlurred(WritingZonePresenter.buildInitial()),
            parentElement: parent_element,
        } as unknown as HostElement;

        textarea.value = "Please rebase!";

        const focus = vi.spyOn(textarea, "focus");
        const setSelectionRange = vi.spyOn(textarea, "setSelectionRange");

        WritingZoneController({ focus_writing_zone_when_connected: false }).focusTextArea(host);

        expect(focus).toHaveBeenCalledOnce();
        expect(setSelectionRange).toHaveBeenCalledOnce();
        expect(setSelectionRange).toHaveBeenCalledWith(
            textarea.value.length,
            textarea.value.length
        );

        expect(host.presenter.is_focused).toBe(true);
        expect(Array.from(parent_element.classList)).toContain(
            "pull-request-comment-with-writing-zone-active"
        );
    });

    it("blurTextArea() should blur the <textarea/>, remove the component focused state and remove the active class on its parent element", () => {
        const parent_element = doc.createElement("div");
        const host = {
            textarea,
            presenter: WritingZonePresenter.buildFocused(WritingZonePresenter.buildInitial()),
            parentElement: parent_element,
        } as unknown as HostElement;

        const blur = vi.spyOn(textarea, "blur");

        WritingZoneController({ focus_writing_zone_when_connected: false }).blurTextArea(host);

        expect(blur).toHaveBeenCalledOnce();
        expect(host.presenter.is_focused).toBe(false);
        expect(Array.from(parent_element.classList)).not.toContain(
            "pull-request-comment-with-writing-zone-active"
        );
    });

    it("switchToWritingMode() should focus the <textarea/> set the presenter to writing_mode", () => {
        const host = {
            textarea,
            presenter: {
                initial_content: "",
                is_focused: false,
                is_in_writing_mode: false,
            },
        } as HostElement;

        const focus = vi.spyOn(textarea, "focus");

        WritingZoneController({ focus_writing_zone_when_connected: false }).switchToWritingMode(
            host
        );

        expect(focus).toHaveBeenCalledOnce();
        expect(host.presenter.is_focused).toBe(true);
        expect(host.presenter.is_in_writing_mode).toBe(true);
    });

    it("resetTextArea() should empty + blur the <textarea/> and", () => {
        const parent_element = doc.createElement("div");
        const host = {
            textarea,
            presenter: WritingZonePresenter.buildFocused(WritingZonePresenter.buildInitial()),
            parentElement: parent_element,
        } as unknown as HostElement;

        textarea.value = "Please rebase!";

        const blur = vi.spyOn(textarea, "blur");

        WritingZoneController({ focus_writing_zone_when_connected: false }).resetTextArea(host);

        expect(textarea.value).toBe("");
        expect(blur).toHaveBeenCalledOnce();
        expect(host.presenter.is_focused).toBe(false);
        expect(Array.from(parent_element.classList)).not.toContain(
            "pull-request-comment-with-writing-zone-active"
        );
    });

    it.each([
        [false, false],
        [true, true],
    ])(
        'When the config attribute "should_focus_when_writing_zone_once_rendered" is %s, then shouldFocusWritingZoneOnceRendered() should return %s',
        (should_focus_when_writing_zone_once_rendered, expected) => {
            expect(
                WritingZoneController({
                    focus_writing_zone_when_connected: should_focus_when_writing_zone_once_rendered,
                }).shouldFocusWritingZoneWhenConnected()
            ).toBe(expected);
        }
    );

    it("setWritingZoneContent() should set the WritingZone initial_content", () => {
        const host = {
            presenter: WritingZonePresenter.buildInitial(),
        } as unknown as HostElement;
        const new_content = "This is new content";

        WritingZoneController({
            focus_writing_zone_when_connected: true,
        }).setWritingZoneContent(host, new_content);

        expect(host.presenter.initial_content).toBe(new_content);
    });
});
