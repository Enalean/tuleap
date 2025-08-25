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

import { describe, expect, it, vi } from "vitest";
import type { FileProperties, ItemSearchResult, OtherItemTypeCollection } from "../../../../type";
import type { VueWrapper } from "@vue/test-utils";
import { RouterLinkStub, shallowMount } from "@vue/test-utils";
import CellTitle from "./CellTitle.vue";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import * as tlp_dropdown from "@tuleap/tlp-dropdown";
import { OTHER_ITEM_TYPES } from "../../../../injection-keys";
import { PROJECT_ID } from "../../../../configuration-keys";

vi.mock("@tuleap/tlp-dropdown");

describe("CellTitle", () => {
    function getWrapper(
        item: ItemSearchResult,
        other_item_types: OtherItemTypeCollection,
    ): VueWrapper {
        return shallowMount(CellTitle, {
            props: {
                item,
            },
            global: {
                ...getGlobalTestOptions({}),
                stubs: {
                    RouterLink: RouterLinkStub,
                },
                provide: {
                    [OTHER_ITEM_TYPES.valueOf()]: other_item_types,
                    [PROJECT_ID.valueOf()]: 101,
                },
            },
        });
    }

    it("should output a link for File", () => {
        const item = {
            id: 123,
            type: "file",
            title: "Lorem",
            file_properties: {
                file_type: "text/html",
                download_href: "/path/to/file",
                open_href: null,
            } as FileProperties,
        } as unknown as ItemSearchResult;

        const wrapper = getWrapper(item, {});

        const link = wrapper.find("[data-test=link]");
        expect(link.attributes().href).toBe("/path/to/file");
        expect(link.attributes().title).toBe("Lorem");
    });

    it("should output a link to open a File", () => {
        const item = {
            id: 123,
            type: "file",
            title: "Lorem",
            file_properties: {
                file_type: "text/html",
                download_href: "/path/to/file",
                open_href: "/path/to/open/file",
            } as FileProperties,
        } as unknown as ItemSearchResult;

        const wrapper = getWrapper(item, {});

        const link = wrapper.find("[data-test=link]");
        expect(link.attributes().href).toBe("/path/to/open/file");
        expect(link.attributes().title).toBe("Lorem");
    });

    it("should output a link for Wiki", () => {
        const item = {
            id: 123,
            type: "wiki",
            title: "Lorem",
        } as unknown as ItemSearchResult;

        const wrapper = getWrapper(item, {});

        const link = wrapper.find("[data-test=link]");
        expect(link.attributes().href).toBe("/plugins/docman/?group_id=101&action=show&id=123");
        expect(link.attributes().title).toBe("Lorem");
    });

    it("should set the empty icon for empty document", () => {
        const item = {
            id: 123,
            type: "empty",
            title: "Lorem",
        } as unknown as ItemSearchResult;

        const wrapper = getWrapper(item, { other: { title: "Other", icon: "other-icon" } });

        expect(wrapper.find("[data-test=icon]").classes()).toContain("document-empty-icon");
    });

    it("should set the empty icon for other type document", () => {
        const item = {
            id: 123,
            type: "other",
            title: "Lorem",
        } as unknown as ItemSearchResult;

        const wrapper = getWrapper(item, { other: { title: "Other", icon: "other-icon" } });

        expect(wrapper.find("[data-test=icon]").classes()).not.toContain("document-empty-icon");
        expect(wrapper.find("[data-test=icon]").classes()).toContain("other-icon");
    });

    it("should output a route link for Embedded", () => {
        const fake_dropdown_object = {
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
        } as unknown as Dropdown;

        vi.spyOn(tlp_dropdown, "createDropdown").mockReturnValue(fake_dropdown_object);

        const item = {
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
        } as unknown as ItemSearchResult;

        const wrapper = getWrapper(item, {});

        expect(wrapper.vm.in_app_link).toStrictEqual({
            name: "item",
            params: {
                folder_id: "120",
                item_id: "123",
            },
        });
        const link = wrapper.find("[data-test=router-link]");
        expect(link.attributes().title).toBe("Lorem");
    });
});
