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
import { getGlobalTestOptions } from "../../support/global-options-for-tests";
import DeleteBaselineButton from "./DeleteBaselineButton.vue";
import ActionButton from "../common/ActionButton.vue";

describe("DeleteBaselineButton", () => {
    let show_modal_mock = jest.fn();

    function createWrapper(comparisons) {
        const baseline = { id: 1 };

        return shallowMount(DeleteBaselineButton, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        comparisons: {
                            namespaced: true,
                            state: {
                                ...comparisons,
                            },
                        },
                        dialog_interface: {
                            namespaced: true,
                            mutations: {
                                showModal: show_modal_mock,
                            },
                        },
                    },
                }),
                provide: { is_admin: true },
            },
            props: {
                baseline,
            },
        });
    }

    it("should display delete button as disabled while comparisons are loading", () => {
        const wrapper = createWrapper({ is_loading: true, comparisons: [] });

        expect(wrapper.findComponent(ActionButton).props("disabled")).toBe(true);
    });

    it.each([
        [[{ base_baseline_id: 1, compared_to_baseline_id: 2 }]],
        [[{ base_baseline_id: 2, compared_to_baseline_id: 1 }]],
    ])(
        "should display delete button as disabled if baseline is part of a comparison %s",
        (comparisons) => {
            const wrapper = createWrapper({
                is_loading: false,
                comparisons,
            });

            expect(wrapper.findComponent(ActionButton).props("disabled")).toBe(true);
        },
    );

    it.each([[[]], [[{ base_baseline_id: 2, compared_to_baseline_id: 3 }]]])(
        "should display delete button as enabled if baseline is not part of comparison %s",
        (comparisons) => {
            const wrapper = createWrapper({
                is_loading: false,
                comparisons,
            });

            expect(wrapper.findComponent(ActionButton).props("disabled")).toBe(false);
        },
    );

    it("shows modal on click", async () => {
        const wrapper = createWrapper({
            is_loading: false,
            comparisons: [],
        });

        await wrapper.trigger("click");

        expect(show_modal_mock).toHaveBeenCalled();
    });
});
