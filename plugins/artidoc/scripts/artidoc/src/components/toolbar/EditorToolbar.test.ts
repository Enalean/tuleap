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
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import { TOOLBAR_BUS } from "@/toolbar-bus-injection-key";
import EditorToolbar from "./EditorToolbar.vue";
import type { ToolbarBus } from "@tuleap/prose-mirror-editor";
import { buildToolbarBus } from "@tuleap/prose-mirror-editor";

describe("EditorToolbar", () => {
    it("should trigger bold when bold is clicked", () => {
        const mock_activate_bold = vi.fn().mockReturnValue({});
        const mock_bold = vi.fn().mockReturnValue({});
        const set_view = vi.fn().mockReturnValue({
            activateBold: mock_activate_bold,
        });
        const bus: ToolbarBus = {
            bold: mock_bold,
            setView: set_view,
        } as unknown as ToolbarBus;
        const wrapper = shallowMount(EditorToolbar, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [TOOLBAR_BUS.valueOf()]: bus,
                },
            },
        });

        const bold_icon = wrapper.find("[data-test=icon-bold]");
        bold_icon.trigger("click");
        expect(mock_bold).toHaveBeenCalledOnce();
    });

    it("should have activated class when text is bold", async () => {
        const bus: ToolbarBus = buildToolbarBus();
        const wrapper = shallowMount(EditorToolbar, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [TOOLBAR_BUS.valueOf()]: bus,
                },
            },
        });

        await bus.view?.activateBold(true);
        const bold_icon = wrapper.find("[data-test=icon-bold]");
        expect(bold_icon.classes()).toContain("activated");
    });
});
