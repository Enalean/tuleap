/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import type { BoldElement } from "./bold";
import { renderBoldItem } from "./bold";
import type { ToolbarBus } from "@tuleap/prose-mirror-editor";
import { createLocalDocument } from "./helpers/helper-for-test";

describe("BoldElement", () => {
    it("When clicked, Then it should call toolbar_bus bold method", () => {
        const doc = createLocalDocument();
        const mock_bold = vi.fn();
        const toolbar_bus = {
            bold: mock_bold,
        } as unknown as ToolbarBus;
        const host = Object.assign(doc.createElement("span"), {
            is_activated: false,
            toolbar_bus,
        } as BoldElement);
        const target = doc.createElement("div") as unknown as ShadowRoot;

        renderBoldItem(host)(host, target);

        const button = target.querySelector<HTMLButtonElement>("[data-test=button-bold]");
        if (!button) {
            throw new Error("Expected a button");
        }

        button.click();

        expect(mock_bold).toHaveBeenCalledOnce();
    });

    it("Should have the activate class", () => {
        const doc = createLocalDocument();
        const mock_bold = vi.fn();
        const toolbar_bus = {
            bold: mock_bold,
        } as unknown as ToolbarBus;
        const host = Object.assign(doc.createElement("span"), {
            is_activated: true,
            toolbar_bus,
        } as BoldElement);
        const target = doc.createElement("div") as unknown as ShadowRoot;

        renderBoldItem(host)(host, target);

        const button = target.querySelector<HTMLButtonElement>("[data-test=button-bold]");
        if (!button) {
            throw new Error("Expected a button");
        }

        expect(button.classList.contains("prose-mirror-button-activated")).toBe(true);
    });
});
