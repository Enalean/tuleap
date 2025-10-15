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

import type { MockInstance } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { okAsync } from "neverthrow";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DisplayEmbedded from "./DisplayEmbedded.vue";
import DisplayEmbeddedContent from "./DisplayEmbeddedContent.vue";
import DisplayEmbeddedSpinner from "./DisplayEmbeddedSpinner.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import type { ErrorState } from "../../store/error/module";
import type { Embedded, Item } from "../../type";
import { EMBEDDED_FILE_DISPLAY_LARGE } from "../../type";
import * as VersionRestQuerier from "../../api/version-rest-querier";
import { PROJECT, USER_ID } from "../../configuration-keys";
import { ProjectBuilder } from "../../../tests/builders/ProjectBuilder";
import * as display_preferences from "../../helpers/preferences/embedded-file-display-preferences";
import { Option } from "@tuleap/option";

vi.mock("@tuleap/autocomplete-for-select2", () => {
    return { autocomplete_users_for_select2: vi.fn() };
});

vi.useFakeTimers();

describe("DisplayEmbedded", () => {
    let loadDocument: () => Promise<Item>;
    let update_currently_previewed_item: MockInstance;
    let getEmbeddedFileVersionContent: MockInstance;

    beforeEach(() => {
        loadDocument = (): Promise<Item> =>
            Promise.resolve({
                id: 10,
                type: "embedded",
                embedded_file_properties: {
                    content: "<p>my custom content </p>",
                },
            } as Embedded);
        update_currently_previewed_item = vi.fn();
        getEmbeddedFileVersionContent = vi.spyOn(
            VersionRestQuerier,
            "getEmbeddedFileVersionContent",
        );
        vi.spyOn(display_preferences, "getEmbeddedFileDisplayPreference").mockResolvedValue(
            Option.fromValue(EMBEDDED_FILE_DISPLAY_LARGE),
        );
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
                    },
                    actions: {
                        loadDocumentWithAscendentHierarchy: loadDocument,
                    },
                    mutations: {
                        updateCurrentlyPreviewedItem: update_currently_previewed_item,
                    },
                }),
                provide: {
                    [USER_ID.valueOf()]: 254,
                    [PROJECT.valueOf()]: new ProjectBuilder(101).build(),
                },
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
                    },
                    actions: {
                        loadDocumentWithAscendentHierarchy: loadDocument,
                    },
                    mutations: {
                        updateCurrentlyPreviewedItem: update_currently_previewed_item,
                    },
                }),
                provide: {
                    [USER_ID.valueOf()]: 254,
                    [PROJECT.valueOf()]: new ProjectBuilder(101).build(),
                },
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
        await vi.runOnlyPendingTimersAsync();

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

        await vi.runOnlyPendingTimersAsync();

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
