/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { REPOSITORIES_SORTED_BY_LAST_UPDATE, REPOSITORIES_SORTED_BY_PATH } from "../../constants";
import DisplayModeSwitcher from "./DisplayModeSwitcher.vue";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createLocalVueForTests } from "../../helpers/local-vue-for-tests";

describe("DisplayModeSwitcher", () => {
    async function createWrapper(display_mode: string): Promise<Wrapper<DisplayModeSwitcher>> {
        return shallowMount(DisplayModeSwitcher, {
            localVue: await createLocalVueForTests(),
            mocks: {
                $store: createStoreMock({
                    state: { current_display_mode: display_mode },
                    getters: { isLoading: false },
                }),
            },
        });
    }

    it("displays folders sorted by path", async () => {
        const wrapper = await createWrapper(REPOSITORIES_SORTED_BY_PATH);
        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays folders sorted by date", async () => {
        const wrapper = await createWrapper(REPOSITORIES_SORTED_BY_LAST_UPDATE);
        expect(wrapper.element).toMatchSnapshot();
    });
});
