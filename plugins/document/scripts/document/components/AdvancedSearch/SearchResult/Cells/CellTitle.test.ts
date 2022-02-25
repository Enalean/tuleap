/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { FileProperties, ItemSearchResult } from "../../../../type";
import { shallowMount } from "@vue/test-utils";
import CellTitle from "./CellTitle.vue";
import localVue from "../../../../helpers/local-vue";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import type { ConfigurationState } from "../../../../store/configuration";

describe("CellTitle", () => {
    it("should output a link for File", () => {
        const wrapper = shallowMount(CellTitle, {
            localVue,
            propsData: {
                item: {
                    id: 123,
                    type: "file",
                    title: "Lorem",
                    file_properties: {
                        file_type: "text/html",
                        download_href: "/path/to/file",
                    } as FileProperties,
                } as unknown as ItemSearchResult,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            project_id: 101,
                        } as unknown as ConfigurationState,
                    },
                }),
            },
        });

        const link = wrapper.find("[data-test=link]");
        expect(link.attributes().href).toBe("/path/to/file");
    });

    it("should output a link for Wiki", () => {
        const wrapper = shallowMount(CellTitle, {
            localVue,
            propsData: {
                item: {
                    id: 123,
                    type: "wiki",
                    title: "Lorem",
                } as unknown as ItemSearchResult,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            project_id: 101,
                        } as unknown as ConfigurationState,
                    },
                }),
            },
        });

        const link = wrapper.find("[data-test=link]");
        expect(link.attributes().href).toBe("/plugins/docman/?group_id=101&action=show&id=123");
    });

    it("should output a route link for Embedded", () => {
        const wrapper = shallowMount(CellTitle, {
            localVue,
            propsData: {
                item: {
                    id: 123,
                    type: "embedded",
                    title: "Lorem",
                    parents: [
                        {
                            id: 120,
                            title: "Path",
                        },
                        {
                            id: 121,
                            title: "To",
                        },
                        {
                            id: 122,
                            title: "Folder",
                        },
                    ],
                } as unknown as ItemSearchResult,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            project_id: 101,
                        } as unknown as ConfigurationState,
                    },
                }),
            },
        });

        const link = wrapper.find("[data-test=router-link]");
        expect(link.props().to).toStrictEqual({
            name: "item",
            params: {
                folder_id: "120",
                item_id: "123",
            },
        });
    });
});
