/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import type { Project, Task } from "../../../type";
import HeaderLink from "./HeaderLink.vue";
import { DASHBOARD_ID } from "../../../injection-symbols";

describe("HeaderLink", () => {
    it("should display the project label and icon if needed", async () => {
        const wrapper = shallowMount(HeaderLink, {
            props: {
                task: {
                    xref: "art #123",
                    title: "Tomate salade oignon",
                    project: { label: "ACME Corp", icon: "🐺" } as Project,
                } as Task,
                should_display_project: false,
            },
            global: {
                provide: {
                    [DASHBOARD_ID.valueOf()]: 22,
                },
            },
        });

        expect(wrapper.find("[data-test=project-icon-and-label]").exists()).toBe(false);
        await wrapper.setProps({ should_display_project: true });
        expect(wrapper.find("[data-test=project-icon-and-label]").exists()).toBe(true);
    });
});
