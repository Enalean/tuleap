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
import localVue from "../../support/local-vue.js";
import module_options from "../../store/transition-modal/module.js";
import { create } from "../../support/factories.js";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest";

describe("FilledPreConditionsSection", () => {
    let store;
    let wrapper;

    beforeEach(() => {
        const { state, mutations, actions } = module_options;
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

        wrapper = shallowMount(FilledPreConditionsSection, {
            mocks: {
                $store: store,
            },
            localVue,
            sync: false, // Without this, store.reset() causes errors
        });
    });

    afterEach(() => store.reset());

    describe("writable_fields", () => {
        describe("when no current tracker", () => {
            beforeEach(() => {
                store.state.current_tracker = null;
            });
            it("returns empty array", () => {
                expect(wrapper.vm.writable_fields).toEqual([]);
            });
        });

        describe("with a current tracker", () => {
            const valid_field = create("field", { type: "valid" });
            const invalid_field = create("field", { type: "burndown" });

            beforeEach(() => {
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
                    expect(wrapper.vm.writable_fields.map((field) => field.label)).toEqual([
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
            beforeEach(() => {
                store.state.transitionModal.current_transition = null;
            });
            it("returns empty array", () => {
                expect(wrapper.vm.authorized_user_group_ids).toEqual([]);
            });
        });

        describe("with a current transition", () => {
            const authorized_user_group_ids = ["1", "2"];
            beforeEach(() => {
                store.state.transitionModal.current_transition = {
                    authorized_user_group_ids,
                };
            });
            it("returns transition authorized group ids", () => {
                expect(wrapper.vm.authorized_user_group_ids).toBe(authorized_user_group_ids);
            });
        });
    });

    describe("not_empty_field_ids", () => {
        describe("when no current transition", () => {
            beforeEach(() => {
                store.state.transitionModal.current_transition = null;
            });
            it("returns empty array", () => {
                expect(wrapper.vm.not_empty_field_ids).toEqual([]);
            });
        });

        describe("with a current transition", () => {
            const not_empty_field_ids = [1, 2];
            beforeEach(() => {
                store.state.transitionModal.current_transition = {
                    not_empty_field_ids,
                    authorized_user_group_ids: [],
                };
            });
            it("returns transition empty field ids", () => {
                expect(wrapper.vm.not_empty_field_ids).toBe(not_empty_field_ids);
            });
        });
    });

    describe("transition_comment_not_empty", () => {
        describe("when no current transition", () => {
            beforeEach(() => {
                store.state.transitionModal.current_transition = null;
            });
            it("returns false", () => {
                expect(wrapper.vm.transition_comment_not_empty).toBeFalsy();
            });
        });

        describe("when current transition requires comment", () => {
            beforeEach(() => {
                store.state.transitionModal.current_transition = {
                    is_comment_required: true,
                    authorized_user_group_ids: [],
                };
            });
            it("returns true", () => {
                expect(wrapper.vm.transition_comment_not_empty).toBeTruthy();
            });
        });
    });

    describe(`when the modal is saving`, () => {
        beforeEach(() => {
            store.state.transitionModal.is_modal_save_running = true;
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
