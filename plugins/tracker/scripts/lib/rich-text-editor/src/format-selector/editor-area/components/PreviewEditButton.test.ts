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
import { render } from "lit-html";

describe(`PreviewEditButton`, () => {
    let mount_point: HTMLDivElement, gettext_provider: GettextProvider;
    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        mount_point = doc.createElement("div");
        gettext_provider = {
            gettext: (msgid): string => msgid,
        };
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
        (is_in_edit_mode: boolean, button_name: string, button_icon: string) => {
            const presenter = {
                is_in_edit_mode,
                onClickCallback: jest.fn(),
            };
            const template = createPreviewEditButton(presenter, gettext_provider);
            render(template, mount_point);

            const button = getButton();
            expect(button.outerHTML).toMatchSnapshot();

            const icon = button.querySelector("[data-test=button-icon]");
            if (!(icon instanceof HTMLElement)) {
                throw new Error("Could not get the button icon");
            }
            expect(icon.classList.contains(button_icon)).toBe(true);

            button.click();
            expect(presenter.onClickCallback).toHaveBeenCalled();
        }
    );
});
