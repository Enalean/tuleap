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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
import { describe, beforeEach, it, expect, vi } from "vitest";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import WidgetModalContent from "./WidgetModalContent.vue";
import { createLocalVueForTests } from "../../helpers/local-vue.js";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";
import type { Artifact } from "@tuleap/plugin-timetracking-rest-api-types";
import type Vue from "vue";

describe("Given a personal timetracking widget modal", () => {
    let rest_feedback: { message: string; type: string };
    let is_add_mode: boolean;
    let current_artifact: Artifact;
    const setAddMode = vi.fn();

    async function getWidgetModalContentInstance(): Promise<Wrapper<Vue>> {
        const useStore = defineStore("root", {
            state: () => ({
                rest_feedback: rest_feedback,
                is_add_mode: is_add_mode,
            }),
            getters: {
                current_artifact: () => current_artifact,
            },
            actions: {
                setAddMode: setAddMode,
            },
        });
        const pinia = createTestingPinia({ stubActions: false, createSpy: vi.fn });
        useStore(pinia);

        const component_options = {
            localVue: await createLocalVueForTests(),
            propsData: {
                artifact: {},
                project: {},
                timeData: {},
            },
            pinia,
        };
        return shallowMount(WidgetModalContent, component_options);
    }

    beforeEach(() => {
        rest_feedback = { message: "", type: "" };
        is_add_mode = false;
        current_artifact = {} as Artifact;
    });

    it("When there is no REST feedback, then feedback message should not be displayed", async () => {
        const wrapper = await getWidgetModalContentInstance();
        expect(wrapper.find("[data-test=feedback]").exists()).toBeFalsy();
    });

    it("When there is REST feedback, then feedback message should be displayed", async () => {
        rest_feedback.type = "success";
        const wrapper = await getWidgetModalContentInstance();
        expect(wrapper.find("[data-test=feedback]").exists()).toBeTruthy();
    });

    it("When add mode button is triggered, then setAddMode should be called", async () => {
        const wrapper = await getWidgetModalContentInstance();
        wrapper.get("[data-test=button-set-add-mode]").trigger("click");
        expect(setAddMode).toHaveBeenCalledWith(true);
    });
});
