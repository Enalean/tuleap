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

import Vuex from "vuex";
import GettextPlugin from "vue-gettext";
import { createLocalVue, shallowMount } from "@vue/test-utils";
import PreConditionsSection from "./PreConditionsSection.vue";
import initial_global_state from "../../store/state.js";
import { state as initial_transition_modal_state } from "../../store/transition-modal/module.js";

const localVue = createLocalVue();
localVue.use(Vuex);
localVue.use(GettextPlugin, {
    translations: {},
    silent: true
});

describe("PreConditionsSection", () => {
    let global_store_state;
    let transition_modal_state;
    let wrapper;

    beforeEach(() => {
        global_store_state = {
            ...initial_global_state,
            current_tracker: null
        };
        transition_modal_state = {
            ...initial_transition_modal_state,
            current_transition: null,
            user_groups: null,
            is_loading_modal: null
        };

        const global_store = new Vuex.Store({
            state: global_store_state,
            modules: {
                transitionModal: {
                    namespaced: true,
                    state: transition_modal_state,
                    getters: {
                        is_transition_from_new_artifact: jasmine.createSpy(
                            "is_transition_from_new_artifact"
                        )
                    }
                }
            }
        });
        wrapper = shallowMount(PreConditionsSection, {
            store: global_store,
            localVue
        });
    });

    describe("writable_fields", () => {
        describe("when no current tracker", () => {
            beforeEach(() => {
                global_store_state.current_tracker = null;
            });
            it("returns empty array", () => {
                expect(wrapper.vm.writable_fields).toEqual([]);
            });
        });

        describe("with a current tracker", () => {
            const valid_field = { type: "valid" };
            const invalid_field = { type: "burndown" };

            beforeEach(() => {
                global_store_state.current_tracker = {
                    fields: [invalid_field, valid_field]
                };
            });
            it("returns valid fields", () => {
                expect(wrapper.vm.writable_fields).toContain(valid_field);
            });
            it("does not return invalid fields", () => {
                expect(wrapper.vm.writable_fields).not.toContain(invalid_field);
            });
        });
    });

    describe("authorized_user_group_ids", () => {
        describe("when no current transition", () => {
            beforeEach(() => {
                transition_modal_state.current_transition = null;
            });
            it("returns empty array", () => {
                expect(wrapper.vm.authorized_user_group_ids).toEqual([]);
            });
        });

        describe("with a current transition", () => {
            const authorized_user_group_ids = ["1", "2"];
            beforeEach(() => {
                transition_modal_state.current_transition = { authorized_user_group_ids };
            });
            it("returns transition authorized group ids", () => {
                expect(wrapper.vm.authorized_user_group_ids).toBe(authorized_user_group_ids);
            });
        });
    });

    describe("not_empty_field_ids", () => {
        describe("when no current transition", () => {
            beforeEach(() => {
                transition_modal_state.current_transition = null;
            });
            it("returns empty array", () => {
                expect(wrapper.vm.not_empty_field_ids).toEqual([]);
            });
        });

        describe("with a current transition", () => {
            const not_empty_field_ids = [1, 2];
            beforeEach(() => {
                transition_modal_state.current_transition = { not_empty_field_ids };
            });
            it("returns transition empty field ids", () => {
                expect(wrapper.vm.not_empty_field_ids).toBe(not_empty_field_ids);
            });
        });
    });

    describe("transition_comment_not_empty", () => {
        describe("when no current transition", () => {
            beforeEach(() => {
                transition_modal_state.current_transition = null;
            });
            it("returns false", () => {
                expect(wrapper.vm.transition_comment_not_empty).toBeFalsy();
            });
        });

        describe("when current transition requires comment", () => {
            beforeEach(() => {
                transition_modal_state.current_transition = { is_comment_required: true };
            });
            it("returns true", () => {
                expect(wrapper.vm.transition_comment_not_empty).toBeTruthy();
            });
        });
    });
});
