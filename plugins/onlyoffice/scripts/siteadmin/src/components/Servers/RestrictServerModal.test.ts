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

import { shallowMount } from "@vue/test-utils";
import RestrictServerModal from "./RestrictServerModal.vue";
import { createGettext } from "vue3-gettext";
import type { Server } from "../../type";
import AllowedProjectsTable from "./Restrict/AllowedProjectsTable.vue";

describe("RestrictServerModal", () => {
    it("should disable if there is no project to allow or revoke", async () => {
        const wrapper = shallowMount(RestrictServerModal, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                server: {
                    id: 1,
                    is_project_restricted: true,
                    project_restrictions: [],
                } as unknown as Server,
            },
        });

        expect(wrapper.find("[data-test=submit]").attributes("disabled")).toBeDefined();

        await wrapper.findComponent(AllowedProjectsTable).props("set_nb_to_allow")(1);
        await wrapper.findComponent(AllowedProjectsTable).props("set_nb_to_revoke")(0);
        expect(wrapper.find("[data-test=submit]").attributes("disabled")).toBeUndefined();

        await wrapper.findComponent(AllowedProjectsTable).props("set_nb_to_allow")(0);
        await wrapper.findComponent(AllowedProjectsTable).props("set_nb_to_revoke")(1);
        expect(wrapper.find("[data-test=submit]").attributes("disabled")).toBeUndefined();

        await wrapper.findComponent(AllowedProjectsTable).props("set_nb_to_allow")(0);
        await wrapper.findComponent(AllowedProjectsTable).props("set_nb_to_revoke")(0);
        expect(wrapper.find("[data-test=submit]").attributes("disabled")).toBeDefined();
    });

    it("should display a warning if project is about to be moved from one server to another", async () => {
        const wrapper = shallowMount(RestrictServerModal, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                server: {
                    id: 1,
                    is_project_restricted: true,
                    project_restrictions: [],
                } as unknown as Server,
            },
        });

        expect(wrapper.find("[data-test=warning-moved-project]").exists()).toBe(false);

        await wrapper.findComponent(AllowedProjectsTable).props("set_nb_to_move")(1);
        expect(wrapper.find("[data-test=warning-moved-project]").exists()).toBe(true);

        await wrapper.findComponent(AllowedProjectsTable).props("set_nb_to_move")(0);
        expect(wrapper.find("[data-test=warning-moved-project]").exists()).toBe(false);
    });
});
