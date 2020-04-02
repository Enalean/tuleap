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
import localVue from "../../../helpers/local-vue.js";
import { createStoreMock } from "../../../../../../../src/www/scripts/vue-components/store-wrapper-jest.js";
import NewDocument from "./NewDocument.vue";
import { TYPE_FOLDER } from "../../../constants.js";
import EventBus from "../../../helpers/event-bus.js";

describe("NewDocument", () => {
    let new_item, store;
    beforeEach(() => {
        store = createStoreMock({});
        new_item = (props = {}) => {
            return shallowMount(NewDocument, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`User can create add document to folder when he is docman writer`, () => {
        store.getters.is_item_a_folder = () => true;
        const item = {
            type: TYPE_FOLDER,
            user_can_write: true,
        };

        const wrapper = new_item({ item });
        expect(wrapper.find("[data-test=document-new-item]").exists()).toBeTruthy();
    });
    it(`User can NOT add document to folder when he is docman reader`, () => {
        store.getters.is_item_a_folder = () => true;
        const item = {
            type: TYPE_FOLDER,
            user_can_write: false,
        };

        const wrapper = new_item({ item });
        expect(wrapper.find("[data-test=document-new-item]").exists()).toBeFalsy();
    });
    it(`Click on new document open the corresponding modal`, () => {
        store.getters.is_item_a_folder = () => true;
        const item = {
            type: TYPE_FOLDER,
            user_can_write: true,
        };
        const event_bus_emit = jest.spyOn(EventBus, "$emit");

        const wrapper = new_item({ item });
        wrapper.get("[data-test=document-new-item]").trigger("click");
        expect(event_bus_emit).toHaveBeenCalledWith("show-new-document-modal", {
            detail: { parent: item },
        });
    });
});
