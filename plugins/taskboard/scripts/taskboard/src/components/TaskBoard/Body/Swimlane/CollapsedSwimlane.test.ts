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

import { shallowMount } from "@vue/test-utils";
import CollapsedSwimlane from "./CollapsedSwimlane.vue";
import { createTaskboardLocalVue } from "../../../../helpers/local-vue-for-test";
import { createStoreMock } from "../../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { ColumnDefinition, Swimlane } from "../../../../type";
import { RootState } from "../../../../store/type";

describe("CollapsedSwimlane", () => {
    it("displays a toggle icon and a card with minimal information", async () => {
        const $store = createStoreMock({
            state: {
                swimlane: {},
                column: {
                    columns: [] as ColumnDefinition[],
                },
            } as RootState,
        });
        const wrapper = shallowMount(CollapsedSwimlane, {
            localVue: await createTaskboardLocalVue(),
            mocks: { $store },
            propsData: {
                swimlane: {
                    card: {
                        color: "fiesta-red",
                    },
                } as Swimlane,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("expand the swimlane when user click on the toggle icon", async () => {
        const $store = createStoreMock({
            state: {
                swimlane: {},
                column: {
                    columns: [] as ColumnDefinition[],
                },
            } as RootState,
        });
        const swimlane: Swimlane = {
            card: {
                color: "fiesta-red",
            },
        } as Swimlane;
        const wrapper = shallowMount(CollapsedSwimlane, {
            localVue: await createTaskboardLocalVue(),
            mocks: { $store },
            propsData: { swimlane },
        });
        wrapper.get(".taskboard-swimlane-toggle").trigger("click");
        expect($store.dispatch).toHaveBeenCalledWith("swimlane/expandSwimlane", swimlane);
    });
});
