/*
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

import CommentNotEmptyCheckBox from "./CommentNotEmptyCheckBox.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests.js";

describe("CommentNotEmptyCheckBox", () => {
    function filledPreConditionsMockFactory(state) {
        return shallowMount(CommentNotEmptyCheckBox, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        transitionModal: {
                            namespaced: true,
                            state,
                            getters: {
                                is_transition_from_new_artifact: () => false,
                            },
                        },
                    },
                }),
            },
        });
    }

    describe("transition_comment_not_empty", () => {
        describe("when no current transition", () => {
            it("returns false", () => {
                const wrapper = filledPreConditionsMockFactory(null);
                expect(wrapper.get("[data-test=not-empty-comment-checkbox]").element.checked).toBe(
                    false,
                );
            });
        });

        describe("when current transition requires comment", () => {
            it("returns true", () => {
                const state = {
                    current_transition: {
                        is_comment_required: true,
                        authorized_user_group_ids: [],
                        not_empty_field_ids: [],
                    },
                };
                const wrapper = filledPreConditionsMockFactory(state);
                expect(wrapper.get("[data-test=not-empty-comment-checkbox]").element.checked).toBe(
                    true,
                );
            });
        });
    });

    describe(`when the modal is saving`, () => {
        let wrapper;
        beforeEach(() => {
            const state = {
                is_modal_save_running: true,
            };
            wrapper = filledPreConditionsMockFactory(state);
        });

        it(`will disable the "Not empty comment" checkbox`, () => {
            const not_empty_comment_checkbox = wrapper.get(
                "[data-test=not-empty-comment-checkbox]",
            );
            expect(not_empty_comment_checkbox.attributes("disabled")).toBe("");
        });
    });
});
