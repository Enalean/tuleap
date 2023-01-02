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

import { describe, expect, it, vi } from "vitest";

const getSpy = vi.fn();
vi.mock("@tuleap/fetch-result", () => {
    return {
        getJSON<TypeOfJSONPayload>(
            uri: string,
            options?: OptionsWithAutoEncodedParameters
        ): ResultAsync<TypeOfJSONPayload, Fault> {
            return getSpy(uri, options);
        },
    };
});
vi.mock("@tuleap/autocomplete-for-select2", () => {
    return {
        autocomplete_projects_for_select2(select: HTMLSelectElement): void {
            select.options.add(new Option("ACME Corp (acme)"));
            select.options.add(new Option("EVIL Corp (evil)"));
        },
    };
});

import { createGettext } from "vue3-gettext";
import { shallowMount } from "@vue/test-utils";
import ProjectAllower from "./ProjectAllower.vue";
import type { ResultAsync } from "neverthrow";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { OptionsWithAutoEncodedParameters } from "@tuleap/fetch-result";

describe("ProjectAllower", () => {
    it("should fetch project information and add it to the list", async () => {
        const add = vi.fn();
        const error = vi.fn();

        getSpy.mockReturnValue(okAsync([{ id: 102, label: "ACME Corp", shortname: "acme" }]));

        const wrapper = shallowMount(ProjectAllower, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                add,
                error,
            },
        });

        await wrapper.find({ ref: "select" }).setValue("ACME Corp (acme)");
        await wrapper.find("[data-test=button]").trigger("click");

        expect(getSpy).toHaveBeenCalledWith(`/api/projects`, {
            params: { query: '{"shortname":"acme"}' },
        });
        expect(error).toHaveBeenCalledWith("");
        expect(add).toHaveBeenCalledWith({ id: 102, label: "ACME Corp", url: "/projects/acme" });
    });

    it("should encode the shortname in the project url", async () => {
        const add = vi.fn();
        const error = vi.fn();

        getSpy.mockReturnValue(okAsync([{ id: 102, label: "ACME Corp", shortname: "ac/me" }]));

        const wrapper = shallowMount(ProjectAllower, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                add,
                error,
            },
        });

        await wrapper.find({ ref: "select" }).setValue("ACME Corp (ac/me)");
        await wrapper.find("[data-test=button]").trigger("click");

        expect(add).toHaveBeenCalledWith({ id: 102, label: "ACME Corp", url: "/projects/ac%2Fme" });
    });

    it("should display an error if no project matchs the shortname", async () => {
        const add = vi.fn();
        const error = vi.fn();

        getSpy.mockReturnValue(okAsync([]));

        const wrapper = shallowMount(ProjectAllower, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                add,
                error,
            },
        });

        await wrapper.find({ ref: "select" }).setValue("ACME Corp (acme)");
        await wrapper.find("[data-test=button]").trigger("click");

        expect(getSpy).toHaveBeenCalledWith(`/api/projects`, {
            params: { query: '{"shortname":"acme"}' },
        });
        expect(error).toHaveBeenCalledWith("Unable to find project information");
        expect(add).not.toHaveBeenCalled();
    });

    it("should display an error if retrieval of the project fails", async () => {
        const add = vi.fn();
        const error = vi.fn();

        getSpy.mockReturnValue(errAsync(Fault.fromMessage("Something went wrong")));

        const wrapper = shallowMount(ProjectAllower, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                add,
                error,
            },
        });

        await wrapper.find({ ref: "select" }).setValue("ACME Corp (acme)");
        await wrapper.find("[data-test=button]").trigger("click");

        expect(getSpy).toHaveBeenCalledWith(`/api/projects`, {
            params: { query: '{"shortname":"acme"}' },
        });
        expect(error).toHaveBeenCalledWith("Unable to find project information");
        expect(add).not.toHaveBeenCalled();
    });

    it("should display an error if more than one project match the shortname", async () => {
        const add = vi.fn();
        const error = vi.fn();

        getSpy.mockReturnValue(
            okAsync([
                { id: 102, label: "ACME Corp", shortname: "acme" },
                { id: 103, label: "ACME Corp clone", shortname: "acme" },
            ])
        );

        const wrapper = shallowMount(ProjectAllower, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                add,
                error,
            },
        });

        await wrapper.find({ ref: "select" }).setValue("ACME Corp (acme)");
        await wrapper.find("[data-test=button]").trigger("click");

        expect(getSpy).toHaveBeenCalledWith(`/api/projects`, {
            params: { query: '{"shortname":"acme"}' },
        });
        expect(error).toHaveBeenCalledWith("Unable to find project information");
        expect(add).not.toHaveBeenCalled();
    });
});
