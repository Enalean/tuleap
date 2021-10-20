/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import * as tuleap_api from "../api/tuleap-api";
import type { HostElement, TextAndFormat, TextAndFormatOptions } from "./text-and-format";
import { getTextAndFormatTemplate, interpretCommonMark, isDisabled } from "./text-and-format";
import { setCatalog } from "../gettext-catalog";

function getHost(data?: Partial<TextAndFormat>): HostElement {
    return {
        ...data,
        interpreted_commonmark: "",
        dispatchEvent: jest.fn(),
    } as unknown as HostElement;
}

const noop = (): void => {
    //Do nothing
};

const getOptions = (data?: Partial<TextAndFormatOptions>): TextAndFormatOptions => ({
    identifier: "unique-id",
    rows: 3,
    onContentChange: noop,
    onFormatChange: noop,
    ...data,
});

describe(`TextAndFormat`, () => {
    let target: ShadowRoot;
    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        target = doc.createElement("div") as unknown as ShadowRoot;
        setCatalog({ getString: (msgid) => msgid });
    });

    describe(`interpretCommonMark()`, () => {
        it(`when in preview mode, it switches to edit mode`, async () => {
            const post = jest.spyOn(tuleap_api, "postInterpretCommonMark");
            const content = "# Oh no! Anyway...";

            await interpretCommonMark(getHost({ is_in_preview_mode: true }), content);

            expect(post).not.toHaveBeenCalled();
        });

        it(`when in edit mode, it switches to preview mode
            and sets the interpreted CommonMark on the host`, async () => {
            const post = jest
                .spyOn(tuleap_api, "postInterpretCommonMark")
                .mockResolvedValue("<p>HTML</p>");
            const content = "# Markdown title";

            const host = getHost({ is_in_preview_mode: false });
            const promise = interpretCommonMark(host, content);
            expect(host.is_preview_loading).toBe(true);
            expect(post).toHaveBeenCalled();

            await promise;
            expect(host.has_error).toBe(false);
            expect(host.error_message).toBe("");
            expect(host.is_preview_loading).toBe(false);
            expect(host.is_in_preview_mode).toBe(true);
            expect(host.interpreted_commonmark).toBe("<p>HTML</p>");
        });

        it(`sets the error message when CommonMark cannot be interpreted`, async () => {
            const error = new Error("Failed to interpret the CommonMark");
            jest.spyOn(tuleap_api, "postInterpretCommonMark").mockRejectedValue(error);
            const content = "# Oh no! Anyway...";

            const host = getHost({ is_in_preview_mode: false });
            await interpretCommonMark(host, content);

            expect(host.has_error).toBe(true);
            expect(host.error_message).toBe(error.message);
            expect(host.is_preview_loading).toBe(false);
            expect(host.is_in_preview_mode).toBe(true);
            expect(host.interpreted_commonmark).toBe("");
        });
    });

    describe(`onUploadImage`, () => {
        let host: HostElement;
        beforeEach(() => {
            host = getHost();
            const update = getTextAndFormatTemplate(host, getOptions());
            update(host, target);
        });

        it(`when the RichTextEditor emits an "upload-image" event, it will reemit it`, () => {
            const detail = { image: { id: 9 } };
            const dispatch = jest.spyOn(host, "dispatchEvent");
            getSelector("[data-test=text-editor]").dispatchEvent(
                new CustomEvent("upload-image", { detail })
            );

            const reemitted_event = dispatch.mock.calls[0][0];
            if (!(reemitted_event instanceof CustomEvent)) {
                throw new Error("Expected a CustomEvent");
            }
            expect(reemitted_event.type).toBe("upload-image");
            expect(reemitted_event.detail).toBe(detail);
        });
    });

    describe("Template", () => {
        it(`assigns the same identifier on Format Selector and Rich Text Editor
            so that the lib can bind them together`, () => {
            const identifier = "psycheometry";
            const host = getHost();
            const update = getTextAndFormatTemplate(host, getOptions({ identifier }));
            update(host, target);

            const format_selector_element = getSelector("[data-test=format-selector]");
            const text_editor_element = getSelector("[data-test=text-editor]");
            expect(format_selector_element.getAttribute("identifier")).toBe(identifier);
            expect(text_editor_element.getAttribute("identifier")).toBe(identifier);
        });

        it("shows the Rich Text Editor if there is no error and if the user is in edit mode", () => {
            const host = getHost({ has_error: false, is_in_preview_mode: false });
            const update = getTextAndFormatTemplate(host, getOptions());
            update(host, target);

            expect(
                getSelector("[data-test=text-editor]").classList.contains(
                    "tuleap-artifact-modal-hidden"
                )
            ).toBe(false);
            expect(target.querySelector("[data-test=text-field-commonmark-preview]")).toBeNull();
            expect(target.querySelector("[data-test=text-field-error]")).toBeNull();
        });

        it("shows the CommonMark preview if there is no error and if the user is in preview mode", () => {
            const host = getHost({ has_error: false, is_in_preview_mode: true });
            const update = getTextAndFormatTemplate(host, getOptions());
            update(host, target);

            expect(
                getSelector("[data-test=text-editor]").classList.contains(
                    "tuleap-artifact-modal-hidden"
                )
            ).toBe(true);
            expect(
                target.querySelector("[data-test=text-field-commonmark-preview]")
            ).not.toBeNull();
            expect(target.querySelector("[data-test=text-field-error]")).toBeNull();
        });

        it("shows the error message if there was a problem during the CommonMark interpretation", () => {
            const host = getHost({
                has_error: true,
                error_message: "Interpretation failed !!!!!!!!",
                is_in_preview_mode: false,
            });
            const update = getTextAndFormatTemplate(host, getOptions());
            update(host, target);

            expect(
                getSelector("[data-test=text-editor]").classList.contains(
                    "tuleap-artifact-modal-hidden"
                )
            ).toBe(true);
            expect(target.querySelector("[data-test=text-field-commonmark-preview]")).toBeNull();
            expect(getSelector("[data-test=text-field-error]").textContent).toContain(
                "Interpretation failed !!!!!!!!"
            );
        });
    });

    describe("disabling the text field", () => {
        it.each([
            ["field is disabled", true, false],
            ["preview is loading", false, true],
        ])(
            `disables the text area if the %s`,
            (disabled_entity, is_field_disabled, is_preview_loading) => {
                const host = getHost({ disabled: is_field_disabled, is_preview_loading });
                expect(isDisabled(host)).toBe(true);
            }
        );

        it(`enables the text field when the field is not disabled and when it is not loading`, () => {
            const host = getHost({ disabled: false, is_preview_loading: false });
            expect(isDisabled(host)).toBe(false);
        });
    });

    function getSelector(selector: string): HTMLElement {
        const selected = target.querySelector(selector);
        if (!(selected instanceof HTMLElement)) {
            throw new Error("Could not select element");
        }
        return selected;
    }
});
