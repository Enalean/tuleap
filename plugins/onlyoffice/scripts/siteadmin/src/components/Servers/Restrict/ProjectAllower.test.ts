/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import * as fetch_result from "@tuleap/fetch-result";
import * as strict_inject from "@tuleap/vue-strict-inject";

const getSpy = vi.fn();
vi.mock("@tuleap/fetch-result");
vi.mock("@tuleap/autocomplete-for-select2", () => {
    return {
        autocomplete_projects_for_select2(select: HTMLSelectElement): void {
            select.options.add(new Option("ACME Corp (acme)"));
            select.options.add(new Option("EVIL Corp (evil)"));
        },
    };
});
vi.mock("@tuleap/vue-strict-inject");

import { createGettext } from "vue3-gettext";
import { shallowMount } from "@vue/test-utils";
import ProjectAllower from "./ProjectAllower.vue";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { uri } from "@tuleap/fetch-result";
import type { Config, Server } from "../../../type";
import MoveProjectConfirmationModal from "./MoveProjectConfirmationModal.vue";

describe("ProjectAllower", () => {
    beforeEach((): void => {
        vi.spyOn(fetch_result, "getJSON").mockImplementation(getSpy);
    });

    it("should fetch project information and add it to the list", async () => {
        const server: Server = { id: 1 } as Server;

        const error = vi.fn();

        getSpy.mockReturnValue(okAsync([{ id: 102, label: "ACME Corp", shortname: "acme" }]));
        vi.spyOn(strict_inject, "strictInject").mockReturnValue({
            servers: [server],
        } as unknown as Config);

        const wrapper = shallowMount(ProjectAllower, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                server,
                error,
            },
        });

        await wrapper.find({ ref: "select" }).setValue("ACME Corp (acme)");
        await wrapper.find("[data-test=button]").trigger("click");

        expect(getSpy).toHaveBeenCalledWith(uri`/api/projects`, {
            params: { query: '{"shortname":"acme"}' },
        });
        expect(error).toHaveBeenCalledWith("");
        const input = wrapper.find("[data-test=project-to-add]").element;
        if (!(input instanceof HTMLInputElement)) {
            throw Error("Unable to find input");
        }
        expect(input.value).toBe("102");
    });

    it("should display an error if no project matchs the shortname", async () => {
        const server: Server = { id: 1 } as Server;

        const error = vi.fn();

        getSpy.mockReturnValue(okAsync([]));
        vi.spyOn(strict_inject, "strictInject").mockReturnValue({
            servers: [server],
        } as unknown as Config);

        const wrapper = shallowMount(ProjectAllower, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                server,
                error,
            },
        });

        await wrapper.find({ ref: "select" }).setValue("ACME Corp (acme)");
        await wrapper.find("[data-test=button]").trigger("click");

        expect(getSpy).toHaveBeenCalledWith(uri`/api/projects`, {
            params: { query: '{"shortname":"acme"}' },
        });
        expect(error).toHaveBeenCalledWith("Unable to find project information");
        expect(wrapper.find("[data-test=project-to-add]").exists()).toBe(false);
    });

    it("should display an error if retrieval of the project fails", async () => {
        const server: Server = { id: 1 } as Server;

        const error = vi.fn();

        getSpy.mockReturnValue(errAsync(Fault.fromMessage("Something went wrong")));
        vi.spyOn(strict_inject, "strictInject").mockReturnValue({
            servers: [server],
        } as unknown as Config);

        const wrapper = shallowMount(ProjectAllower, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                server,
                error,
            },
        });

        await wrapper.find({ ref: "select" }).setValue("ACME Corp (acme)");
        await wrapper.find("[data-test=button]").trigger("click");

        expect(getSpy).toHaveBeenCalledWith(uri`/api/projects`, {
            params: { query: '{"shortname":"acme"}' },
        });
        expect(error).toHaveBeenCalledWith("Unable to find project information");
        expect(wrapper.find("[data-test=project-to-add]").exists()).toBe(false);
    });

    it("should display an error if more than one project match the shortname", async () => {
        const server: Server = { id: 1 } as Server;

        const error = vi.fn();

        getSpy.mockReturnValue(
            okAsync([
                { id: 102, label: "ACME Corp", shortname: "acme" },
                { id: 103, label: "ACME Corp clone", shortname: "acme" },
            ]),
        );
        vi.spyOn(strict_inject, "strictInject").mockReturnValue({
            servers: [server],
        } as unknown as Config);

        const wrapper = shallowMount(ProjectAllower, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                server,
                error,
            },
        });

        await wrapper.find({ ref: "select" }).setValue("ACME Corp (acme)");
        await wrapper.find("[data-test=button]").trigger("click");

        expect(getSpy).toHaveBeenCalledWith(uri`/api/projects`, {
            params: { query: '{"shortname":"acme"}' },
        });
        expect(error).toHaveBeenCalledWith("Unable to find project information");
        expect(wrapper.find("[data-test=project-to-add]").exists()).toBe(false);
    });

    it("should ask for confirmation before moving a project to another one", async () => {
        const server: Server = {
            id: 1,
            is_project_restricted: true,
            project_restrictions: [],
        } as unknown as Server;
        const another_server: Server = {
            id: 2,
            is_project_restricted: true,
            project_restrictions: [{ id: 102, label: "ACME Corp", url: "/projects/acme" }],
        } as unknown as Server;

        const error = vi.fn();

        getSpy.mockReturnValue(okAsync([{ id: 102, label: "ACME Corp", shortname: "acme" }]));
        vi.spyOn(strict_inject, "strictInject").mockReturnValue({
            servers: [server, another_server],
        } as unknown as Config);

        const wrapper = shallowMount(ProjectAllower, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                server,
                error,
            },
        });

        await wrapper.find({ ref: "select" }).setValue("ACME Corp (acme)");
        await wrapper.find("[data-test=button]").trigger("click");

        expect(getSpy).toHaveBeenCalledWith(uri`/api/projects`, {
            params: { query: '{"shortname":"acme"}' },
        });
        expect(wrapper.find("[data-test=project-to-add]").exists()).toBe(false);
        expect(wrapper.findComponent(MoveProjectConfirmationModal).exists()).toBe(true);
        await wrapper.findComponent(MoveProjectConfirmationModal).props("move")();
        const input = wrapper.find("[data-test=project-to-add]").element;
        if (!(input instanceof HTMLInputElement)) {
            throw Error("Unable to find input");
        }
        expect(input.value).toBe("102");
    });
});
