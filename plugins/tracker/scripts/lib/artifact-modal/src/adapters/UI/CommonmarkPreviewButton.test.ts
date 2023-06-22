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
 *
 */

import { buttonLabel, CommonmarkPreviewButton, iconClasses } from "./CommonmarkPreviewButton";
import { setCatalog } from "../../gettext-catalog";

type HostElement = CommonmarkPreviewButton & HTMLElement;

describe("CommonmarkPreviewButton", () => {
    beforeEach(() => {
        setCatalog({
            getString: (msgid) => msgid,
        });
    });

    it.each([
        ["Edit", true, "fa-pencil-alt"],
        ["Preview", false, "fa-eye"],
    ])(
        `displays the '%s' button if the preview mode is %s`,
        (expected_button_label, is_in_preview_mode, expected_class) => {
            const host = {
                isInPreviewMode: is_in_preview_mode,
                isPreviewLoading: false,
            } as CommonmarkPreviewButton;
            const icon_classes = iconClasses(host);

            expect(icon_classes).toContain(expected_class);
            expect(icon_classes).not.toContain("fa-circle-notch");
            expect(icon_classes).not.toContain("fa-spin");
            expect(buttonLabel(host)).toEqual(expected_button_label);
        }
    );

    it("displays the spinner when the preview is loading", () => {
        const host = {
            isInPreviewMode: false,
            isPreviewLoading: true,
        } as CommonmarkPreviewButton;
        const icon_classes = iconClasses(host);

        expect(icon_classes).toContain("fa-circle-notch");
        expect(icon_classes).toContain("fa-spin");
    });

    describe(`render()`, () => {
        let target: ShadowRoot;
        beforeEach(() => {
            const doc = document.implementation.createHTMLDocument();
            target = doc.createElement("div") as unknown as ShadowRoot;
        });

        it(`when I click on the button, it dispatches a commonmark-preview-event`, () => {
            const dispatchEvent = jest.fn();
            const host = {
                isInPreviewMode: false,
                isPreviewLoading: false,
                buttonLabel: "Preview",
                iconClasses: [],
                dispatchEvent,
            } as unknown as HostElement;
            const update = CommonmarkPreviewButton.content(host);
            update(host, target);

            const button = getButton(target);
            button.dispatchEvent(new MouseEvent("click"));
            const event = dispatchEvent.mock.calls[0][0];
            expect(event.type).toBe("commonmark-preview-event");
        });

        it(`while preview is loading, the button is disabled`, () => {
            const host = {
                isInPreviewMode: false,
                isPreviewLoading: true,
                buttonLabel: "Preview",
                iconClasses: [],
            } as unknown as HostElement;
            const update = CommonmarkPreviewButton.content(host);
            update(host, target);

            const button = getButton(target);
            expect(button.disabled).toBe(true);
        });
    });
});

function getButton(target: ShadowRoot): HTMLButtonElement {
    const button = target.querySelector("[data-test=button-commonmark-preview]");
    if (!(button instanceof HTMLButtonElement)) {
        throw new Error("Expected to find the button in the template");
    }
    return button;
}
