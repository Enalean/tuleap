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
import ActionsHeader from "./ActionsHeader.vue";

import localVue from "../../../helpers/local-vue.js";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";

describe("QuickLookDocumentPreview", () => {
    let action_header_factory;
    beforeEach(() => {
        const store_options = {};

        const store = createStoreMock(store_options);

        action_header_factory = (props = {}) => {
            return shallowMount(ActionsHeader, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`Given user can write
        When he displays item actions
        Then the default action is Update`, () => {
        const wrapper = action_header_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: true,
            },
        });

        expect(
            wrapper.find("[data-test=item-action-create-new-version-button]").exists()
        ).toBeTruthy();
        expect(wrapper.find("[data-test=item-action-details-button]").exists()).toBeFalsy();
    });

    it(`Given user can read item
        When he displays item actions
        Then the default action is Details`, () => {
        const wrapper = action_header_factory({
            item: {
                id: 1,
                title: "my item title",
                type: "file",
                user_can_write: false,
            },
        });

        expect(
            wrapper.find("[data-test=item-action-create-new-version-button]").exists()
        ).toBeFalsy();
        expect(wrapper.find("[data-test=item-action-details-button]").exists()).toBeTruthy();
    });
});
