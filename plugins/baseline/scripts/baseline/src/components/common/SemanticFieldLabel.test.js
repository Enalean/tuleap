/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import { createLocalVueForTests } from "../../support/local-vue.ts";
import { createStoreMock } from "../../support/store-wrapper.test-helper.js";
import SemanticFieldLabel from "./SemanticFieldLabel.vue";
import store_options from "../../store/store_options";

describe("SemanticFieldLabel", () => {
    let $store;
    let wrapper;

    beforeEach(async () => {
        $store = createStoreMock({
            ...store_options,
            getters: {
                "semantics/field_label": () => "My description",
                "semantics/is_field_label_available": () => true,
            },
        });
        wrapper = shallowMount(SemanticFieldLabel, {
            propsData: {
                semantic: "description",
                tracker_id: 1,
            },
            localVue: await createLocalVueForTests(),
            mocks: {
                $store,
            },
        });
    });

    it("loads semantic fields on mount", () => {
        expect($store.dispatch).toHaveBeenCalledWith("semantics/loadByTrackerId", 1);
    });

    describe("when semantic is not available", () => {
        beforeEach(() => {
            $store.getters["semantics/is_field_label_available"] = () => false;
        });

        it("shows only skeleton", () => {
            expect(wrapper.find('[data-test-type="skeleton"]').exists()).toBeTruthy();

            expect(wrapper.text()).toBe("");
        });
    });

    describe("when semantic is available", () => {
        beforeEach(() => {
            $store.getters["semantics/is_field_label_available"] = () => true;
            $store.getters["semantics/field_label"] = () => "Status";
        });

        it("shows only field label", () => {
            expect(wrapper.text()).toBe("Status");
        });
    });
});
