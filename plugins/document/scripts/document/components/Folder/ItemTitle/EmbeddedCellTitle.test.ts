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

import VueRouter from "vue-router";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import EmbeddedCellTitle from "./EmbeddedCellTitle.vue";
import localVue from "../../../helpers/local-vue";
import { TYPE_EMBEDDED } from "../../../constants";
import type { Embedded, Folder, RootState } from "../../../type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import * as route from "../../../helpers/use-router";
import type { Location, Route } from "vue-router/types/router";

describe("EmbeddedCellTitle", () => {
    function getWrapper(item: Embedded): Wrapper<EmbeddedCellTitle> {
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

        return shallowMount(EmbeddedCellTitle, {
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

    it(`Given embedded_file_properties is not set
        When we display item title
        Then we should display corrupted badge`, () => {
        const item = {
            id: 42,
            title: "my corrupted embedded document",
            embedded_file_properties: null,
            type: TYPE_EMBEDDED,
        } as Embedded;

        const wrapper = getWrapper(item);

        expect(wrapper.find(".document-badge-corrupted").exists()).toBeTruthy();
    });

    it(`Given embedded_file_properties is set
        When we display item title
        Then we should not display corrupted badge`, () => {
        const item = {
            id: 42,
            title: "my corrupted embedded document",
            embedded_file_properties: {
                file_type: "text/html",
                content: "<p>this is my custom embedded content</p>",
            },
            type: TYPE_EMBEDDED,
        } as Embedded;

        const wrapper = getWrapper(item);

        expect(wrapper.find(".document-badge-corrupted").exists()).toBeFalsy();
    });
});
