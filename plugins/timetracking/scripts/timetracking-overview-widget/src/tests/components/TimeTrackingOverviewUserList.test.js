/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
import TimeTrackingOverviewUserList from "../../components/TimeTrackingOverviewUserList.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { createLocalVueForTests } from "../helpers/local-vue.js";

describe("Given a timetracking overview widget", () => {
    let store_options, wrapper, input, store;
    beforeEach(async () => {
        store_options = {
            state: {
                users: [
                    {
                        user_name: "user_1",
                        user_id: 100,
                    },
                ],
            },
        };
        store = createStoreMock(store_options);
        const component_options = {
            localVue: await createLocalVueForTests(),
            mocks: { $store: store },
        };

        wrapper = shallowMount(TimeTrackingOverviewUserList, component_options);
    });

    it("When tracker total sum not equal zero, then table row is displayed", () => {
        input = wrapper.get("[data-test=timetracking-overview-users-selector]");
        input.setValue(100);
        input.trigger("input");

        expect(store.commit).toHaveBeenCalledWith("setSelectedUser", "100");
    });
});
