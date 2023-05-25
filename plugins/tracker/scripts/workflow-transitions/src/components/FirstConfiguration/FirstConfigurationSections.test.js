/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import FirstConfigurationSections from "./FirstConfigurationSections.vue";
import { createLocalVueForTests } from "../../support/local-vue.js";
import store_options from "../../store/index.js";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("FirstConfigurationSections", () => {
    let store;

    beforeEach(() => {
        store = createStoreMock(store_options);
    });

    const getWrapper = async () => {
        return shallowMount(FirstConfigurationSections, {
            mocks: {
                $store: store,
            },
            localVue: await createLocalVueForTests(),
        });
    };

    afterEach(() => store.reset());

    const create_workflow_selector = '[data-test="create-workflow"]';

    describe("When an operation is running", () => {
        beforeEach(() => {
            store.state.is_operation_running = true;
        });

        it("the submit button will be disabled", async () => {
            const wrapper = await getWrapper();
            const create_workflow_button = wrapper.get(create_workflow_selector);
            expect(create_workflow_button.attributes("disabled")).toBe("disabled");
        });
    });
});
