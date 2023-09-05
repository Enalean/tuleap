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
 */

import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { createLocalVueForTests } from "../support/local-vue.js";
import ConfigureStateButton from "./ConfigureStateButton.vue";

describe(`ConfigureStateButton`, () => {
    let store;
    async function createWrapper(transition) {
        store = createStoreMock({});
        return shallowMount(ConfigureStateButton, {
            localVue: await createLocalVueForTests(),
            mocks: { $store: store },
            propsData: { transition },
        });
    }

    it(`When I click the button, it will dispatch an action to open the transitions configuration modal`, async () => {
        const transition = { id: 134 };
        const wrapper = await createWrapper(transition);
        wrapper.trigger("click");

        expect(store.dispatch).toHaveBeenCalledWith(
            "transitionModal/showTransitionConfigurationModal",
            transition,
        );
    });

    it(`when the transition is updated, it will show an animation`, async () => {
        const transition = { id: 144, updated: true };
        const wrapper = await createWrapper(transition);

        expect(wrapper.classes()).toContain("tlp-button-success");
    });
});
