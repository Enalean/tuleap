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

import type { HostElement, TextAndFormat, TextAndFormatOptions } from "./text-and-format";
import { getTextAndFormatTemplate, interpretCommonMark, isDisabled } from "./text-and-format";
import { setCatalog } from "../../gettext-catalog";
import { FormattedTextController } from "../../domain/common/FormattedTextController";
import { DispatchEventsStub } from "../../../tests/stubs/DispatchEventsStub";
import { TEXT_FORMAT_TEXT } from "@tuleap/plugin-tracker-constants";
import type { InterpretCommonMark } from "../../domain/common/InterpretCommonMark";
import { InterpretCommonMarkStub } from "../../../tests/stubs/InterpretCommonMarkStub";
import { Fault } from "@tuleap/fault";

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

const HTML_STRING = `<h1>Oh no! Anyway...</h1>`;
const COMMONMARK_STRING = "# Oh no! Anyway...";

describe(`TextAndFormat`, () => {
    let target: ShadowRoot, interpreter: InterpretCommonMark, doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        target = doc.createElement("div") as unknown as ShadowRoot;
        setCatalog({ getString: (msgid) => msgid });

        interpreter = InterpretCommonMarkStub.withHTML(HTML_STRING);
    });

    function getHost(data?: Partial<TextAndFormat>): HostElement {
        const element = doc.createElement("span");
        const host = {
            ...data,
            interpreted_commonmark: "",
            controller: FormattedTextController(
                DispatchEventsStub.buildNoOp(),
                interpreter,
                TEXT_FORMAT_TEXT
            ),
        } as HostElement;
        return Object.assign(element, host);
    }

    describe(`interpretCommonMark()`, () => {
        const interpret = (host: HostElement): Promise<void> =>
            interpretCommonMark(host, COMMONMARK_STRING);

        it(`when in preview mode, it switches to edit mode`, async () => {
            const host = getHost({ is_in_preview_mode: true });
            await interpret(host);
            expect(host.is_in_preview_mode).toBe(false);
        });

        it(`when in edit mode, it switches to preview mode
            and sets the interpreted CommonMark on the host`, async () => {
            const host = getHost({ is_in_preview_mode: false });
            const promise = interpret(host);
            expect(host.is_preview_loading).toBe(true);

            await promise;
            expect(host.has_error).toBe(false);
            expect(host.error_message).toBe("");
            expect(host.is_preview_loading).toBe(false);
            expect(host.is_in_preview_mode).toBe(true);
            expect(host.interpreted_commonmark).toBe(HTML_STRING);
        });

        it(`sets the error message when CommonMark cannot be interpreted`, async () => {
            const error_message = "Invalid CommonMark";
            interpreter = InterpretCommonMarkStub.withFault(Fault.fromMessage(error_message));
            const host = getHost({ is_in_preview_mode: false });
            await interpret(host);

            expect(host.has_error).toBe(true);
            expect(host.error_message).toBe(error_message);
            expect(host.is_preview_loading).toBe(false);
            expect(host.is_in_preview_mode).toBe(true);
            expect(host.interpreted_commonmark).toBe("");
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
