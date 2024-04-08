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
 */

import { describe, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import * as rest from "./helpers/rest-querier";
import { okAsync } from "neverthrow";
import { createGettext } from "vue3-gettext";
import type { ProjectReference } from "@tuleap/core-rest-api-types";
import App from "./App.vue";

vi.mock("./rest-querier");

describe("App", () => {
    it("should display the project label", async () => {
        vi.spyOn(rest, "getProject").mockReturnValue(
            okAsync({
                id: 123,
                label: "Acme Project",
                icon: "",
                uri: "",
            } as ProjectReference),
        );

        const wrapper = shallowMount(App, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            propsData: {
                project_id: 123,
            },
        });

        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=title]").text()).toBe(
            "Artifacts as Documents for Acme Project",
        );
    });
});
