/*
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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
import QuickLookItemIsLockedMessage from "./QuickLookItemIsLockedMessage.vue";
import localVue from "../../helpers/local-vue";
import Vuex from "vuex";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { TYPE_FILE } from "../../constants";
import type { Item } from "../../type";
import type { User } from "../../type";

localVue.use(Vuex);

describe("QuickLookItemIsLockedMessage", () => {
    it("renders locked message for document", () => {
        const item = {
            type: TYPE_FILE,
            lock_info: {
                lock_by: { id: 1 } as User,
                lock_date: "2019-04-25T16:32:59+02:00",
            } as LockInfo,
        } as Item;

        const wrapper = shallowMount(QuickLookItemIsLockedMessage, {
            localVue,
            mocks: {
                $store: createStoreMock({
                    state: {
                        currently_previewed_item: item,
                    },
                }),
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
