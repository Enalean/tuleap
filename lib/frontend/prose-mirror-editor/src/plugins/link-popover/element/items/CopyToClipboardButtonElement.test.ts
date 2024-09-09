/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import { createLocalDocument, gettext_provider } from "../../../../helpers/helper-for-test";
import type {
    HostElement,
    InternalCopyToClipboardButtonElement,
} from "./CopyToClipboardButtonElement";
import { renderCopyToClipboardItem } from "./CopyToClipboardButtonElement";

describe("CopyToClipboardButtonElement", () => {
    let doc: Document, target: ShadowRoot;

    beforeEach(() => {
        doc = createLocalDocument();
        target = doc.createElement("div") as unknown as ShadowRoot;
    });

    it.each([
        [false, "fa-regular", "fa-copy"],
        [true, "fa-solid", "fa-check"],
    ])(
        "When has_been_copied_to_clipboard is %s, then the icon should have the classes %s %s",
        (has_been_copied_to_clipboard, icon_style, icon_name) => {
            const host = Object.assign(doc.createElement("div"), {
                has_been_copied_to_clipboard,
            } as InternalCopyToClipboardButtonElement) as HostElement;

            const render = renderCopyToClipboardItem(host, gettext_provider);
            render(host, target);

            const icon_element = target.querySelector("[data-test=copy-to-clipboard-icon]");
            if (!icon_element) {
                throw new Error("Unable to find the icon element :(");
            }

            expect(icon_element.classList.contains(icon_style)).toBe(true);
            expect(icon_element.classList.contains(icon_name)).toBe(true);
        },
    );
});
