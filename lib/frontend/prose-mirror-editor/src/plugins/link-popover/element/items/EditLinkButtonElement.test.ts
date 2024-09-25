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

import { describe, it, expect, vi } from "vitest";
import type { HostElement } from "./EditLinkButtonElement";
import { renderEditLinkButton } from "./EditLinkButtonElement";
import { createLocalDocument } from "../../../../helpers";

describe("EditLinkButtonElement", () => {
    it("When clicked, Then it should dispatch a toggle-edition-mode event", () => {
        const doc = createLocalDocument();
        const host = Object.assign(doc.createElement("span"), {
            button_title: "Click me",
        } as HostElement);
        const target = doc.createElement("div") as unknown as ShadowRoot;
        const dispatchEvent = vi.spyOn(host, "dispatchEvent");

        renderEditLinkButton(host)(host, target);

        const button = target.querySelector<HTMLButtonElement>("[data-test=edit-link-button]");
        if (!button) {
            throw new Error("Expected a button");
        }

        expect(button.title).toBe(host.button_title);
        button.click();

        const event = dispatchEvent.mock.calls[0][0];
        expect(event.type).toBe("toggle-edition-mode");
    });
});
