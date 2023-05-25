/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import FilledPreConditionsSection from "./FilledPreConditionsSection.vue";
import { createLocalVueForTests } from "../../support/local-vue.js";
import module_options from "../../store/transition-modal/module.js";
import { create } from "../../support/factories.js";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import * as list_picker from "@tuleap/list-picker";

describe("FilledPreConditionsSection", () => {
    let store;

    async function filledPreConditionsMockFactory(state_store) {
        let state = state_store;
        if (state_store === null) {
            state = module_options.state;
        }

        const { mutations, actions } = module_options;
        const store_options = {
            state: {
                current_tracker: null,
                transitionModal: state,
            },
            getters: {
                "transitionModal/is_transition_from_new_artifact": false,
            },
            mutations,
            actions,
        };
        store = createStoreMock(store_options);

        return shallowMount(FilledPreConditionsSection, {
            mocks: {
                $store: store,
            },
            localVue: await createLocalVueForTests(),
            sync: false, // Without this, store.reset() causes errors
        });
    }

    beforeEach(() => {
        jest.spyOn(list_picker, "createListPicker").mockImplementation();
    });

    afterEach(() => store.reset());

    describe("writable_fields", () => {
        describe("when no current tracker", () => {
            it("returns empty array", async () => {
                const wrapper = await filledPreConditionsMockFactory(null);
                expect(wrapper.vm.writable_fields).toHaveLength(0);
            });
        });

        describe("with a current tracker", () => {
            let wrapper;
            const valid_field = create("field", { type: "valid" });
            const invalid_field = create("field", { type: "burndown" });

            beforeEach(async () => {
                wrapper = await filledPreConditionsMockFactory(null);
                store.state.current_tracker = {
                    fields: [invalid_field, valid_field],
                };
            });
            it("returns valid fields", () => {
                expect(wrapper.vm.writable_fields).toContain(valid_field);
            });
            it("does not return invalid fields", () => {
                expect(wrapper.vm.writable_fields).not.toContain(invalid_field);
            });

            describe("which fields are not sorted", () => {
                beforeEach(() => {
                    store.state.current_tracker.fields = [
                        create("field", { type: "valid", label: "second" }),
                        create("field", { type: "valid", label: "First" }),
                        create("field", { type: "valid", label: "Third" }),
                    ];
                });
                it("returns fields sorted by natural order", () => {
                    expect(wrapper.vm.writable_fields.map((field) => field.label)).toStrictEqual([
                        "First",
                        "second",
                        "Third",
                    ]);
                });
            });
        });
    });

    describe("authorized_user_group_ids", () => {
        describe("when no current transition", () => {
            it("returns empty array", async () => {
                const wrapper = await filledPreConditionsMockFactory(null);
                expect(wrapper.vm.authorized_user_group_ids).toHaveLength(0);
            });
        });

        describe("with a current transition", () => {
            const authorized_user_group_ids = ["1", "2"];
            it("returns transition authorized group ids", async () => {
                const state = {
                    current_transition: {
                        not_empty_field_ids: [],
                        authorized_user_group_ids,
                    },
                };
                const wrapper = await filledPreConditionsMockFactory(state);
                expect(wrapper.vm.authorized_user_group_ids).toStrictEqual(
                    authorized_user_group_ids
                );
            });
        });
    });

    describe("not_empty_field_ids", () => {
        describe("when no current transition", () => {
            it("returns empty array", async () => {
                const wrapper = await filledPreConditionsMockFactory(null);
                expect(wrapper.vm.not_empty_field_ids).toHaveLength(0);
            });
        });

        describe("with a current transition", () => {
            const not_empty_field_ids = [1, 2];
            it("returns transition empty field ids", async () => {
                const state = {
                    current_transition: {
                        not_empty_field_ids,
                        authorized_user_group_ids: [],
                    },
                };
                const wrapper = await filledPreConditionsMockFactory(state);
                expect(wrapper.vm.$data.not_empty_field_ids).toStrictEqual(not_empty_field_ids);
            });
        });
    });

    describe("transition_comment_not_empty", () => {
        describe("when no current transition", () => {
            it("returns false", async () => {
                const wrapper = await filledPreConditionsMockFactory(null);
                expect(wrapper.vm.transition_comment_not_empty).toBeFalsy();
            });
        });

        describe("when current transition requires comment", () => {
            it("returns true", async () => {
                const state = {
                    current_transition: {
                        is_comment_required: true,
                        authorized_user_group_ids: [],
                        not_empty_field_ids: [],
                    },
                };
                const wrapper = await filledPreConditionsMockFactory(state);
                expect(wrapper.vm.transition_comment_not_empty).toBeTruthy();
            });
        });
    });

    describe(`when the modal is saving`, () => {
        let wrapper;
        beforeEach(async () => {
            const state = {
                is_modal_save_running: true,
            };
            wrapper = await filledPreConditionsMockFactory(state);
        });

        it(`will disable the "Authorized ugroups" selectbox`, () => {
            const authorized_ugroups_selectbox = wrapper.get(
                "[data-test=authorized-ugroups-select]"
            );
            expect(authorized_ugroups_selectbox.attributes("disabled")).toBeTruthy();
        });

        it(`will disable the "Not empty fields" select`, () => {
            const not_empty_field_select = wrapper.get("[data-test=not-empty-field-select]");
            expect(not_empty_field_select.attributes("disabled")).toBeTruthy();
        });

        it(`will disable the "Not empty comment" checkbox`, () => {
            const not_empty_comment_checkbox = wrapper.get(
                "[data-test=not-empty-comment-checkbox]"
            );
            expect(not_empty_comment_checkbox.attributes("disabled")).toBeTruthy();
        });
    });
});
