/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
import FileCellTitle from "./FileCellTitle.vue";
import { TYPE_FILE } from "../../../constants";
import type { FileProperties, Folder, ItemFile, RootState } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("FileCellTitle", () => {
    function getWrapper(item: ItemFile): VueWrapper<InstanceType<typeof FileCellTitle>> {
        return shallowMount(FileCellTitle, {
            props: { item },
            global: {
                ...getGlobalTestOptions({
                    state: {
                        current_folder: {
                            id: 1,
                            title: "My current folder",
                        } as Folder,
                    } as RootState,
                }),
            },
        });
    }

    it(`Given file_properties is not set
        When we display item title
        Then we should display corrupted badge`, () => {
        const item = {
            id: 42,
            title: "my corrupted embedded document",
            file_properties: null,
            type: TYPE_FILE,
        } as ItemFile;

        const wrapper = getWrapper(item);

        expect(wrapper.find(".document-badge-corrupted").exists()).toBeTruthy();
    });

    it(`Given file_properties is set
        When we display item title
        Then we should not display corrupted badge`, () => {
        const item = {
            id: 42,
            title: "my embedded document",
            file_properties: {
                file_name: "my file",
                file_type: "image/png",
                download_href: "/plugins/docman/download/119/42",
                open_href: null,
                file_size: 109768,
            } as FileProperties,
            type: TYPE_FILE,
        } as ItemFile;

        const wrapper = getWrapper(item);

        expect(wrapper.find(".document-badge-corrupted").exists()).toBeFalsy();
    });

    it(`Given file_properties is set
        When we display item title
        Then we should have a link to download the file`, () => {
        const item = {
            id: 42,
            title: "my embedded document",
            file_properties: {
                file_name: "my file",
                file_type: "image/png",
                download_href: "/plugins/docman/download/119/42",
                open_href: null,
                file_size: 109768,
            } as FileProperties,
            type: TYPE_FILE,
        } as ItemFile;

        const wrapper = getWrapper(item);

        expect(
            wrapper.find<HTMLAnchorElement>("[data-test=document-folder-subitem-link]").element
                .href,
        ).toContain("/plugins/docman/download/119/42");
    });

    it(`Given file_properties is set
        And item has an open_href
        When we display item title
        Then we should have a link to open the file`, () => {
        const item = {
            id: 42,
            title: "my embedded document",
            file_properties: {
                file_name: "my file",
                file_type: "image/png",
                download_href: "/plugins/docman/download/119/42",
                open_href: "/path/to/open/42",
                file_size: 109768,
            } as FileProperties,
            type: TYPE_FILE,
        } as ItemFile;

        const wrapper = getWrapper(item);

        expect(
            wrapper.find<HTMLAnchorElement>("[data-test=document-folder-subitem-link]").element
                .href,
        ).toContain("/path/to/open/42");
    });
});
