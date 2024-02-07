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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import TimetrackingWidget from "./TimetrackingWidget.vue";
import WidgetReadingMode from "./WidgetReadingMode.vue";
import WidgetWritingMode from "./WidgetWritingMode.vue";
import WidgetArtifactTable from "./WidgetArtifactTable.vue";
import { createTestingPinia } from "@pinia/testing";
import { defineStore } from "pinia";
import { createLocalVueForTests } from "../helpers/local-vue";
import type Vue from "vue";

const userId = 102;
const userLocale = "fr_FR";

describe("Given a personal timetracking widget", () => {
    let reading_mode: boolean;

    async function getPersonalWidgetInstance(): Promise<Wrapper<Vue>> {
        const useStore = defineStore("root", {
            state: () => ({
                reading_mode: reading_mode,
            }),
            actions: {
                initUserId() {},
                initUserLocale() {},
            },
        });
        const pinia = createTestingPinia({ stubActions: false });
        useStore(pinia);

        const component_options = {
            propsData: {
                userId,
                userLocale,
            },
            localVue: await createLocalVueForTests(),
            pinia,
        };
        return shallowMount(TimetrackingWidget, component_options);
    }

    it("When reading mode is true, then reading should be displayed but not writing mode", async () => {
        reading_mode = true;
        const wrapper = await getPersonalWidgetInstance();
        expect(wrapper.findComponent(WidgetReadingMode).exists()).toBeTruthy();
        expect(wrapper.findComponent(WidgetWritingMode).exists()).toBeFalsy();
        expect(wrapper.findComponent(WidgetArtifactTable).exists()).toBeTruthy();
    });

    it("When reading mode is false, then writing should be displayed but not reading mode", async () => {
        reading_mode = false;
        const wrapper = await getPersonalWidgetInstance();
        expect(wrapper.findComponent(WidgetReadingMode).exists()).toBeFalsy();
        expect(wrapper.findComponent(WidgetWritingMode).exists()).toBeTruthy();
    });
});
