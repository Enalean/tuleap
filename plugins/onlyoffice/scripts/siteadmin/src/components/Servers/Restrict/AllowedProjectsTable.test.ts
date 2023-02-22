/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

vi.mock("@tuleap/autocomplete-for-select2", () => {
    return {
        autocomplete_projects_for_select2(): void {
            //do nothing
        },
    };
});

import type { Config, Server } from "../../../type";
import { shallowMount } from "@vue/test-utils";
import AllowedProjectsTable from "./AllowedProjectsTable.vue";
import { createGettext } from "vue3-gettext";
import { CONFIG } from "../../../injection-keys";

describe("AllowedProjectsTable", () => {
    it("should display existing projects", () => {
        const server: Server = {
            id: 1,
            server_url: "https://example.com",
            is_project_restricted: true,
            project_restrictions: [
                {
                    id: 101,
                    label: "Project A",
                    url: "/projects/project-a",
                },
                {
                    id: 103,
                    label: "Le projet C",
                    url: "/projects/project-c",
                },
            ],
        } as unknown as Server;

        const wrapper = shallowMount(AllowedProjectsTable, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [CONFIG as symbol]: {
                        servers: [server],
                    } as unknown as Config,
                },
            },
            props: {
                server,
                submit: vi.fn(),
            },
        });

        expect(wrapper.text()).toContain("Project A");
        expect(wrapper.text()).toContain("Le projet C");
    });

    it("should display existing projects - filtered ones", async () => {
        const server: Server = {
            id: 1,
            server_url: "https://example.com",
            is_project_restricted: true,
            project_restrictions: [
                {
                    id: 101,
                    label: "Project A",
                    url: "/projects/project-a",
                },
                {
                    id: 103,
                    label: "Le projet C",
                    url: "/projects/project-c",
                },
            ],
        } as unknown as Server;

        const wrapper = shallowMount(AllowedProjectsTable, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [CONFIG as symbol]: {
                        servers: [server],
                    } as unknown as Config,
                },
            },
            props: {
                server,
                submit: vi.fn(),
            },
        });

        await wrapper.find("[data-test=filter]").setValue("project");

        expect(wrapper.text()).toContain("Project A");
        expect(wrapper.text()).not.toContain("Le projet C");
    });

    it("should allow to remove all at once", async () => {
        const server: Server = {
            id: 1,
            server_url: "https://example.com",
            is_project_restricted: true,
            project_restrictions: [
                {
                    id: 101,
                    label: "Project A",
                    url: "/projects/project-a",
                },
                {
                    id: 103,
                    label: "Le projet C",
                    url: "/projects/project-c",
                },
            ],
        } as unknown as Server;

        const wrapper = shallowMount(AllowedProjectsTable, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [CONFIG as symbol]: {
                        servers: [server],
                    } as unknown as Config,
                },
            },
            props: {
                server,
                submit: vi.fn(),
            },
        });

        expect(wrapper.find("[data-test=delete]").attributes("disabled")).toBeDefined();
        await wrapper.find("[data-test=remove-all]").setValue(true);
        expect(wrapper.find("[data-test=delete]").attributes("disabled")).toBeUndefined();

        expect(wrapper.text()).toContain("Project A");
        expect(wrapper.text()).toContain("Le projet C");
        expect(wrapper.findAll(".tlp-table-row-danger")).toHaveLength(2);
    });
});
