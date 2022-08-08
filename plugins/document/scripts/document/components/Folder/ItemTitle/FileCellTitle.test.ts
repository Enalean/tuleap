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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FileCellTitle from "./FileCellTitle.vue";
import localVue from "../../../helpers/local-vue";
import { TYPE_FILE } from "../../../constants";
import type { FileProperties, Folder, ItemFile, RootState } from "../../../type";
import VueRouter from "vue-router";
import type { Location, Route } from "vue-router/types/router";
import * as route from "../../../helpers/use-router";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("FileCellTitle", () => {
    function getWrapper(item: ItemFile): Wrapper<FileCellTitle> {
        const router = new VueRouter();
        jest.spyOn(router, "resolve").mockImplementation(() => ({
            location: {} as Location,
            route: {} as Route,
            href: "/patch/to/embedded",
            normalizedTo: {} as Location,
            resolved: {} as Route,
        }));
        const mocked_router = jest.spyOn(route, "useRouter");
        mocked_router.mockReturnValue(router);

        return shallowMount(FileCellTitle, {
            localVue,
            propsData: { item },
            mocks: {
                localVue,
                $store: createStoreMock({
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
            title: "my corrupted embedded document",
            file_properties: {
                file_name: "my file",
                file_type: "image/png",
                download_href: "/plugins/docman/download/119/42",
                file_size: 109768,
            } as FileProperties,
            type: TYPE_FILE,
        } as ItemFile;

        const wrapper = getWrapper(item);

        expect(wrapper.find(".document-badge-corrupted").exists()).toBeFalsy();
    });
});
