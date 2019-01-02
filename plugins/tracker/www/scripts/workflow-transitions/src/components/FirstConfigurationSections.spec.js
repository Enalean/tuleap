/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

import { shallowMount } from "@vue/test-utils";
import FirstConfigurationSections from "./FirstConfigurationSections.vue";
import { createList } from "../support/factories.js";
import localVue from "../support/local-vue.js";
import store_options from "../store/index.js";
import { createStoreWrapper } from "../support/store-wrapper.spec-helper.js";

describe("FirstConfigurationSections", () => {
    let store_wrapper;
    let wrapper;

    beforeEach(() => {
        store_wrapper = createStoreWrapper(store_options, {
            current_tracker: {
                fields: createList("field", 2)
            }
        });
        wrapper = shallowMount(FirstConfigurationSections, {
            store: store_wrapper.store,
            localVue,
            propsData: { trackerId: 1 }
        });
    });

    describe("all_fields", () => {
        beforeEach(() => {
            store_wrapper.state.current_tracker.fields = [
                {
                    field_id: 1,
                    label: "First field",
                    type: "sb",
                    bindings: { type: "static" }
                },
                {
                    field_id: 2,
                    label: "Not 'sb' type field",
                    type: "column",
                    bindings: { type: "static" }
                },
                {
                    field_id: 3,
                    label: "Not 'static' binding field",
                    type: "sb",
                    bindings: { type: null }
                },
                {
                    field_id: 4,
                    label: "Fourth field",
                    type: "sb",
                    bindings: { type: "static" }
                }
            ];
        });

        it("returns only fields with 'sb' type", () => {
            expect(wrapper.vm.all_fields).not.toContain({
                id: 2,
                label: "Not 'sb' type field"
            });
        });
        it("returns only fields with 'static' binding type", () => {
            expect(wrapper.vm.all_fields).not.toContain({
                id: 3,
                label: "Not 'static' binding field"
            });
        });
        it("returns only field id and label", () => {
            expect(wrapper.vm.all_fields).toContain({
                id: 1,
                label: "First field"
            });
        });
    });
});
