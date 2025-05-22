/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import OtherDocumentCellTitle from "./OtherDocumentCellTitle.vue";
import type { OtherTypeProperties, Folder, OtherTypeItem, RootState } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { OTHER_ITEM_TYPES } from "../../../injection-keys";

describe("OtherDocumentCellTitle", () => {
    function getWrapper(
        item: OtherTypeItem,
    ): VueWrapper<InstanceType<typeof OtherDocumentCellTitle>> {
        return shallowMount(OtherDocumentCellTitle, {
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
                provide: {
                    [OTHER_ITEM_TYPES.valueOf()]: {
                        whatever: { title: "Whatever", icon: "whatever-icon " },
                    },
                },
            },
        });
    }

    it(`Given other_type_properties is not set
        When we display item title
        Then we should display corrupted badge`, () => {
        const item = {
            id: 42,
            title: "my corrupted other type document",
            other_type_properties: null,
            type: "whatever",
        } as OtherTypeItem;

        const wrapper = getWrapper(item);

        expect(wrapper.find(".document-badge-corrupted").exists()).toBeTruthy();
    });

    it(`Given other_type_properties is set
        When we display item title
        Then we should not display corrupted badge`, () => {
        const item = {
            id: 42,
            title: "my other type document",
            other_type_properties: {
                open_href: "/whatever/42",
            } as OtherTypeProperties,
            type: "whatever",
        } as OtherTypeItem;

        const wrapper = getWrapper(item);

        expect(wrapper.find(".document-badge-corrupted").exists()).toBeFalsy();
    });

    it(`Given other_type_properties is set
        When we display item title
        Then we should have a link to download the file`, () => {
        const item = {
            id: 42,
            title: "my other type document",
            other_type_properties: {
                open_href: "/whatever/42",
            } as OtherTypeProperties,
            type: "whatever",
        } as OtherTypeItem;

        const wrapper = getWrapper(item);

        expect(
            wrapper.find<HTMLAnchorElement>("[data-test=document-folder-subitem-link]").element
                .href,
        ).toContain("/whatever/42");
    });

    it(`Given it is a known other type
        When we display item title
        Then the icon is given by configuration`, () => {
        const item = {
            id: 42,
            title: "my other type document",
            other_type_properties: {
                open_href: "/whatever/42",
            } as OtherTypeProperties,
            type: "whatever",
        } as OtherTypeItem;

        const wrapper = getWrapper(item);

        expect(wrapper.find("[data-test=icon]").classes()).toContain("whatever-icon");
    });

    it(`Given it is an unknown other type
        When we display item title
        Then the icon defaults to empty`, () => {
        const item = {
            id: 42,
            title: "my other type document",
            other_type_properties: {
                open_href: "/whatever/42",
            } as OtherTypeProperties,
            type: "unknown",
        } as OtherTypeItem;

        const wrapper = getWrapper(item);

        expect(wrapper.find("[data-test=icon]").classes()).toContain("document-empty-icon");
    });
});
