/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../../helpers/local-vue-for-tests";
import ProjectShortName from "./ProjectShortName.vue";
import EventBus from "../../../helpers/event-bus";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";

describe("ProjectShortName", () => {
    async function createWrapper(error: boolean): Promise<Wrapper<Vue, Element>> {
        const useStore = defineStore("root", {
            getters: {
                has_error: () => error,
            },
        });

        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(ProjectShortName, {
            localVue: await createProjectRegistrationLocalVue(),
            pinia,
        });
    }

    describe("Slug display", () => {
        it(`Does not display anything if project shortname is empty`, async () => {
            const wrapper = await createWrapper(false);
            expect(wrapper.find("[data-test=project-shortname-slugified-section]").exists()).toBe(
                false,
            );
            expect(wrapper.get("[data-test=project-shortname-edit-section]").classes()).toEqual([
                "tlp-form-element",
                "project-short-name-hidden-section",
            ]);
        });

        it(`Display the edit mode when there is an error`, async () => {
            const wrapper = await createWrapper(true);

            expect(wrapper.find("[data-test=project-shortname-slugified-section]").exists()).toBe(
                false,
            );

            await EventBus.$emit("slugify-project-name", "My");

            expect(wrapper.get("[data-test=project-shortname-edit-section]").classes()).toEqual([
                "tlp-form-element",
                "project-short-name-edit-section",
            ]);
        });

        it(`Displays slugged project name`, async () => {
            const wrapper = await createWrapper(false);

            await EventBus.$emit("slugify-project-name", "My project");

            expect(wrapper.find("[data-test=project-shortname-slugified-section]").exists()).toBe(
                true,
            );
            expect(wrapper.get("[data-test=project-shortname-edit-section]").classes()).toEqual([
                "tlp-form-element",
                "project-short-name-hidden-section",
            ]);
        });
    });

    describe("Slugify parent label", () => {
        it(`Has an error when shortname has less than 3 characters`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            const wrapper = await createWrapper(false);

            await EventBus.$emit("slugify-project-name", "My");

            expect(wrapper.find("[data-test=has-error-slug]").exists()).toBe(true);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: "my",
                name: "My",
            });
        });

        it(`Has no error when shortname has exactly 30 characters`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");
            const wrapper = await createWrapper(false);

            await EventBus.$emit("slugify-project-name", "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa");

            expect(wrapper.find("[data-test=has-error-slug]").exists()).toBe(false);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa",
                name: "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa",
            });
        });

        it(`Truncates slugified shortname to 30 characters`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");
            const wrapper = await createWrapper(false);

            await EventBus.$emit("slugify-project-name", "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaabbbbb");

            expect(wrapper.find("[data-test=has-error-slug]").exists()).toBe(false);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa",
                name: "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaabbbbb",
            });
        });

        it(`Has an error when shortname start by a numerical character`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");
            const wrapper = await createWrapper(false);

            await EventBus.$emit("slugify-project-name", "0My project");

            expect(wrapper.find("[data-test=has-error-slug]").exists()).toBe(true);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: "0my-project",
                name: "0My project",
            });
        });

        it(`Store and validate the project name`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            const wrapper = await createWrapper(false);
            await EventBus.$emit("slugify-project-name", "my project name");

            expect(wrapper.find("[data-test=has-error-slug]").exists()).toBe(false);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: "my-project-name",
                name: "my project name",
            });
        });

        it(`Slugified project name handle correctly the accents`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            const wrapper = await createWrapper(false);
            await EventBus.$emit("slugify-project-name", "accentué ç è é ù ë");

            expect(wrapper.find("[data-test=has-error-slug]").exists()).toBe(false);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: "accentue-c-e-e-u-e",
                name: "accentué ç è é ù ë",
            });
        });

        it(`Slugified project name should be lower case`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            const wrapper = await createWrapper(false);
            await EventBus.$emit("slugify-project-name", "My Project Short Name");

            expect(wrapper.find("[data-test=has-error-slug]").exists()).toBe(false);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: "my-project-short-name",
                name: "My Project Short Name",
            });
        });

        it(`Slugified project name handle correctly the special characters`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            const wrapper = await createWrapper(false);
            await EventBus.$emit("slugify-project-name", "valid 11.11");

            expect(wrapper.find("[data-test=has-error-slug]").exists()).toBe(false);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: "valid-11-11",
                name: "valid 11.11",
            });
        });

        it(`Slugified project name does not repeat replacement when special characters are siblings`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");
            const wrapper = await createWrapper(false);
            await EventBus.$emit("slugify-project-name", "valid'*_©®11");

            expect(wrapper.find("[data-test=has-error-slug]").exists()).toBe(false);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: "valid-11",
                name: "valid'*_©®11",
            });
        });

        it(`Does not slugify in edit mode`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");
            const wrapper = await createWrapper(false);
            await EventBus.$emit("slugify-project-name", "test-project!!!!");

            expect(wrapper.find("[data-test=has-error-slug]").exists()).toBe(false);

            expect(event_bus_emit).not.toHaveBeenCalledWith("update-project-name", {
                slugified_name: "test-project!!!!",
                name: "test-project",
            });
        });
    });

    describe("Project shortname update", () => {
        it(`Validate string but not calls slugify when shortname is in edit mode`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");
            const wrapper = await createWrapper(false);
            await EventBus.$emit("slugify-project-name", "Accentué ç è é ù ë");

            await wrapper.get("[data-test=project-shortname-slugified-section]").trigger("click");
            await wrapper.get("[data-test=new-project-shortname]").setValue("Accentué ç è é ù ë");
            expect(wrapper.find("[data-test=has-error-slug]").exists()).toBe(true);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: "Accentué ç è é ù ë",
                name: "Accentué ç è é ù ë",
            });
        });
    });
});
