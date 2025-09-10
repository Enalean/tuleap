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

import { describe, beforeEach, it, expect, jest } from "@jest/globals";
import { defineStore } from "pinia";
import { createTestingPinia } from "@pinia/testing";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../tests/helpers/global-options-for-tests";
import TimeTrackingOverviewUserList from "./TimeTrackingOverviewUserList.vue";

const user = {
    user_name: "user_1",
    user_id: 100,
};

describe("Given a timetracking overview widget", () => {
    let setSelectedUserId: jest.Mock;

    beforeEach(() => {
        setSelectedUserId = jest.fn();
    });

    const getWrapper = (): VueWrapper => {
        const useStore = defineStore("overview/1", {
            state: () => ({
                users: [user],
            }),
            actions: {
                setSelectedUserId,
            },
        });

        const pinia = createTestingPinia({ stubActions: false });
        useStore(pinia);

        return shallowMount(TimeTrackingOverviewUserList, {
            global: getGlobalTestOptions(pinia),
        });
    };

    it("When tracker total sum not equal zero, then table row is displayed", () => {
        const wrapper = getWrapper();
        const input = wrapper.find("[data-test=timetracking-overview-users-selector]");

        input.setValue(user.user_id);
        input.trigger("input");

        expect(setSelectedUserId).toHaveBeenCalledWith(user.user_id);
    });
});
