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
import localVue from "../../../helpers/local-vue.js";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";
import PasteItem from "./PasteItem.vue";
import {
    rewire as rewireEventBus,
    restore as restoreEventBus
} from "../../../helpers/event-bus.js";
import {
    rewire$doesFolderNameAlreadyExist,
    rewire$doesDocumentNameAlreadyExist,
    restore as restoreCheckItemTitle
} from "../../../helpers/metadata-helpers/check-item-title.js";
import {
    rewire$isItemDestinationIntoItself,
    restore as restoreClipboardHelpers
} from "../../../helpers/clipboard/clipboard-helpers.js";
import {
    TYPE_FOLDER,
    TYPE_EMPTY,
    CLIPBOARD_OPERATION_COPY,
    CLIPBOARD_OPERATION_CUT
} from "../../../constants.js";

describe("PasteItem", () => {
    let store, event_bus, paste_item_factory;
    beforeEach(() => {
        store = createStoreMock({}, { clipboard: {} });

        event_bus = jasmine.createSpyObj("event_bus", ["$emit"]);
        rewireEventBus(event_bus);

        paste_item_factory = (props = {}) => {
            return shallowMount(PasteItem, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store }
            });
        };
    });

    afterEach(() => {
        restoreEventBus();
        restoreCheckItemTitle();
        restoreClipboardHelpers();
    });

    it(`Given an item is in the clipboard
        And the inspected item is a folder the user can write
        Then item can be pasted`, () => {
        store.state.clipboard = { item_title: "My item", operation_type: CLIPBOARD_OPERATION_COPY };
        const current_folder = {};
        store.state.current_folder = current_folder;
        store.getters.is_item_a_folder = () => true;

        const destination = {
            user_can_write: true
        };
        const wrapper = paste_item_factory({ destination });

        expect(wrapper.text()).toContain("My item");

        wrapper.trigger("click");

        expect(store.dispatch).toHaveBeenCalledWith("clipboard/pasteItem", [
            destination,
            current_folder,
            store
        ]);
        expect(event_bus.$emit).toHaveBeenCalledWith("hide-action-menu");
    });

    it(`Given no item is in the clipboard
        Then no item can be pasted`, () => {
        store.state.clipboard = { item_title: null, operation_type: null };
        store.getters.is_item_a_folder = () => true;

        const wrapper = paste_item_factory({
            destination: {
                user_can_write: true
            }
        });

        expect(wrapper.html()).toBeFalsy();
    });

    it(`Given an item is in the clipboard
        And the inspected item is not a folder
        Then no item can be pasted`, () => {
        store.state.clipboard = { item_title: "My item", operation_type: CLIPBOARD_OPERATION_COPY };
        store.getters.is_item_a_folder = () => false;

        const wrapper = paste_item_factory({
            destination: {
                user_can_write: true
            }
        });

        expect(wrapper.html()).toBeFalsy();
    });

    it(`Given an item is in the clipboard
        And the inspected item is a folder the user can not write
        Then no item can be pasted`, () => {
        store.state.clipboard = { item_title: "My item", operation_type: CLIPBOARD_OPERATION_COPY };
        store.getters.is_item_a_folder = () => true;

        const wrapper = paste_item_factory({
            destination: {
                user_can_write: false
            }
        });

        expect(wrapper.html()).toBeFalsy();
    });

    it(`Given an item is being pasted
        Then the action is marked as disabled
        And the menu is not closed if the user tries to click on it`, () => {
        store.state.clipboard = {
            item_title: "My item",
            operation_type: CLIPBOARD_OPERATION_COPY,
            pasting_in_progress: true
        };
        store.getters.is_item_a_folder = () => true;

        const wrapper = paste_item_factory({
            destination: {
                user_can_write: true
            }
        });

        expect(wrapper.attributes().disabled).toBeTruthy();
        expect(wrapper.classes("tlp-dropdown-menu-item-disabled")).toBe(true);

        wrapper.trigger("click");

        expect(event_bus.$emit).not.toHaveBeenCalled();
    });

    it(`Given a document is in the clipboard to be moved
        And the inspected item is a folder containing a document with the same name
        Then the item can not be pasted`, () => {
        store.state.clipboard = {
            item_title: "My item",
            item_type: TYPE_EMPTY,
            operation_type: CLIPBOARD_OPERATION_CUT
        };
        store.state.folder_content = [];
        store.getters.is_item_a_folder = () => true;

        const doesDocumentNameAlreadyExist = jasmine.createSpy("doesDocumentNameAlreadyExist");
        rewire$doesDocumentNameAlreadyExist(doesDocumentNameAlreadyExist);

        doesDocumentNameAlreadyExist.and.returnValue(true);

        const wrapper = paste_item_factory({
            destination: {
                user_can_write: true
            }
        });

        expect(wrapper.html()).toBeFalsy();
    });

    it(`Given a folder is in the clipboard to be moved
        And the inspected item is a folder containing a folder with the same name
        Then the item can not be pasted`, () => {
        store.state.clipboard = {
            item_title: "My item",
            item_type: TYPE_FOLDER,
            operation_type: CLIPBOARD_OPERATION_CUT
        };
        store.state.folder_content = [];
        store.getters.is_item_a_folder = () => true;

        const doesFolderNameAlreadyExist = jasmine.createSpy("doesFolderNameAlreadyExist");
        rewire$doesFolderNameAlreadyExist(doesFolderNameAlreadyExist);

        doesFolderNameAlreadyExist.and.returnValue(true);

        const wrapper = paste_item_factory({
            destination: {
                user_can_write: true
            }
        });

        expect(wrapper.html()).toBeFalsy();
    });

    it(`Given a folder is in the clipboard to be moved
        And the inspected item is a subfolder
        Then the item can not be pasted`, () => {
        store.state.clipboard = {
            item_title: "My item",
            item_type: TYPE_FOLDER,
            operation_type: CLIPBOARD_OPERATION_CUT
        };
        store.state.folder_content = [];
        store.getters.is_item_a_folder = () => true;

        const doesFolderNameAlreadyExist = jasmine.createSpy("doesFolderNameAlreadyExist");
        rewire$doesFolderNameAlreadyExist(doesFolderNameAlreadyExist);
        doesFolderNameAlreadyExist.and.returnValue(false);

        const isItemDestinationIntoItself = jasmine.createSpy("isItemDestinationIntoItself");
        rewire$isItemDestinationIntoItself(isItemDestinationIntoItself);
        isItemDestinationIntoItself.and.returnValue(true);

        const wrapper = paste_item_factory({
            destination: {
                user_can_write: true
            }
        });

        expect(wrapper.html()).toBeFalsy();
    });
});
