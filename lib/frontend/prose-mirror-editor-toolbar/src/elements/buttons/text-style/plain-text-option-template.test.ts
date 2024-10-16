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
import { buildToolbarBus } from "@tuleap/prose-mirror-editor";
import { createLocalDocument } from "../../../helpers/helper-for-test";
import { renderPlainTextOption } from "./plain-text-option-template";
import type { HostElement } from "./text-style";

describe("plain-text-option-template", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        const doc = createLocalDocument();

        target = doc.createElement("div") as unknown as ShadowRoot;
    });

    const getPlainTextOption = (host: HostElement): HTMLOptionElement => {
        renderPlainTextOption(host)(host, target);

        const option = target.querySelector("option");
        if (!option) {
            throw new Error("Expected an option");
        }
        return option;
    };

    it.each([
        [false, "should not be selected nor disabled"],
        [true, "should be selected and disabled"],
    ])("When host.is_plain_text_activated === %s then the option %s", (is_plain_text_activated) => {
        const host = { is_plain_text_activated } as HostElement;
        const option = getPlainTextOption(host);

        expect(option.disabled).toBe(is_plain_text_activated);
        expect(option.selected).toBe(is_plain_text_activated);
    });

    it("When the option is clicked, then it should call toolbar_bus.plainText()", () => {
        const toolbar_bus = buildToolbarBus();
        const host = { is_plain_text_activated: false, toolbar_bus } as HostElement;
        const applyPlainText = vi.spyOn(toolbar_bus, "plainText");

        getPlainTextOption(host).click();

        expect(applyPlainText).toHaveBeenCalledOnce();
    });
});
