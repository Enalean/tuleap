/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FieldTrackerTemplateId from "./FieldTrackerTemplateId.vue";
import type { State } from "../../../../store/type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("FieldTrackerTemplateId", () => {
    function getWrapper(
        is_a_duplication: boolean,
        is_created_from_default_template: boolean,
    ): Wrapper<FieldTrackerTemplateId> {
        return shallowMount(FieldTrackerTemplateId, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        selected_tracker_template: {
                            id: "100",
                            name: "bugs",
                        },
                        selected_project_tracker_template: {
                            id: "101",
                            name: "Kanban",
                        },
                    } as State,
                    getters: {
                        is_a_duplication,
                        is_created_from_default_template,
                    },
                }),
            },
        });
    }

    it("If it is a duplication, then it sets the input value with the selected tracker id", () => {
        const wrapper = getWrapper(true, false);
        const input: HTMLInputElement = wrapper.element as HTMLInputElement;

        expect(input.value).toBe("100");
    });

    it("sets the input value with the selected project tracker id", () => {
        const wrapper = getWrapper(false, false);
        const input: HTMLInputElement = wrapper.element as HTMLInputElement;

        expect(input.value).toBe("101");
    });

    it("If it is from default template, then it sets the input value with the selected tracker id", () => {
        const wrapper = getWrapper(false, true);
        const input: HTMLInputElement = wrapper.element as HTMLInputElement;

        expect(input.value).toBe("100");
    });
});
