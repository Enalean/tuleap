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

import { createPreviewEditButton } from "./PreviewEditButton";
import type { GettextProvider } from "@tuleap/gettext";
import { render } from "lit/html.js";
import { stripLitExpressionComments } from "../../../test-helper";
import { initGettextSync } from "@tuleap/gettext";

const emptyFunction = (): void => {
    //Do nothing
};
const identity = <T>(param: T): T => param;

describe(`PreviewEditButton`, () => {
    let mount_point: HTMLDivElement, gettext_provider: GettextProvider;
    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        mount_point = doc.createElement("div");
        gettext_provider = initGettextSync("rich-text-editor", {}, "en_US");
    });

    function getButton(): HTMLButtonElement {
        const button = mount_point.firstElementChild;
        if (!(button instanceof HTMLButtonElement)) {
            throw new Error("Could not find the button");
        }
        return button;
    }

    it.each([
        [true, "Preview", "fa-eye"],
        [false, "Edit", "fa-pencil-alt"],
    ])(
        `when is_in_edit_mode is %s, it will create a %s button`,
        async (is_in_edit_mode: boolean, button_name: string, button_icon: string) => {
            const presenter = {
                is_in_edit_mode,
                promise_of_preview: Promise.resolve(),
                onClickCallback: jest.fn(),
            };
            const template = createPreviewEditButton(presenter, gettext_provider);
            render(template, mount_point);
            await presenter.promise_of_preview;
            // I don't really understand why, but I have to await twice
            await presenter.promise_of_preview;

            const button = getButton();
            expect(stripLitExpressionComments(button.outerHTML)).toMatchSnapshot();

            const icon = button.querySelector("[data-test=button-icon]");
            if (!(icon instanceof HTMLElement)) {
                throw new Error("Could not get the button icon");
            }
            expect(icon.classList.contains(button_icon)).toBe(true);

            button.click();
            expect(presenter.onClickCallback).toHaveBeenCalled();
        },
    );

    it(`if the promise fails, it will re-enable the button to allow people to retry`, () => {
        const presenter = {
            is_in_edit_mode: true,
            promise_of_preview: Promise.reject("Network error"),
            onClickCallback: emptyFunction,
        };
        const template = createPreviewEditButton(presenter, gettext_provider);
        render(template, mount_point);
        return presenter.promise_of_preview.catch(identity).then(() => {
            const button = getButton();
            expect(stripLitExpressionComments(button.outerHTML)).toMatchInlineSnapshot(`
                <button type="button" class="btn btn-small rte-button">
                  <i aria-hidden="true" class="fas fa-fw fa-eye"></i>
                  Preview
                </button>
            `);
        });
    });

    it(`while promise_of_preview is unsettled, it will create a disabled loading button`, () => {
        const presenter = {
            is_in_edit_mode: true,
            promise_of_preview: Promise.resolve(),
            onClickCallback: emptyFunction,
        };
        const template = createPreviewEditButton(presenter, gettext_provider);
        render(template, mount_point);
        const button = getButton();
        expect(stripLitExpressionComments(button.outerHTML)).toMatchInlineSnapshot(`
            <button type="button" class="btn btn-small rte-button" disabled="">
              <i class="fas fa-fw fa-spin fa-circle-notch" aria-hidden="true"></i>
              Preview
            </button>
        `);
    });
});
