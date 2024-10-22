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
import type { ToolbarBus } from "@tuleap/prose-mirror-editor";
import { buildToolbarBus } from "@tuleap/prose-mirror-editor";
import { createLocalDocument, gettext_provider } from "../../helpers/helper-for-test";
import type { HostElement } from "./unlink";
import { connect, renderUnlinkElement } from "./unlink";

describe("unlink", () => {
    let target: ShadowRoot, toolbar_bus: ToolbarBus;

    beforeEach(() => {
        const doc = createLocalDocument();

        target = doc.createElement("div") as unknown as ShadowRoot;
        toolbar_bus = buildToolbarBus();
    });

    it("When the component is connected, then it should set its part of the toolbar view so it will be able to update itself when the view changes.", () => {
        const host = {
            is_activated: false,
            toolbar_bus,
        } as HostElement;

        connect(host);

        toolbar_bus.view.activateUnlink(true);
        expect(host.is_activated).toBe(true);

        toolbar_bus.view.activateUnlink(false);
        expect(host.is_activated).toBe(false);
    });

    it("When the button is clicked, Then it should call toolbar_bus unlink() method", () => {
        const applyUnlink = vi.spyOn(toolbar_bus, "unlink");
        const host = { toolbar_bus, is_activated: true } as HostElement;

        renderUnlinkElement(host, gettext_provider)(host, target);

        const button = target.querySelector<HTMLButtonElement>("[data-test=button-unlink]");
        if (!button) {
            throw new Error("Expected a button");
        }

        button.click();

        expect(applyUnlink).toHaveBeenCalledOnce();
    });

    it.each([
        [false, true, "it should NOT have the button-active class, and should be disabled"],
        [true, false, "it should have the button-active class, and should NOT be disabled"],
    ])(
        "When is_activated is %s and is_disabled is %s, then %s",
        (is_activated: boolean, is_disabled: boolean) => {
            const host = {
                is_activated,
                is_disabled,
                toolbar_bus,
            } as HostElement;

            renderUnlinkElement(host, gettext_provider)(host, target);

            const button = target.querySelector<HTMLButtonElement>("[data-test=button-unlink]");
            if (!button) {
                throw new Error("Expected a button");
            }

            expect(button.classList.contains("button-active")).toBe(is_activated);
            expect(button.hasAttribute("disabled")).toBe(!is_activated);
        },
    );
});
