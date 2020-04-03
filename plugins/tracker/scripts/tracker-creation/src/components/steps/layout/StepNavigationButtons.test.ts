/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { shallowMount, Wrapper } from "@vue/test-utils";
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { createTrackerCreationLocalVue } from "../../../helpers/local-vue-for-tests";
import StepNavigationButtons from "./StepNavigationButtons.vue";
import VueRouter from "vue-router";
import { createRouter } from "../../../router";

describe("StepNavigationButtons", () => {
    async function getWrapper(
        props: {},
        is_ready_for_step_2 = true,
        is_ready_to_submit = true,
        has_form_been_submitted = false
    ): Promise<Wrapper<StepNavigationButtons>> {
        const router: VueRouter = createRouter("my-project");

        jest.spyOn(router, "push").mockImplementation();

        return shallowMount(StepNavigationButtons, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        has_form_been_submitted,
                    },
                    getters: {
                        is_ready_for_step_2,
                        is_ready_to_submit,
                    },
                }),
            },
            propsData: {
                ...props,
            },
            localVue: await createTrackerCreationLocalVue(),
            router,
        });
    }

    it("Does not display the [<- back] button when there is no previous step", async () => {
        const wrapper = await getWrapper({
            nextStepName: "step-2",
        });

        expect(wrapper.find("[data-test=button-next]").exists()).toBe(true);
        expect(wrapper.find("[data-test=button-back]").exists()).toBe(false);
    });

    it("Does not display the [next ->] button when there is no next step, but displays the submit button instead", async () => {
        const wrapper = await getWrapper({
            previousStepName: "step-1",
        });

        expect(wrapper.find("[data-test=button-next]").exists()).toBe(false);
        expect(wrapper.find("[data-test=button-back]").exists()).toBe(true);
        expect(wrapper.find("[data-test=button-create-my-tracker]").exists()).toBe(true);
    });

    it("Disables the [Create my tracker] submit button when the creation is not ready to be submitted", async () => {
        const wrapper = await getWrapper({ previousStepName: "step-1" }, false, false);
        const submit_button = wrapper.get("[data-test=button-create-my-tracker]");

        expect(submit_button.attributes("disabled")).toBe("disabled");
    });

    it("Disables the [Create my tracker] submit button when the form has been submitted", async () => {
        const wrapper = await getWrapper({ previousStepName: "step-1" }, false, false, true);
        const submit_button = wrapper.get("[data-test=button-create-my-tracker]");

        expect(submit_button.attributes("disabled")).toBe("disabled");
        expect(submit_button.find("i.fa-spin").exists()).toBe(true);
    });

    it("Clicking on [Create my tracker] sets the form as submitted", async () => {
        const wrapper = await getWrapper({ previousStepName: "step-1" }, false, true, false);
        const submit_button = wrapper.get("[data-test=button-create-my-tracker]");

        submit_button.trigger("click");

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith(
            "setCreationFormHasBeenSubmitted",
            expect.anything()
        );
    });

    it("Disables the [next ->] button when the creation is not ready for the step 2 and to click on it does nothing", async () => {
        const wrapper = await getWrapper({ nextStepName: "step-2" }, false);
        const next_step_button = wrapper.get("[data-test=button-next]");

        expect(next_step_button.attributes("disabled")).toBe("disabled");

        next_step_button.trigger("click");

        expect(wrapper.vm.$router.push).not.toHaveBeenCalled();
    });

    it("Clicking on the [next ->] button makes the app navigate to the next step", async () => {
        const wrapper = await getWrapper({ nextStepName: "step-2" }, true);

        wrapper.get("[data-test=button-next]").trigger("click");

        expect(wrapper.vm.$router.push).toHaveBeenCalledWith({ name: "step-2" });
    });
});
