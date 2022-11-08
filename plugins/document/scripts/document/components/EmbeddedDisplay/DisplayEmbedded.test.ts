/*
 * Copyright (c) Enalean 2019 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

import { okAsync } from "neverthrow";

const getEmbeddedFileVersionContent = jest.fn();
jest.mock("../../api/version-rest-querier", () => {
    return {
        getEmbeddedFileVersionContent,
    };
});
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../helpers/local-vue";
import DisplayEmbedded from "./DisplayEmbedded.vue";
import DisplayEmbeddedContent from "./DisplayEmbeddedContent.vue";
import DisplayEmbeddedSpinner from "./DisplayEmbeddedSpinner.vue";

describe("DisplayEmbedded", () => {
    let store = {
        dispatch: jest.fn(),
        commit: jest.fn(),
    };

    it(`Given user display an embedded file content
        When backend throw a permission error
        Then no spinner is displayed and component is not rendered`, async () => {
        const store_options = {
            state: {
                error: {
                    has_document_permission_error: true,
                    has_document_loading_error: false,
                },
            },
            getters: {
                "error/does_document_have_any_error": true,
            },
        };
        store = createStoreMock(store_options);

        const wrapper = shallowMount(DisplayEmbedded, {
            localVue,
            propsData: {
                item_id: 42,
            },
            mocks: { $store: store },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(DisplayEmbeddedContent).exists()).toBeFalsy();
        expect(wrapper.findComponent(DisplayEmbeddedSpinner).exists()).toBeFalsy();
    });

    it(`Given user display an embedded file content
        When backend throw a loading error
        Then no spinner is displayed and component is not rendered`, async () => {
        const store_options = {
            state: {
                error: {
                    has_document_permission_error: false,
                    has_document_loading_error: true,
                },
            },
            getters: {
                "error/does_document_have_any_error": true,
            },
        };
        store = createStoreMock(store_options);

        const wrapper = shallowMount(DisplayEmbedded, {
            localVue,
            propsData: {
                item_id: 42,
            },
            mocks: { $store: store },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(DisplayEmbeddedContent).exists()).toBeFalsy();
        expect(wrapper.findComponent(DisplayEmbeddedSpinner).exists()).toBeFalsy();
    });

    it(`Given user display an embedded file content
        When component is rendered
        Backend load the embedded file content`, async () => {
        const store_options = {
            state: {
                error: {},
            },
            getters: {
                "error/does_document_have_any_error": false,
            },
        };

        store = createStoreMock(store_options);

        store.dispatch.mockImplementation((action_name) => {
            if (action_name === "loadDocumentWithAscendentHierarchy") {
                return {
                    id: 10,
                    type: "embedded",
                    embedded_file_properties: {
                        content: "<p>my custom content </p>",
                    },
                };
            }

            return null;
        });

        const wrapper = shallowMount(DisplayEmbedded, {
            localVue,
            propsData: {
                item_id: 42,
            },
            mocks: { $store: store },
        });

        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(DisplayEmbeddedContent).exists()).toBeTruthy();
        expect(wrapper.findComponent(DisplayEmbeddedContent).props().content_to_display).toBe(
            "<p>my custom content </p>"
        );
        expect(
            wrapper.findComponent(DisplayEmbeddedContent).props().specific_version_number
        ).toBeNull();
        expect(wrapper.findComponent(DisplayEmbeddedSpinner).exists()).toBeFalsy();
    });

    it(`Given user display an embedded file content at a specific version
        When component is rendered
        Backend load the embedded file content and the specific version content`, async () => {
        const store_options = {
            state: {
                error: {},
            },
            getters: {
                "error/does_document_have_any_error": false,
            },
        };

        store = createStoreMock(store_options);

        store.dispatch.mockImplementation((action_name) => {
            if (action_name === "loadDocumentWithAscendentHierarchy") {
                return {
                    id: 10,
                    type: "embedded",
                    embedded_file_properties: {
                        content: "<p>my custom content </p>",
                    },
                };
            }

            return null;
        });

        getEmbeddedFileVersionContent.mockReturnValue(
            okAsync({ version_number: 3, content: "<p>An old content</p>" })
        );

        const wrapper = shallowMount(DisplayEmbedded, {
            localVue,
            propsData: {
                item_id: 42,
                version_id: 123,
            },
            mocks: { $store: store },
        });

        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(DisplayEmbeddedContent).exists()).toBeTruthy();
        expect(wrapper.findComponent(DisplayEmbeddedContent).props().content_to_display).toBe(
            "<p>An old content</p>"
        );
        expect(wrapper.findComponent(DisplayEmbeddedContent).props().specific_version_number).toBe(
            3
        );
        expect(wrapper.findComponent(DisplayEmbeddedSpinner).exists()).toBeFalsy();
    });

    it(`Reset currently displayed item form stored
        When component is destroyed`, () => {
        const store_options = {
            state: {
                error: {},
            },
            getters: {
                "error/does_document_have_any_error": false,
            },
        };

        store = createStoreMock(store_options);

        const wrapper = shallowMount(DisplayEmbedded, {
            localVue,
            propsData: {
                item_id: 42,
            },
            mocks: { $store: store },
        });

        wrapper.destroy();
        expect(store.commit).toHaveBeenCalledWith("updateCurrentlyPreviewedItem", null);
    });
});
