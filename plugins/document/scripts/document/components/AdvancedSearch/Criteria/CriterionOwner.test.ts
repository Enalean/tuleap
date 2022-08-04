/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
import CriterionOwner from "./CriterionOwner.vue";
import localVue from "../../../helpers/local-vue";
import * as autocomplete from "@tuleap/autocomplete-for-select2";
import * as retrieve_selected_owner from "../../../helpers/owner/retrieve-selected-owner";
import type { RestUser } from "../../../api/rest-querier";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

jest.mock("tlp");

describe("CriterionOwner", () => {
    it("should render the component with an already selected user", async () => {
        const autocompleter = jest.spyOn(autocomplete, "autocomplete_users_for_select2");
        let select2 = {};
        select2 = {
            trigger: (): Record<string, never> => select2,
            on: (): Record<string, never> => select2,
        };
        autocompleter.mockReturnValue(select2);

        const get_spy = jest.spyOn(retrieve_selected_owner, "retrieveSelectedOwner");
        get_spy.mockResolvedValue({ display_name: "John Doe", username: "jdoe" } as RestUser);

        const wrapper = shallowMount(CriterionOwner, {
            localVue,
            mocks: {
                $store: createStoreMock({
                    state: { configuration: { project_name: "test" } },
                }),
            },
            propsData: {
                criterion: {
                    name: "owner",
                    label: "Owner",
                },
                value: "jdoe",
            },
        });
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();
        expect(wrapper.element).toMatchSnapshot();
    });
});
