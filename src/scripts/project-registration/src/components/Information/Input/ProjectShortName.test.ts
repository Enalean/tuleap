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
import type { DefaultData } from "vue/types/options";
import EventBus from "../../../helpers/event-bus";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";

describe("ProjectShortName", () => {
    async function createWrapper(
        data: DefaultData<ProjectShortName>,
    ): Promise<Wrapper<ProjectShortName>> {
        const useStore = defineStore("root", {
            getters: {
                has_error: () => false,
            },
        });

        const pinia = createTestingPinia();
        useStore(pinia);

        return shallowMount(ProjectShortName, {
            data(): DefaultData<ProjectShortName> {
                return { ...data };
            },
            localVue: await createProjectRegistrationLocalVue(),
            pinia,
        });
    }

    describe("Slug display", () => {
        it(`Does not display anything if project shortname is empty`, async () => {
            const data = {
                slugified_project_name: "",
                has_slug_error: false,
                is_in_edit_mode: false,
            };
            const wrapper = await createWrapper(data);
            expect(wrapper.find("[data-test=project-shortname-slugified-section]").exists()).toBe(
                false,
            );
            expect(wrapper.get("[data-test=project-shortname-edit-section]").classes()).toEqual([
                "tlp-form-element",
                "project-short-name-hidden-section",
            ]);
        });

        it(`Display the edit mode if user switched to edit short name mode`, async () => {
            const data = {
                slugified_project_name: "",
                has_slug_error: false,
                is_in_edit_mode: true,
            };
            const wrapper = await createWrapper(data);

            expect(wrapper.find("[data-test=project-shortname-slugified-section]").exists()).toBe(
                false,
            );
            expect(wrapper.get("[data-test=project-shortname-edit-section]").classes()).toEqual([
                "tlp-form-element",
                "project-short-name-edit-section",
            ]);
        });

        it(`Displays slugged project name`, async () => {
            const data = {
                slugified_project_name: "my-short-name",
                has_slug_error: false,
                is_in_edit_mode: false,
            };
            const wrapper = await createWrapper(data);

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

            const data = {
                slugified_project_name: "",
                has_slug_error: false,
                is_in_edit_mode: false,
            };
            const wrapper = await createWrapper(data);

            EventBus.$emit("slugify-project-name", "My");

            expect(wrapper.vm.$data.slugified_project_name).toBe("my");
            expect(wrapper.vm.$data.has_slug_error).toBe(true);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: wrapper.vm.$data.slugified_project_name,
                name: "My",
            });
        });

        it(`Has no error when shortname has exactly 30 characters`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            const data = {
                slugified_project_name: "",
                has_slug_error: false,
                is_in_edit_mode: false,
            };
            const wrapper = await createWrapper(data);

            EventBus.$emit("slugify-project-name", "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa");

            expect(wrapper.vm.$data.slugified_project_name).toBe("aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa");
            expect(wrapper.vm.$data.has_slug_error).toBe(false);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: wrapper.vm.$data.slugified_project_name,
                name: "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa",
            });
        });

        it(`Truncates slugified shortname to 30 characters`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            const data = {
                slugified_project_name: "",
                has_slug_error: false,
                is_in_edit_mode: false,
            };
            const wrapper = await createWrapper(data);

            EventBus.$emit("slugify-project-name", "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaabbbbb");

            expect(wrapper.vm.$data.slugified_project_name).toBe("aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa");
            expect(wrapper.vm.$data.has_slug_error).toBe(false);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: wrapper.vm.$data.slugified_project_name,
                name: "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaabbbbb",
            });
        });

        it(`Has an error when shortname start by a numerical character`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            const data = {
                slugified_project_name: "",
                has_slug_error: false,
                is_in_edit_mode: false,
            };
            const wrapper = await createWrapper(data);

            EventBus.$emit("slugify-project-name", "0My project");

            expect(wrapper.vm.$data.slugified_project_name).toBe("0my-project");
            expect(wrapper.vm.$data.has_slug_error).toBe(true);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: wrapper.vm.$data.slugified_project_name,
                name: "0My project",
            });
        });

        it(`Store and validate the project name`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            const data = {
                slugified_project_name: "",
                has_slug_error: false,
                is_in_edit_mode: false,
            };
            const wrapper = await createWrapper(data);
            EventBus.$emit("slugify-project-name", "my project name");

            expect(wrapper.vm.$data.slugified_project_name).toBe("my-project-name");
            expect(wrapper.vm.$data.has_slug_error).toBe(false);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: wrapper.vm.$data.slugified_project_name,
                name: "my project name",
            });
        });

        it(`Slugified project name handle correctly the accents`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            const data = {
                slugified_project_name: "",
                has_slug_error: false,
                is_in_edit_mode: false,
            };
            const wrapper = await createWrapper(data);
            EventBus.$emit("slugify-project-name", "accentué ç è é ù ë");

            expect(wrapper.vm.$data.slugified_project_name).toBe("accentue-c-e-e-u-e");
            expect(wrapper.vm.$data.has_slug_error).toBe(false);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: wrapper.vm.$data.slugified_project_name,
                name: "accentué ç è é ù ë",
            });
        });

        it(`Slugified project name should be lower case`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            const data = {
                slugified_project_name: "",
                has_slug_error: false,
                is_in_edit_mode: false,
            };
            const wrapper = await createWrapper(data);
            EventBus.$emit("slugify-project-name", "My Project Short Name");

            expect(wrapper.vm.$data.slugified_project_name).toBe("my-project-short-name");
            expect(wrapper.vm.$data.has_slug_error).toBe(false);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: wrapper.vm.$data.slugified_project_name,
                name: "My Project Short Name",
            });
        });

        it(`Slugified project name handle correctly the special characters`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            const data = {
                slugified_project_name: "",
                has_slug_error: false,
                is_in_edit_mode: false,
            };
            const wrapper = await createWrapper(data);
            EventBus.$emit("slugify-project-name", "valid 11.11");

            expect(wrapper.vm.$data.slugified_project_name).toBe("valid-11-11");
            expect(wrapper.vm.$data.has_slug_error).toBe(false);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: wrapper.vm.$data.slugified_project_name,
                name: "valid 11.11",
            });
        });

        it(`Slugified project name does not repeat replacement when special characters are siblings`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            const data = {
                slugified_project_name: "",
                has_slug_error: false,
                is_in_edit_mode: false,
            };
            const wrapper = await createWrapper(data);
            EventBus.$emit("slugify-project-name", "valid'*©®11");

            expect(wrapper.vm.$data.slugified_project_name).toBe("valid-11");
            expect(wrapper.vm.$data.has_slug_error).toBe(false);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: wrapper.vm.$data.slugified_project_name,
                name: "valid'*©®11",
            });
        });

        it(`Does not slugify in edit mode`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            const data = {
                slugified_project_name: "",
                has_slug_error: false,
                is_in_edit_mode: true,
                project_name: "test-project",
            };
            const wrapper = await createWrapper(data);
            EventBus.$emit("slugify-project-name", "test-project!!!!");

            expect(wrapper.vm.$data.slugified_project_name).toBe("");
            expect(wrapper.vm.$data.has_slug_error).toBe(false);

            expect(event_bus_emit).not.toHaveBeenCalledWith("update-project-name", {
                slugified_name: "test-project!!!!",
                name: "test-project",
            });
        });
    });

    describe("Project shortname update", () => {
        it(`Validate string but not calls slugify when shortname is in edit mode`, async () => {
            const event_bus_emit = jest.spyOn(EventBus, "$emit");

            const data = {
                slugified_project_name: "my-short-name",
                has_slug_error: false,
                is_in_edit_mode: false,
                project_name: "my-short-name",
            };
            const wrapper = await createWrapper(data);

            wrapper.get("[data-test=new-project-shortname]").setValue("Original");

            wrapper.get("[data-test=project-shortname-slugified-section]").trigger("click");

            wrapper.get("[data-test=new-project-shortname]").setValue("Accentué ç è é ù ë");
            expect(wrapper.vm.$data.slugified_project_name).toBe("Accentué ç è é ù ë");
            expect(wrapper.vm.$data.has_slug_error).toBe(true);

            expect(event_bus_emit).toHaveBeenCalledWith("update-project-name", {
                slugified_name: "Accentué ç è é ù ë",
                name: wrapper.vm.$data.project_name,
            });
        });
    });
});
