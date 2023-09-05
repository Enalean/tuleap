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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createProjectRegistrationLocalVue } from "../../helpers/local-vue-for-tests";
import * as element_checker from "../../helpers/is-element-in-viewport";
import VueRouter from "vue-router";
import type { Store } from "@tuleap/vuex-store-wrapper-jest";
import type { RootState } from "../../store/type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import TemplateFooter from "./TemplateFooter.vue";

describe("TemplateFooter", () => {
    let factory: Wrapper<TemplateFooter>, router: VueRouter, store: Store, state: RootState;
    beforeEach(async () => {
        state = {} as RootState;
        const getters = {
            is_template_selected: false,
        };

        const store_options = {
            state,
            getters,
        };

        store = createStoreMock(store_options);

        router = new VueRouter({
            routes: [
                {
                    path: "/",
                    name: "template",
                },
                {
                    path: "/information",
                    name: "information",
                },
            ],
        });

        factory = shallowMount(TemplateFooter, {
            localVue: await createProjectRegistrationLocalVue(),
            router,
            mocks: { $store: store },
        });
    });

    describe("Next button", () => {
        it(`Enables the 'Next' button when template is selected`, async () => {
            const wrapper = factory;

            const next_button: HTMLButtonElement = wrapper.get(
                "[data-test=project-registration-next-button]",
            ).element as HTMLButtonElement;

            expect(next_button.getAttribute("disabled")).toBe("disabled");

            wrapper.vm.$store.getters.is_template_selected = true;
            await wrapper.vm.$nextTick();

            expect(next_button.getAttribute("disabled")).toBeNull();
        });

        it(`Go to 'Project information' step when the 'Next' button is clicked`, async () => {
            const wrapper = factory;

            wrapper.vm.$store.getters.is_template_selected = true;
            await wrapper.vm.$nextTick();

            wrapper.get("[data-test=project-registration-next-button]").trigger("click");

            expect(wrapper.vm.$route.name).toBe("information");
        });
    });

    describe("pinned_class", () => {
        it("Should have pinned class on scroll when element is NOT visible", async () => {
            jest.spyOn(element_checker, "isElementInViewport").mockReturnValue(false);

            const wrapper = shallowMount(TemplateFooter, {
                localVue: await createProjectRegistrationLocalVue(),
                router,
                mocks: { $store: store },
            });

            wrapper.vm.$store.getters.is_template_selected = true;
            await wrapper.vm.$nextTick();

            const template_footer: HTMLElement = wrapper.get("[data-test=project-template-footer]")
                .element as unknown as HTMLElement;

            expect(template_footer.classList).toContain("pinned");
        });

        it("Should NOT have pinned class on scroll when element is already visible", () => {
            const wrapper = factory;

            wrapper.vm.$store.getters.is_template_selected = true;

            const template_footer: HTMLElement = wrapper.get("[data-test=project-template-footer]")
                .element as unknown as HTMLElement;

            expect(template_footer.classList).not.toContain("pinned");
        });

        it("Should NOT have pinned class when no template have been selected", () => {
            const wrapper = factory;

            wrapper.vm.$store.getters.is_template_selected = false;
            const template_footer: HTMLElement = wrapper.get("[data-test=project-template-footer]")
                .element as unknown as HTMLElement;

            expect(template_footer.classList).not.toContain("pinned");
        });
    });
});
