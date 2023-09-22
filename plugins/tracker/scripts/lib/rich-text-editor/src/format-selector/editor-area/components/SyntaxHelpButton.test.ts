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

import { createSyntaxHelpButton } from "./SyntaxHelpButton";
import type { GettextProvider } from "@tuleap/gettext";
import { render } from "lit-html";
import { stripLitExpressionComments } from "../../../test-helper";
import { initGettextSync } from "@tuleap/gettext";

describe(`SyntaxHelpButton`, () => {
    let mount_point: HTMLDivElement, gettext_provider: GettextProvider;
    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        mount_point = doc.createElement("div");
        gettext_provider = initGettextSync("rich-text-editor", {}, "en_US");
    });

    it(`will create a custom element with the button and the popover content`, () => {
        const template = createSyntaxHelpButton({ is_disabled: false }, gettext_provider);
        render(template, mount_point);
        expect(stripLitExpressionComments(mount_point.innerHTML)).toMatchSnapshot();
    });

    it(`when is_selected is true, it will disable the button`, () => {
        const template = createSyntaxHelpButton({ is_disabled: true }, gettext_provider);
        render(template, mount_point);

        const help_button = mount_point.querySelector("[data-test=help-button]");
        if (!(help_button instanceof HTMLButtonElement)) {
            throw new Error("Could not find the help button");
        }
        expect(help_button.disabled).toBe(true);
    });
});
