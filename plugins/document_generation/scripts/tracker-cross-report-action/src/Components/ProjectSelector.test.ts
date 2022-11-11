/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
import { flushPromises, shallowMount } from "@vue/test-utils";
import ProjectSelector from "./ProjectSelector.vue";
import { getGlobalTestOptions } from "./global-options-for-test";
import * as rest_querier from "../rest-querier";
import type { ProjectResponse } from "../rest-querier";

const projects: ProjectResponse[] = [
    {
        id: 102,
        label: "Project A",
        icon: "",
    },
    {
        id: 105,
        label: "Project B",
        icon: "",
    },
];

describe("ProjectSelector", () => {
    it("displays possible projects", async () => {
        vi.spyOn(rest_querier, "getProjects").mockResolvedValue(projects);

        const wrapper = shallowMount(ProjectSelector, {
            global: getGlobalTestOptions(),
            props: {
                project_id: null,
            },
        });

        await flushPromises();

        const selector = wrapper.get("select");

        expect(selector.findAll("option")).toHaveLength(2);
    });

    it("returns selected project", async () => {
        vi.spyOn(rest_querier, "getProjects").mockResolvedValue(projects);

        const wrapper = shallowMount(ProjectSelector, {
            global: getGlobalTestOptions(),
            props: {
                project_id: null,
            },
        });

        await flushPromises();

        const selector = wrapper.get("select");

        await selector.setValue(102);

        const emitted_input = wrapper.emitted("update:project_id");
        expect(emitted_input).toBeDefined();
        if (emitted_input === undefined) {
            throw new Error("Expected an update event to be emitted");
        }
        expect(emitted_input[0]).toStrictEqual([102]);
    });
});
