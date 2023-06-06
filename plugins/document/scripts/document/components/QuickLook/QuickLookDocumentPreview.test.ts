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
import QuickLookDocumentPreview from "./QuickLookDocumentPreview.vue";
import { TYPE_EMBEDDED, TYPE_FILE, TYPE_LINK } from "../../constants";
import type { Embedded, Item, ItemFile, Link, RootState } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("QuickLookDocumentPreview", () => {
    function createWrapper(
        currently_previewed_item: Item,
        icon_class: string,
        is_loading_currently_previewed_item: boolean
    ): VueWrapper<InstanceType<typeof QuickLookDocumentPreview>> {
        return shallowMount(QuickLookDocumentPreview, {
            props: {
                iconClass: icon_class,
            },
            global: {
                ...getGlobalTestOptions({
                    state: {
                        currently_previewed_item,
                        is_loading_currently_previewed_item,
                    } as RootState,
                }),
                directives: {
                    "dompurify-html": jest.fn(),
                },
            },
        });
    }
    it("Renders an image if the item is an image", () => {
        const currently_previewed_item = {
            id: 42,
            parent_id: 66,
            type: TYPE_FILE,
            user_can_write: true,
            file_properties: {
                file_type: "image/png",
            },
        } as ItemFile;

        const wrapper = createWrapper(currently_previewed_item, "", false);

        expect(wrapper.find(".document-quick-look-image-container").exists()).toBeTruthy();
        expect(wrapper.find(".document-quick-look-embedded").exists()).toBeFalsy();
        expect(wrapper.find(".document-quick-look-icon-container").exists()).toBeFalsy();
    });

    it("Renders some rich text html if the item is an embedded file", () => {
        const currently_previewed_item = {
            id: 42,
            parent_id: 66,
            type: TYPE_EMBEDDED,
            user_can_write: true,
            embedded_file_properties: {
                content: "<h1>Hello world!</h1>",
            },
        } as Embedded;

        const wrapper = createWrapper(currently_previewed_item, "", false);

        expect(wrapper.find(".document-quick-look-embedded").exists()).toBeTruthy();
        expect(wrapper.find(".document-quick-look-image-container").exists()).toBeFalsy();
        expect(wrapper.find(".document-quick-look-icon-container").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-preview-spinner]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-quick-look-embedded]").exists()).toBeTruthy();
    });

    it("Displays the icon passed in props otherwise", () => {
        const currently_previewed_item = {
            id: 42,
            parent_id: 66,
            type: TYPE_LINK,
            user_can_write: true,
        } as Link;

        const wrapper = createWrapper(currently_previewed_item, "fa-link", false);

        expect(wrapper.find(".fa-link").exists()).toBeTruthy();
        expect(wrapper.find(".document-quick-look-icon-container").exists()).toBeTruthy();
        expect(wrapper.find(".document-quick-look-image-container").exists()).toBeFalsy();
        expect(wrapper.find(".document-quick-look-embedded").exists()).toBeFalsy();
    });

    it("Display spinner when embedded file is loaded", () => {
        const currently_previewed_item = {
            id: 42,
            parent_id: 66,
            type: TYPE_EMBEDDED,
            user_can_write: true,
        } as Embedded;

        const wrapper = createWrapper(currently_previewed_item, "", true);

        expect(wrapper.find("[data-test=document-preview-spinner]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-quick-look-embedded]").exists()).toBeFalsy();
    });

    it("Do not display spinner for other types", () => {
        const currently_previewed_item = {
            id: 42,
            parent_id: 66,
            type: TYPE_FILE,
            user_can_write: true,
            file_properties: null,
        } as ItemFile;

        const wrapper = createWrapper(currently_previewed_item, "", true);

        expect(wrapper.find("[data-test=document-preview-spinner]").exists()).toBeFalsy();
    });
});
