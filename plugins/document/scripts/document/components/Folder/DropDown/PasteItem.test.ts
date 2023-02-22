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

const emitMock = jest.fn();
jest.mock("../../../helpers/emitter", () => {
    return {
        emit: emitMock,
    };
});

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../helpers/local-vue";
import PasteItem from "./PasteItem.vue";
import * as check_item_title from "../../../helpers/properties-helpers/check-item-title";
import * as clipboard_helpers from "../../../helpers/clipboard/clipboard-helpers";
import {
    TYPE_FOLDER,
    TYPE_EMPTY,
    CLIPBOARD_OPERATION_COPY,
    CLIPBOARD_OPERATION_CUT,
} from "../../../constants";
import type { Folder, Item } from "../../../type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("PasteItem", () => {
    let store = {
        commit: jest.fn(),
        dispatch: jest.fn(),
    };

    const destination = {
        user_can_write: true,
        type: TYPE_FOLDER,
    } as Item;
    const current_folder = {} as Folder;

    function createWrapper(
        destination: Item,
        current_folder: Folder,
        operation_type: string | null,
        item_title: string | null,
        pasting_in_progress: boolean
    ): Wrapper<PasteItem> {
        store = createStoreMock({
            state: {
                clipboard: {
                    item_title,
                    operation_type,
                    pasting_in_progress,
                    item_type: TYPE_FOLDER,
                },
                current_folder,
                folder_content: [],
            },
        });
        return shallowMount(PasteItem, {
            mocks: {
                $store: store,
            },
            localVue: localVue,
            propsData: { destination },
        });
    }

    beforeEach(() => {
        emitMock.mockClear();
    });

    it(`Given an item is in the clipboard
        And the inspected item is a folder the user can write
        Then item can be pasted`, async () => {
        const wrapper = createWrapper(
            destination,
            current_folder,
            CLIPBOARD_OPERATION_COPY,
            "My item",
            false
        );

        expect(wrapper.text()).toContain("My item");

        wrapper.trigger("click");

        await wrapper.vm.$nextTick();

        expect(store.dispatch).toHaveBeenCalledWith("clipboard/pasteItem", {
            destination_folder: destination,
            current_folder,
            global_context: store,
        });
        expect(emitMock).toHaveBeenCalledWith("hide-action-menu");
    });

    it(`Given no item is in the clipboard
        Then no item can be pasted`, () => {
        const wrapper = createWrapper(destination, current_folder, null, null, true);

        expect(wrapper.html()).toBeFalsy();
    });

    it(`Given an item is in the clipboard
        And the inspected item is not a folder
        Then no item can be pasted`, () => {
        const destination = {
            user_can_write: true,
            type: TYPE_EMPTY,
        } as Item;

        const wrapper = createWrapper(
            destination,
            current_folder,
            CLIPBOARD_OPERATION_COPY,
            "My item",
            true
        );

        expect(wrapper.html()).toBeFalsy();
    });

    it(`Given an item is in the clipboard
        And the inspected item is a folder the user can not write
        Then no item can be pasted`, () => {
        const destination = {
            user_can_write: false,
            type: TYPE_FOLDER,
        } as Item;

        const wrapper = createWrapper(
            destination,
            current_folder,
            CLIPBOARD_OPERATION_COPY,
            "My item",
            true
        );

        expect(wrapper.html()).toBeFalsy();
    });

    it(`Given an item is being pasted
        Then the action is marked as disabled
        And the menu is not closed if the user tries to click on it`, () => {
        const wrapper = createWrapper(
            destination,
            current_folder,
            CLIPBOARD_OPERATION_COPY,
            "My item",
            true
        );

        expect(wrapper.attributes().disabled).toBeTruthy();
        expect(wrapper.classes("tlp-dropdown-menu-item-disabled")).toBe(true);

        wrapper.trigger("click");

        expect(emitMock).not.toHaveBeenCalled();
    });

    it(`Given a document is in the clipboard to be moved
        And the inspected item is a folder containing a document with the same name
        Then the item can not be pasted`, () => {
        jest.spyOn(check_item_title, "doesDocumentNameAlreadyExist").mockReturnValue(true);

        const wrapper = createWrapper(
            destination,
            current_folder,
            CLIPBOARD_OPERATION_CUT,
            "My item",
            true
        );

        expect(wrapper.html()).toBeFalsy();
    });

    it(`Given a folder is in the clipboard to be moved
        And the inspected item is a folder containing a folder with the same name
        Then the item can not be pasted`, () => {
        jest.spyOn(check_item_title, "doesFolderNameAlreadyExist").mockReturnValue(true);

        const wrapper = createWrapper(
            destination,
            current_folder,
            CLIPBOARD_OPERATION_CUT,
            "My item",
            true
        );

        expect(wrapper.html()).toBeFalsy();
    });

    it(`Given a folder is in the clipboard to be moved
        And the inspected item is a subfolder
        Then the item can not be pasted`, () => {
        jest.spyOn(check_item_title, "doesFolderNameAlreadyExist").mockReturnValue(false);
        jest.spyOn(clipboard_helpers, "isItemDestinationIntoItself").mockReturnValue(true);

        const wrapper = createWrapper(
            destination,
            current_folder,
            CLIPBOARD_OPERATION_CUT,
            "My item",
            true
        );

        expect(wrapper.html()).toBeFalsy();
    });
});
