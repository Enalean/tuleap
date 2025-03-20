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
import { buildToolbarBus } from "@tuleap/prose-mirror-editor";
import type { Popover } from "@tuleap/tlp-popovers";
import { connect } from "./link";
import type { HostElement } from "./link";

describe("link", () => {
    describe("connect", () => {
        it("When the component is connected, then it should set its part of the toolbar view so it will be able to update itself when the view changes", () => {
            const toolbar_bus = buildToolbarBus();
            const host = { toolbar_bus } as unknown as HostElement;

            connect(host);

            const link_state = {
                is_activated: true,
                is_disabled: false,
                link_href: "https://example.com",
                link_title: "See example",
            };

            toolbar_bus.view.activateLink(link_state);

            expect(host.is_activated).toBe(link_state.is_activated);
            expect(host.is_disabled).toBe(link_state.is_disabled);
            expect(host.link_href).toBe(link_state.link_href);
            expect(host.link_title).toBe(link_state.link_title);
        });

        it("when the toolbar view wants to open the link menu, then it should open it", () => {
            const toolbar_bus = buildToolbarBus();
            const host = {
                toolbar_bus,
                popover_instance: { show: vi.fn() } as unknown as Popover,
            } as unknown as HostElement;

            connect(host);
            toolbar_bus.view.toggleToolbarMenu("link");

            expect(host.popover_instance.show).toHaveBeenCalledOnce();
        });

        it("when the toolbar view wants to open another menu, then it should do nothing", () => {
            const toolbar_bus = buildToolbarBus();
            const host = {
                toolbar_bus,
                popover_instance: { show: vi.fn() } as unknown as Popover,
            } as unknown as HostElement;

            connect(host);
            toolbar_bus.view.toggleToolbarMenu("starters");

            expect(host.popover_instance.show).not.toHaveBeenCalled();
        });
    });
});
