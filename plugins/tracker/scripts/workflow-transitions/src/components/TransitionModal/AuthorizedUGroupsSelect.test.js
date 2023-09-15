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

import AuthorizedUGroupsSelect from "./AuthorizedUGroupsSelect.vue";
import * as list_picker from "@tuleap/list-picker";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests.js";

describe("AuthorizedUGroupsSelect", () => {
    function getWrapper(current_transition, is_modal_save_running) {
        return shallowMount(AuthorizedUGroupsSelect, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        transitionModal: {
                            namespaced: true,
                            state: {
                                user_groups: [],
                                current_transition,
                                is_modal_save_running,
                            },
                        },
                    },
                }),
            },
        });
    }

    beforeEach(() => {
        jest.spyOn(list_picker, "createListPicker").mockImplementation();
    });

    describe("authorized_user_group_ids", () => {
        describe("when no current transition", () => {
            it("returns empty array", () => {
                const wrapper = getWrapper(null, false);
                const all_options = wrapper
                    .get("[data-test=authorized-ugroups-select]")
                    .findAll("option");
                expect(all_options).toHaveLength(0);
            });
        });

        describe("with a current transition", () => {
            const authorized_user_group_ids = ["1", "2"];
            it("returns transition authorized group ids", () => {
                const current_transition = {
                    not_empty_field_ids: [],
                    authorized_user_group_ids,
                };
                const wrapper = getWrapper(current_transition, false);
                expect(wrapper.vm.authorized_user_group_ids).toStrictEqual(
                    authorized_user_group_ids,
                );
            });
        });
    });

    describe(`when the modal is saving`, () => {
        it(`will disable the "Authorized ugroups" selectbox`, () => {
            const current_transition = {
                not_empty_field_ids: [],
                authorized_user_group_ids: [],
            };
            const wrapper = getWrapper(current_transition, true);
            const authorized_ugroups_selectbox = wrapper.get(
                "[data-test=authorized-ugroups-select]",
            );
            expect(authorized_ugroups_selectbox.attributes("disabled")).toBe("");
        });
    });
});
