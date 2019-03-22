/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
import localVue from "../../support/local-vue.js";
import { createStoreMock } from "../../support/store-wrapper.spec-helper.js";
import store_options from "../../store/index.js";
import SemanticFieldLabel from "./SemanticFieldLabel.vue";
import { create } from "../../support/factories";

describe("SemanticFieldLabel", () => {
    const skeleton_selector = '[data-test-type="skeleton"]';

    let store;
    let wrapper;

    beforeEach(() => {
        store = createStoreMock(store_options);
        wrapper = shallowMount(SemanticFieldLabel, {
            propsData: {
                semantic: "description",
                tracker_id: 1
            },
            localVue,
            mocks: {
                $store: store
            }
        });
    });

    it("loads semantic fields on mount", () => {
        expect(store.dispatch).toHaveBeenCalledWith("loadSemanticFields", 1);
    });

    describe("with initial store", () => {
        beforeEach(() => {
            store.state = store_options.state;
        });

        it("shows only skeleton", () => {
            expect(wrapper.contains(skeleton_selector)).toBeTruthy();
            expect(wrapper.text()).toEqual("");
        });
    });

    describe("when semantic is loading", () => {
        beforeEach(() => {
            store.state.is_semantic_fields_by_tracker_id_loading[1] = true;
        });

        it("shows only skeleton", () => {
            expect(wrapper.contains(skeleton_selector)).toBeTruthy();
            expect(wrapper.text()).toEqual("");
        });
    });

    describe("when semantics are loaded", () => {
        beforeEach(() => {
            store.state.is_semantic_fields_by_tracker_id_loading = { 1: false };
        });

        describe("and a semantic exists", () => {
            beforeEach(() => {
                store.state.semantic_fields_by_tracker_id = {
                    1: {
                        description: create("field", {
                            label: "Status"
                        })
                    }
                };
            });

            it("shows only field label", () => {
                expect(wrapper.text()).toEqual("Status");
            });
        });

        describe("and no semantic exists", () => {
            beforeEach(() => {
                store.state.semantic_fields_by_tracker_id = {
                    1: {}
                };
            });

            it("shows only skeleton", () => {
                expect(wrapper.contains(skeleton_selector)).toBeTruthy();
                expect(wrapper.text()).toEqual("");
            });
        });
    });
});
