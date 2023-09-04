/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { setCatalog } from "../../gettext-catalog";
import { FormatSelector, isSyntaxHelperDisabled } from "./FormatSelector";

type HostElement = FormatSelector & HTMLElement;

function getHost(props = {}): HostElement {
    return { ...props } as unknown as HostElement;
}

describe(`FormatSelector`, () => {
    let doc: Document, target: ShadowRoot;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        target = doc.createElement("div") as unknown as ShadowRoot;
        setCatalog({ getString: (msgid) => msgid });
    });

    describe(`disabled`, () => {
        it.each([
            ["the field is disabled", true, false, false],
            ["the user is in preview mode", true, false, false],
            ["preview is loading", false, false, true],
        ])(
            "will disable the format selectbox when %s",
            (result_condition, disabled, isInPreviewMode, isPreviewLoading) => {
                const host = getHost({ disabled, isInPreviewMode, isPreviewLoading });
                const update = FormatSelector.content(host);
                update(host, target);

                const format_selectbox = target.querySelector("[data-test=format]");
                expect(format_selectbox?.hasAttribute("disabled")).toBe(true);
            },
        );
        it(`enables the button if the field is not disabled, if the user is not in preview mode
            and if the CommonMark interpretation is not loading`, () => {
            const host = getHost({
                disabled: false,
                isInPreviewMode: false,
                isPreviewLoading: false,
            });
            const update = FormatSelector.content(host);
            update(host, target);

            const format_selectbox = target.querySelector("[data-test=format]");
            expect(format_selectbox?.hasAttribute("disabled")).toBe(false);
        });
    });
    describe("commonmark syntax helper button and preview button display", () => {
        it.each([["html"], ["text"]])(
            `does not displays the CommonMark related buttons if the chosen format is %s`,
            (format) => {
                const host = getHost({ value: format });
                const update = FormatSelector.content(host);
                update(host, target);

                expect(target.querySelector("[data-test=preview-button]")).toBeNull();
                expect(target.querySelector("[data-test=syntax-button]")).toBeNull();
            },
        );
        it(`displays the CommonMark related buttons if the chosen format is 'Markdown'`, () => {
            const host = getHost({ value: "commonmark" });
            const update = FormatSelector.content(host);
            update(host, target);

            expect(target.querySelector("[data-test=preview-button]")).not.toBeNull();
            expect(target.querySelector("[data-test=syntax-button]")).not.toBeNull();
        });
    });
    describe("disabling of the CommonMark syntax helper button", () => {
        it.each([
            [true, false],
            [false, true],
        ])("disables the syntax helper button", (isInPreviewMode, isPreviewLoading) => {
            setCatalog({ getString: () => "" });
            const host = getHost({ isInPreviewMode, isPreviewLoading });

            expect(isSyntaxHelperDisabled(host)).toBe(true);
        });
        it(`enables the syntax helper button if the preview is not loading
            and if the user is in edit mode`, () => {
            const host = getHost({ isInPreviewMode: false, isPreviewLoading: false });

            expect(isSyntaxHelperDisabled(host)).toBe(false);
        });
    });
});
