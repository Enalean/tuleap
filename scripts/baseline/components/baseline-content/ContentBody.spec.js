/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
import localVue from "../../support/local-vue.js";
import { createStoreMock } from "../../support/store-wrapper.spec-helper.js";
import store_options from "../../store/store_options";
import { restore } from "../../api/rest-querier";
import ContentBody from "./ContentBody.vue";

describe("ContentBody", () => {
    let wrapper;

    const information_message_selector = '[data-test-type="information-message"]';

    beforeEach(() => {
        wrapper = shallowMount(ContentBody, {
            propsData: {
                first_level_artifacts: []
            },
            localVue,
            mocks: {
                $store: createStoreMock(store_options)
            }
        });
    });

    afterEach(restore);

    it("shows information message", () => {
        expect(wrapper.contains(information_message_selector)).toBeTruthy();
    });
});
