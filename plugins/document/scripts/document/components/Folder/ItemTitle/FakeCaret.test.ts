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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FakeCaret from "./FakeCaret.vue";
import type { Empty, Folder, Item, ItemFile, Link, Wiki, RootState } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("FakeCaret", () => {
    const item = {
        id: 42,
        parent_id: 66,
        type: "wiki",
    } as Wiki;

    function getWrapper(state: RootState, item: Wiki): VueWrapper<InstanceType<typeof FakeCaret>> {
        const component_options = {
            props: {
                item,
            },
        };

        return shallowMount(FakeCaret, {
            global: {
                ...getGlobalTestOptions({
                    state: state as unknown as RootState,
                }),
            },
            ...component_options,
        });
    }

    it(`Given item has no siblings,
        when current folder is displayed,
        then fake caret of item is not displayed so that there isn't any unneeded whitespace`, () => {
        const folder_content: Array<Item> = [item];
        const state = {
            current_folder: { id: 66 } as Folder,
            folder_content,
        } as RootState;
        const wrapper = getWrapper(state, item);

        expect(wrapper.find(".fa-fw").exists()).toBeFalsy();

        expect(wrapper.find(".fa-fw").exists()).toBeFalsy();
    });

    it(`Given item has only documents as siblings,
        when current folder is displayed,
        then fake caret of item is not displayed so that there isn't any unneeded whitespace`, () => {
        const folder_content: Array<Item> = [
            { id: 43, parent_id: 66, type: "file" } as ItemFile,
            { id: 44, parent_id: 66, type: "link" } as Link,
            { id: 45, parent_id: 66, type: "wiki" } as Wiki,
            { id: 46, parent_id: 66, type: "empty" } as Empty,
            item,
        ];
        const state = {
            current_folder: { id: 66 } as Folder,
            folder_content,
        } as RootState;
        const wrapper = getWrapper(state, item);

        expect(wrapper.find(".fa-fw").exists()).toBeFalsy();
    });

    it(`Given item has at least one folder as sibling,
        when current folder is displayed,
        then fake caret of item is displayed for better alignment of icons`, () => {
        const folder_content: Array<Item> = [
            { id: 43, parent_id: 66, type: "file" } as ItemFile,
            { id: 44, parent_id: 66, type: "link" } as Link,
            { id: 45, parent_id: 66, type: "folder" } as Folder,
            { id: 46, parent_id: 66, type: "empty" } as Empty,
            item,
        ];
        const state = {
            current_folder: { id: 66 } as Folder,
            folder_content,
        } as RootState;
        const wrapper = getWrapper(state, item);

        expect(wrapper.find(".fa-fw").exists()).toBeTruthy();
    });

    it(`Given item has no siblings,
        And item is in a subfolder,
        when current folder is displayed,
        then fake caret of item is displayed for better alignment of icons`, () => {
        const folder_content: Array<Item> = [
            { id: 43, parent_id: 111, type: "file" } as ItemFile,
            { id: 44, parent_id: 111, type: "link" } as Link,
            { id: 66, parent_id: 111, type: "folder" } as Folder,
            { id: 46, parent_id: 111, type: "empty" } as Empty,
            item,
        ];
        const state = {
            current_folder: { id: 111 } as Folder,
            folder_content,
        } as RootState;
        const wrapper = getWrapper(state, item);

        expect(wrapper.find(".fa-fw").exists()).toBeTruthy();
    });

    it(`Given item has only documents as siblings,
        And item is in a subfolder,
        when current folder is displayed,
        then fake caret of item is displayed for better alignment of icons`, () => {
        const folder_content: Array<Item> = [
            { id: 43, parent_id: 66, type: "file" } as ItemFile,
            { id: 44, parent_id: 66, type: "link" } as Link,
            { id: 66, parent_id: 111, type: "folder" } as Folder,
            { id: 46, parent_id: 66, type: "empty" } as Empty,
            item,
        ];
        const state = {
            current_folder: { id: 111 } as Folder,
            folder_content,
        } as RootState;
        const wrapper = getWrapper(state, item);

        expect(wrapper.find(".fa-fw").exists()).toBeTruthy();
    });

    it(`Given item has at least one folder as siblings,
        And item is in a subfolder,
        when current folder is displayed,
        then fake caret of item is displayed for better alignment of icons`, () => {
        const folder_content: Array<Item> = [
            { id: 43, parent_id: 66, type: "file" } as ItemFile,
            { id: 44, parent_id: 66, type: "link" } as Link,
            { id: 66, parent_id: 111, type: "folder" } as Folder,
            { id: 46, parent_id: 66, type: "folder" } as Folder,
            item,
        ];
        const state = {
            current_folder: { id: 111 } as Folder,
            folder_content,
        } as RootState;
        const wrapper = getWrapper(state, item);

        expect(wrapper.find(".fa-fw").exists()).toBeTruthy();
    });
});
