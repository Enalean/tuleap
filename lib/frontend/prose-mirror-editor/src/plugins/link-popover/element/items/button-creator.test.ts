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
import { createButton } from "./button-creator";
import { createLocalDocument, gettext_provider } from "../../../../helpers/helper-for-test";
import { isOpenLinkButtonElement } from "./OpenLinkButtonElement";
import { isCopyToClipboardElement } from "./CopyToClipboardButtonElement";

describe("button-creator", () => {
    let doc: Document;

    beforeEach(() => {
        doc = createLocalDocument();
    });

    it("Given a OpenLinkButton, then it should return a OpenLinkButtonElement", () => {
        const button = createButton(doc, gettext_provider, {
            type: "open-link",
            sanitized_link_href: "https://example.com",
        });

        if (!isOpenLinkButtonElement(button)) {
            throw new Error("Expected an OpenLinkButtonElement");
        }

        expect(button.sanitized_link_href).toBe("https://example.com");
        expect(button.gettext_provider).toBe(gettext_provider);
    });

    it("Given a CopyToClipboardButton, then it should return a CopyToClipboardButtonElement", () => {
        const button = createButton(doc, gettext_provider, {
            type: "copy-to-clipboard",
            value_to_copy: "art #123",
            value_copied_title: "Value copied!",
            copy_value_title: "Copy value",
        });

        if (!isCopyToClipboardElement(button)) {
            throw new Error("Expected an CopyToClipboardButtonElement");
        }

        expect(button.value_to_copy).toBe("art #123");
        expect(button.value_copied_title).toBe("Value copied!");
        expect(button.copy_value_title).toBe("Copy value");
    });
});
