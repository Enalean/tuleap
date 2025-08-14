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
import SemanticFieldLabel from "./SemanticFieldLabel.vue";

describe("SemanticFieldLabel", () => {
    let load_tracker_id_mock = jest.fn();

    function createWrapper(is_field_label_available, field_label = "My description") {
        return shallowMount(SemanticFieldLabel, {
            props: {
                semantic: "description",
                tracker_id: 1,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        semantics: {
                            namespaced: true,
                            getters: {
                                field_label: () => () => field_label,
                                is_field_label_available: () => () => is_field_label_available,
                            },
                            actions: {
                                loadByTrackerId: load_tracker_id_mock,
                            },
                        },
                    },
                }),
            },
        });
    }

    it("loads semantic fields on mount", () => {
        createWrapper(true);
        expect(load_tracker_id_mock).toHaveBeenCalled();
    });

    it("when semantic is not available then it shows only skeleton", () => {
        const wrapper = createWrapper(false);
        expect(wrapper.find('[data-test-type="skeleton"]').exists()).toBeTruthy();

        expect(wrapper.text()).toBe("");
    });

    it("when semantic is available then it shows only field label", () => {
        const wrapper = createWrapper(true, "Status");
        expect(wrapper.text()).toBe("Status");
    });
});
