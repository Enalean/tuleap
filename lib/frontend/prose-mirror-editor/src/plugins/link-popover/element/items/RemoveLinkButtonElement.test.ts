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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { createLocalDocument, gettext_provider } from "../../../../helpers";
import type { HostElement, InternalRemoveLnkButton } from "./RemoveLinkButtonElement";
import { renderRemoveLinkButton } from "./RemoveLinkButtonElement";

describe("RemoveLinkButtonElement", () => {
    let doc: Document, target: ShadowRoot;

    beforeEach(() => {
        doc = createLocalDocument();
        target = doc.createElement("div") as unknown as ShadowRoot;
    });

    it("should contain a button to remove the link from the editor", () => {
        const host = Object.assign(doc.createElement("div"), {
            remove_link_callback: vi.fn(),
            gettext_provider,
        } as InternalRemoveLnkButton) as HostElement;

        const render = renderRemoveLinkButton(host, gettext_provider);
        render(host, target);

        const remove_link_button = target.querySelector<HTMLButtonElement>(
            "[data-test=remove-link-button]",
        );
        if (!remove_link_button) {
            throw new Error("Expected to find a remove link button");
        }

        remove_link_button.click();

        expect(host.remove_link_callback).toHaveBeenCalledOnce();
    });
});
