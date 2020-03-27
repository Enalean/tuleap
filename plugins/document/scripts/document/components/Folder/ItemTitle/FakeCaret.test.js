/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import Vuex from "vuex";
import { createLocalVue, shallowMount } from "@vue/test-utils";
import FakeCaret from "./FakeCaret.vue";

const localVue = createLocalVue();
localVue.use(Vuex);

describe("FakeCaret", () => {
    const item = {
        id: 42,
        parent_id: 66,
        type: "wiki",
    };
    const component_options = {
        localVue,
        propsData: {
            item,
        },
    };

    it(`Given item has no siblings,
        when current folder is displayed,
        then fake caret of item is not displayed so that there isn't any unneeded whitespace`, () => {
        const store = new Vuex.Store({
            state: {
                current_folder: { id: 66 },
                folder_content: [item],
            },
        });
        const wrapper = shallowMount(FakeCaret, { store, ...component_options });

        expect(wrapper.contains(".fa-fw")).toBeFalsy();
    });

    it(`Given item has only documents as siblings,
        when current folder is displayed,
        then fake caret of item is not displayed so that there isn't any unneeded whitespace`, () => {
        const store = new Vuex.Store({
            state: {
                current_folder: { id: 66 },
                folder_content: [
                    { id: 43, parent_id: 66, type: "file" },
                    { id: 44, parent_id: 66, type: "link" },
                    { id: 45, parent_id: 66, type: "wiki" },
                    { id: 46, parent_id: 66, type: "empty" },
                    item,
                ],
            },
        });
        const wrapper = shallowMount(FakeCaret, { store, ...component_options });

        expect(wrapper.contains(".fa-fw")).toBeFalsy();
    });

    it(`Given item has at least one folder as sibling,
        when current folder is displayed,
        then fake caret of item is displayed for better alignment of icons`, () => {
        const store = new Vuex.Store({
            state: {
                current_folder: { id: 66 },
                folder_content: [
                    { id: 43, parent_id: 66, type: "file" },
                    { id: 44, parent_id: 66, type: "link" },
                    { id: 45, parent_id: 66, type: "folder" },
                    { id: 46, parent_id: 66, type: "empty" },
                    item,
                ],
            },
        });
        const wrapper = shallowMount(FakeCaret, { store, ...component_options });

        expect(wrapper.contains(".fa-fw")).toBeTruthy();
    });

    it(`Given item has no siblings,
        And item is in a subfolder,
        when current folder is displayed,
        then fake caret of item is displayed for better alignment of icons`, () => {
        const store = new Vuex.Store({
            state: {
                current_folder: { id: 111 },
                folder_content: [
                    { id: 43, parent_id: 111, type: "file" },
                    { id: 44, parent_id: 111, type: "link" },
                    { id: 66, parent_id: 111, type: "folder" },
                    { id: 46, parent_id: 111, type: "empty" },
                    item,
                ],
            },
        });
        const wrapper = shallowMount(FakeCaret, { store, ...component_options });

        expect(wrapper.contains(".fa-fw")).toBeTruthy();
    });

    it(`Given item has only documents as siblings,
        And item is in a subfolder,
        when current folder is displayed,
        then fake caret of item is displayed for better alignment of icons`, () => {
        const store = new Vuex.Store({
            state: {
                current_folder: { id: 111 },
                folder_content: [
                    { id: 43, parent_id: 66, type: "file" },
                    { id: 44, parent_id: 66, type: "link" },
                    { id: 66, parent_id: 111, type: "folder" },
                    { id: 46, parent_id: 66, type: "empty" },
                    item,
                ],
            },
        });
        const wrapper = shallowMount(FakeCaret, { store, ...component_options });

        expect(wrapper.contains(".fa-fw")).toBeTruthy();
    });

    it(`Given item has at least one folder as siblings,
        And item is in a subfolder,
        when current folder is displayed,
        then fake caret of item is displayed for better alignment of icons`, () => {
        const store = new Vuex.Store({
            state: {
                current_folder: { id: 111 },
                folder_content: [
                    { id: 43, parent_id: 66, type: "file" },
                    { id: 44, parent_id: 66, type: "link" },
                    { id: 66, parent_id: 111, type: "folder" },
                    { id: 46, parent_id: 66, type: "folder" },
                    item,
                ],
            },
        });
        const wrapper = shallowMount(FakeCaret, { store, ...component_options });

        expect(wrapper.contains(".fa-fw")).toBeTruthy();
    });
});
