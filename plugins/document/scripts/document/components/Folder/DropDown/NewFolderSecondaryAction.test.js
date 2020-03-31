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
import NewFolderSecondaryAction from "./NewFolderSecondaryAction.vue";
import { TYPE_FOLDER } from "../../../constants.js";
import EventBus from "../../../helpers/event-bus.js";

describe("NewFolderSecondaryAction", () => {
    let document_action_button_factory, store;
    beforeEach(() => {
        store = createStoreMock({});
        store.getters.is_item_a_folder = () => true;
        document_action_button_factory = (props = {}) => {
            return shallowMount(NewFolderSecondaryAction, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`User can create a folder when he is docman writer`, () => {
        const item = { type: TYPE_FOLDER, user_can_write: true };

        const wrapper = document_action_button_factory({ item });
        expect(
            wrapper.find("[data-test=document-new-folder-creation-button]").exists()
        ).toBeTruthy();
    });
    it(`User can NOT create a folder when he is docman reader`, () => {
        const item = { type: TYPE_FOLDER, user_can_write: false };

        const wrapper = document_action_button_factory({ item });
        expect(
            wrapper.find("[data-test=document-new-folder-creation-button]").exists()
        ).toBeFalsy();
    });
    it(`Click on folder open the corresponding modal`, () => {
        const item = { type: TYPE_FOLDER, user_can_write: true };
        const event_bus_emit = jest.spyOn(EventBus, "$emit");
        const wrapper = document_action_button_factory({ item });

        wrapper.get("[data-test=document-new-folder-creation-button]").trigger("click");
        expect(event_bus_emit).toHaveBeenCalledWith("show-new-folder-modal", {
            detail: { parent: item },
        });
    });
});
