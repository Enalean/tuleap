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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DisplayEmbedded from "./DisplayEmbedded.vue";
import DisplayEmbeddedContent from "./DisplayEmbeddedContent.vue";
import DisplayEmbeddedSpinner from "./DisplayEmbeddedSpinner.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import type { ErrorState } from "../../store/error/module";
import { nextTick } from "vue";
import type { Embedded, Item } from "../../type";

describe("DisplayEmbedded", () => {
    let loadDocument: () => Promise<Item>;
    let update_currently_previewed_item: jest.Mock;
    let get_preferencies: jest.Mock;
    let display_in_large_mode: jest.Mock;

    beforeEach(() => {
        loadDocument = (): Promise<Item> =>
            Promise.resolve({
                id: 10,
                type: "embedded",
                embedded_file_properties: {
                    content: "<p>my custom content </p>",
                },
            } as Embedded);
        update_currently_previewed_item = jest.fn();
        get_preferencies = jest.fn();
        display_in_large_mode = jest.fn();
    });

    function getWrapperWithError(
        has_document_permission_error: boolean,
        has_document_loading_error: boolean,
    ): VueWrapper<InstanceType<typeof DisplayEmbedded>> {
        return shallowMount(DisplayEmbedded, {
            props: {
                item_id: 42,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        error: {
                            state: {
                                has_document_permission_error,
                                has_document_loading_error,
                            } as unknown as ErrorState,
                            namespaced: true,
                            getters: {
                                does_document_have_any_error: () => true,
                            },
                        },
                        preferencies: {
                            namespaced: true,
                            actions: {
                                getEmbeddedFileDisplayPreference: get_preferencies,
                            },
                            mutations: {
                                shouldDisplayEmbeddedInLargeMode: display_in_large_mode,
                            },
                        },
                    },
                    actions: {
                        loadDocumentWithAscendentHierarchy: loadDocument,
                    },
                    mutations: {
                        updateCurrentlyPreviewedItem: update_currently_previewed_item,
                    },
                }),
            },
        });
    }

    function getWrapper(
        version_id: number | null,
    ): VueWrapper<InstanceType<typeof DisplayEmbedded>> {
        return shallowMount(DisplayEmbedded, {
            props: {
                item_id: 42,
                version_id: version_id,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        error: {
                            namespaced: true,
                            getters: {
                                does_document_have_any_error: () => false,
                            },
                        },
                        preferencies: {
                            namespaced: true,
                            actions: {
                                getEmbeddedFileDisplayPreference: get_preferencies,
                            },
                            mutations: {
                                shouldDisplayEmbeddedInLargeMode: display_in_large_mode,
                            },
                        },
                    },
                    actions: {
                        loadDocumentWithAscendentHierarchy: loadDocument,
                    },
                    mutations: {
                        updateCurrentlyPreviewedItem: update_currently_previewed_item,
                    },
                }),
            },
        });
    }

    it(`Given user display an embedded file content
        When backend throw a permission error
        Then no spinner is displayed and component is not rendered`, () => {
        const wrapper = getWrapperWithError(true, false);

        expect(wrapper.findComponent(DisplayEmbeddedContent).exists()).toBeFalsy();
        expect(wrapper.findComponent(DisplayEmbeddedSpinner).exists()).toBeFalsy();
    });

    it(`Given user display an embedded file content
        When backend throw a loading error
        Then no spinner is displayed and component is not rendered`, () => {
        const wrapper = getWrapperWithError(false, true);

        expect(wrapper.findComponent(DisplayEmbeddedContent).exists()).toBeFalsy();
        expect(wrapper.findComponent(DisplayEmbeddedSpinner).exists()).toBeFalsy();
    });

    it(`Given user display an embedded file content
        When component is rendered
        Backend load the embedded file content`, async () => {
        getEmbeddedFileVersionContent.mockReturnValue(
            okAsync({ version_number: 3, content: "<p>my custom content </p>" }),
        );

        const wrapper = getWrapper(null);
        await nextTick();
        await nextTick();
        await nextTick();
        await nextTick();
        await nextTick();

        expect(wrapper.findComponent(DisplayEmbeddedContent).exists()).toBeTruthy();
        expect(wrapper.findComponent(DisplayEmbeddedContent).props().content_to_display).toBe(
            "<p>my custom content </p>",
        );
        expect(
            wrapper.findComponent(DisplayEmbeddedContent).props().specific_version_number,
        ).toBeNull();
        expect(wrapper.findComponent(DisplayEmbeddedSpinner).exists()).toBeFalsy();
    });

    it(`Given user display an embedded file content at a specific version
        When component is rendered
        Backend load the embedded file content and the specific version content`, async () => {
        getEmbeddedFileVersionContent.mockReturnValue(
            okAsync({ version_number: 3, content: "<p>An old content</p>" }),
        );

        const wrapper = getWrapper(3);

        await nextTick();
        await nextTick();
        await nextTick();
        await nextTick();
        await nextTick();

        expect(wrapper.findComponent(DisplayEmbeddedContent).exists()).toBeTruthy();
        expect(wrapper.findComponent(DisplayEmbeddedContent).props().content_to_display).toBe(
            "<p>An old content</p>",
        );
        expect(wrapper.findComponent(DisplayEmbeddedContent).props().specific_version_number).toBe(
            3,
        );
        expect(wrapper.findComponent(DisplayEmbeddedSpinner).exists()).toBeFalsy();
    });

    it(`Reset currently displayed item form stored
        When component is destroyed`, () => {
        const wrapper = getWrapper(null);

        wrapper.unmount();
        expect(update_currently_previewed_item).toHaveBeenCalledWith(expect.anything(), null);
    });
});
