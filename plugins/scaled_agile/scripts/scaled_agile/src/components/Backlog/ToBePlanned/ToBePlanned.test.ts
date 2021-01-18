/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import ToBePlanned from "./ToBePlanned.vue";
import * as retriever from "../../../helpers/ToBePlanned/element-to-plan-retriever";
import * as configuration from "../../../configuration";
import { ToBePlannedElement } from "../../../helpers/ToBePlanned/element-to-plan-retriever";
import { createScaledAgileLocalVue } from "../../../helpers/local-vue-for-test";
import { DefaultData } from "vue/types/options";

describe("ToBePlanned", () => {
    it("Displays the empty state when no artifact are found", async () => {
        jest.spyOn(retriever, "getToBePlannedElements").mockResolvedValue([]);
        jest.spyOn(configuration, "programId").mockImplementation(() => 202);

        const wrapper = shallowMount(ToBePlanned, {
            localVue: await createScaledAgileLocalVue(),
            data(): DefaultData<ToBePlanned> {
                return {
                    to_be_planned_elements: [],
                    is_loading: false,
                    has_error: false,
                };
            },
        });

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
    });

    it("Displays an error when rest route fail", async () => {
        jest.spyOn(retriever, "getToBePlannedElements").mockResolvedValue([]);
        jest.spyOn(configuration, "programId").mockImplementation(() => 202);
        const wrapper = shallowMount(ToBePlanned, {
            localVue: await createScaledAgileLocalVue(),
            data(): DefaultData<ToBePlanned> {
                return {
                    to_be_planned_elements: [],
                    is_loading: false,
                    has_error: true,
                    error_message: "Oups, something happened",
                };
            },
        });

        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(true);
    });

    it("Displays the elements to be planned", async () => {
        const element_one = {
            artifact_id: 1,
            artifact_title: "My artifact",
            tracker: {
                label: "bug",
            },
        } as ToBePlannedElement;
        const element_two = {
            artifact_id: 2,
            artifact_title: "My user story",
            tracker: {
                label: "user_stories",
            },
        } as ToBePlannedElement;

        jest.spyOn(retriever, "getToBePlannedElements").mockResolvedValue([
            element_one,
            element_two,
        ]);
        jest.spyOn(configuration, "programId").mockImplementation(() => 202);

        const wrapper = shallowMount(ToBePlanned, {
            localVue: await createScaledAgileLocalVue(),
            data(): DefaultData<ToBePlanned> {
                return {
                    to_be_planned_elements: [element_one, element_two],
                    is_loading: false,
                    has_error: true,
                    error_message: "Oups, something happened",
                };
            },
        });

        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(true);
    });
});
