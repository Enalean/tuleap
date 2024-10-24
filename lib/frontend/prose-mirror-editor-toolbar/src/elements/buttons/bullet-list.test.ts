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

import { describe, beforeEach, expect, it, vi } from "vitest";
import { buildToolbarBus } from "@tuleap/prose-mirror-editor";
import type { ToolbarBus } from "@tuleap/prose-mirror-editor";
import { createLocalDocument, gettext_provider } from "../../helpers/helper-for-test";
import type { HostElement } from "./bullet-list";
import { connect, renderBulletListItem } from "./bullet-list";

describe("BulletElement", () => {
    let target: ShadowRoot, toolbar_bus: ToolbarBus;

    beforeEach(() => {
        const doc = createLocalDocument();

        target = doc.createElement("div") as unknown as ShadowRoot;
        toolbar_bus = buildToolbarBus();
    });

    it("When the component is connected, then it should set its part of the toolbar view so it will be able to update itself when the view changes.", () => {
        const host = {
            is_activated: false,
            is_disabled: false,
            toolbar_bus,
        } as HostElement;

        connect(host);

        const list_state = {
            is_activated: true,
            is_disabled: false,
        };
        toolbar_bus.view.activateBulletList(list_state);
        expect(host.is_activated).toBe(list_state.is_activated);
        expect(host.is_disabled).toBe(list_state.is_disabled);
    });

    it("When the button is clicked, Then it should call toolbar_bus bulletList() method", () => {
        const applyBulletList = vi.spyOn(toolbar_bus, "bulletList");
        const host = { toolbar_bus } as HostElement;

        renderBulletListItem(host, gettext_provider)(host, target);

        const button = target.querySelector<HTMLButtonElement>("[data-test=button-bullet]");
        if (!button) {
            throw new Error("Expected a button");
        }

        button.click();

        expect(applyBulletList).toHaveBeenCalledOnce();
    });

    it.each([
        [false, true, "it should NOT have the button-active class"],
        [true, false, "it should have the button-active class"],
    ])(
        "When is_activated is %s and is_disabled is %s, then %s",
        (is_activated: boolean, is_disabled: boolean) => {
            const host = {
                is_activated,
                is_disabled,
                toolbar_bus,
            } as HostElement;

            renderBulletListItem(host, gettext_provider)(host, target);

            const button = target.querySelector<HTMLButtonElement>("[data-test=button-bullet]");
            if (!button) {
                throw new Error("Expected a button");
            }

            expect(button.classList.contains("button-active")).toBe(is_activated);
        },
    );
});
