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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { TYPE_FILE, TYPE_FOLDER } from "../../../../../constants";
import type { Folder, Item, ItemFile, State, RootState } from "../../../../../type";
import TitleProperty from "./TitleProperty.vue";
import emitter from "../../../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-test";
import { nextTick } from "vue";

jest.mock("../../../../../helpers/emitter");

describe("TitleProperty", () => {
    let existing_folder_name: string, existing_document_name: string, updated_document_name: string;
    beforeEach(() => {
        existing_folder_name = "Existing folder";
        existing_document_name = "Existing file";
        updated_document_name = "my file";
    });

    function createWrapper(
        value: string,
        isInUpdateContext: boolean,
        parent: Folder,
        currentlyUpdatedItem: Item,
    ): VueWrapper<InstanceType<typeof TitleProperty>> {
        const state = {
            folder_content: [
                {
                    id: 2,
                    title: existing_folder_name,
                    type: TYPE_FOLDER,
                    parent_id: 3,
                } as Folder,
                {
                    id: 20,
                    title: existing_document_name,
                    type: TYPE_FILE,
                    parent_id: 3,
                } as ItemFile,
                {
                    id: 10,
                    title: updated_document_name,
                    type: TYPE_FILE,
                    parent_id: 3,
                } as ItemFile,
            ],
        } as unknown as State;

        return shallowMount(TitleProperty, {
            global: {
                ...getGlobalTestOptions({
                    state: state as RootState,
                }),
            },
            props: {
                value,
                isInUpdateContext,
                parent,
                currentlyUpdatedItem,
            },
        });
    }

    it(`Title can not be updated for root folder`, async () => {
        const value = "A new folder title";
        const isInUpdateContext = true;
        const parent = {} as Folder;
        const currentlyUpdatedItem = {
            type: TYPE_FOLDER,
            parent_id: 0,
        } as Folder;

        const wrapper = createWrapper(value, isInUpdateContext, parent, currentlyUpdatedItem);
        wrapper.setProps({ value: value });

        await nextTick();
        const input = wrapper.get("[data-test=document-new-item-title]");

        if (!(input.element instanceof HTMLInputElement)) {
            throw new Error("input element is not an html input");
        }
        expect(input.element.disabled).toBe(true);
        expect(wrapper.get("[data-test=document-new-item-title-form-element]").classes()).toContain(
            "tlp-form-element-disabled",
        );
    });

    it(`Title can be updated for other items`, async () => {
        const value = "A new folder title";
        const isInUpdateContext = true;
        const parent = {} as Folder;
        const currentlyUpdatedItem = {
            type: TYPE_FOLDER,
            parent_id: 3,
        } as Folder;

        const wrapper = createWrapper(value, isInUpdateContext, parent, currentlyUpdatedItem);
        wrapper.setProps({ value: value });

        await nextTick();
        const input = wrapper.get("[data-test=document-new-item-title]");

        if (!(input.element instanceof HTMLInputElement)) {
            throw new Error("input element is not an html input");
        }
        expect(input.element.disabled).toBe(false);
    });

    it(`When input is updated an event is sent`, async () => {
        const value = "A new folder title";
        const isInUpdateContext = true;
        const parent = {} as Folder;
        const currentlyUpdatedItem = {
            type: TYPE_FOLDER,
            parent_id: 3,
        } as Folder;

        const wrapper = createWrapper(value, isInUpdateContext, parent, currentlyUpdatedItem);
        wrapper.setProps({ value: value });

        await nextTick();
        const input = wrapper.get("[data-test=document-new-item-title]");

        if (!(input.element instanceof HTMLInputElement)) {
            throw new Error("input element is not an html input");
        }
        input.element.value = "My new title";

        input.trigger("input");

        expect(emitter.emit).toHaveBeenCalledWith("update-title-property", "My new title");
    });

    describe("Folder creation", () => {
        it(`Title is valid when no other folder has the same name`, async () => {
            const value = "A new folder title";
            const isInUpdateContext = false;
            const parent = {
                id: 3,
            } as Folder;
            const currentlyUpdatedItem = {
                type: TYPE_FOLDER,
            } as Folder;

            const wrapper = createWrapper(value, isInUpdateContext, parent, currentlyUpdatedItem);
            wrapper.setProps({ value: value });

            await nextTick();
            expect(wrapper.find("[data-test=title-error-message]").exists()).toBeFalsy();
        });

        it(`Error is rendered if folder title is already used`, async () => {
            const value = "";
            const isInUpdateContext = false;
            const parent = {
                id: 3,
            } as Folder;
            const currentlyUpdatedItem = {
                type: TYPE_FOLDER,
            } as Folder;

            const wrapper = createWrapper(value, isInUpdateContext, parent, currentlyUpdatedItem);
            wrapper.setProps({ value: existing_folder_name });

            await nextTick();

            expect(wrapper.find("[data-test=title-error-message]").exists()).toBeTruthy();
        });
    });

    describe("Document creation", () => {
        it(`Title is valid when not other folder has the same name`, async () => {
            const value = "A new document title";
            const isInUpdateContext = false;
            const parent = {
                id: 3,
            } as Folder;
            const currentlyUpdatedItem = {
                type: TYPE_FILE,
            } as ItemFile;

            const wrapper = createWrapper(value, isInUpdateContext, parent, currentlyUpdatedItem);
            wrapper.setProps({ value: value });

            await nextTick();
            expect(wrapper.find("[data-test=title-error-message]").exists()).toBeFalsy();
        });

        it(`Error is rendered if folder title is already used`, async () => {
            const value = "";
            const isInUpdateContext = false;
            const parent = {
                id: 3,
            } as Folder;
            const currentlyUpdatedItem = {
                type: TYPE_FILE,
            } as ItemFile;

            const wrapper = createWrapper(value, isInUpdateContext, parent, currentlyUpdatedItem);
            wrapper.setProps({ value: existing_document_name });

            await nextTick();
            expect(wrapper.find("[data-test=title-error-message]").exists()).toBeTruthy();
        });
    });

    describe("Document update", () => {
        it(`Title is valid when no other document has the same name`, async () => {
            const value = "old title";
            const isInUpdateContext = true;
            const parent = {
                id: 3,
            } as Folder;
            const currentlyUpdatedItem = {
                type: TYPE_FILE,
            } as ItemFile;

            const wrapper = createWrapper(value, isInUpdateContext, parent, currentlyUpdatedItem);
            wrapper.setProps({ value: "updated title" });

            await nextTick();
            expect(wrapper.find("[data-test=title-error-message]").exists()).toBeFalsy();
        });

        it(`Error is rendered if folder title is already used`, async () => {
            const value = updated_document_name;
            const isInUpdateContext = true;
            const parent = {
                id: 3,
            } as Folder;
            const currentlyUpdatedItem = {
                type: TYPE_FILE,
            } as ItemFile;

            const wrapper = createWrapper(value, isInUpdateContext, parent, currentlyUpdatedItem);
            wrapper.setProps({ value: existing_document_name });

            await nextTick();
            expect(wrapper.find("[data-test=title-error-message]").exists()).toBeTruthy();
        });
    });
});
