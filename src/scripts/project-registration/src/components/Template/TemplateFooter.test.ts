/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import * as element_checker from "../../helpers/is-element-in-viewport";
import type VueRouter from "vue-router";
import { createRouter, createWebHistory } from "vue-router";
import TemplateFooter from "./TemplateFooter.vue";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";
import AllProjectTemplates from "./AllProjectTemplates.vue";
import ProjectInformation from "../Information/ProjectInformation.vue";
import { createGettext } from "vue3-gettext";

jest.useFakeTimers();

let is_template_selected = false;
describe("TemplateFooter", () => {
    let router: VueRouter.Router;

    function createWrapper(): VueWrapper {
        const useStore = defineStore("root", {
            getters: {
                is_template_selected: () => (): boolean => {
                    return is_template_selected;
                },
            },
        });

        const pinia = createTestingPinia();
        useStore(pinia);

        router = createRouter({
            history: createWebHistory(),
            routes: [
                {
                    path: "/",
                    name: "template",
                    component: AllProjectTemplates,
                },
                {
                    path: "/information",
                    name: "information",
                    component: ProjectInformation,
                },
            ],
        });

        return shallowMount(TemplateFooter, {
            global: {
                plugins: [router, pinia, createGettext({ silent: true })],
            },
        });
    }

    describe("Next button", () => {
        it(`Enables the 'Next' button when template is selected`, () => {
            is_template_selected = true;
            const wrapper = createWrapper();

            const next_button: HTMLButtonElement = wrapper.get(
                "[data-test=project-registration-next-button]",
            ).element as HTMLButtonElement;

            expect(next_button.getAttribute("disabled")).toBeNull();
        });

        it(`Go to 'Project information' step when the 'Next' button is clicked`, () => {
            is_template_selected = true;

            const wrapper = createWrapper();
            const push = jest.spyOn(router, "push");

            wrapper.get("[data-test=project-registration-next-button]").trigger("click");

            // expect(wrapper.vm.$route.name).toBe("information");
            expect(push).toHaveBeenCalledWith({ name: "information" });
        });
    });

    describe("pinned_class", () => {
        it("Should have pinned class on scroll when element is NOT visible", () => {
            jest.spyOn(element_checker, "isElementInViewport").mockReturnValue(false);

            is_template_selected = true;
            const wrapper = createWrapper();

            const template_footer: HTMLElement = wrapper.get("[data-test=project-template-footer]")
                .element as unknown as HTMLElement;

            expect(template_footer.classList).toContain("pinned");
        });

        it("Should NOT have pinned class on scroll when element is already visible", async () => {
            is_template_selected = true;
            const wrapper = createWrapper();

            await jest.runOnlyPendingTimersAsync();

            const template_footer: HTMLElement = wrapper.get("[data-test=project-template-footer]")
                .element as unknown as HTMLElement;

            expect(template_footer.classList).not.toContain("pinned");
        });

        it("Should NOT have pinned class when no template have been selected", async () => {
            const wrapper = createWrapper();

            await jest.runOnlyPendingTimersAsync();

            const template_footer: HTMLElement = wrapper.get("[data-test=project-template-footer]")
                .element as unknown as HTMLElement;

            expect(template_footer.classList).not.toContain("pinned");
        });
    });
});
