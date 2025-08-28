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

import { afterEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import NewItemMenuOptions from "./NewItemMenuOptions.vue";
import { shallowMount } from "@vue/test-utils";
import type { Item, NewItemAlternativeArray, OtherItemTypeCollection } from "../../../../type";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../../../constants";
import emitter from "../../../../helpers/emitter";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import { NEW_ITEMS_ALTERNATIVES, OTHER_ITEM_TYPES } from "../../../../injection-keys";
import { EMBEDDED_ARE_ALLOWED, USER_CAN_CREATE_WIKI } from "../../../../configuration-keys";

vi.mock("../../../../helpers/emitter");

describe("NewItemMenuOptions", function () {
    const CURRENT_FOLDER: Item = {
        id: 123,
        type: TYPE_FOLDER,
    } as Item;

    afterEach(() => {
        vi.clearAllMocks();
    });

    function getWrapper(
        user_can_create_wiki: boolean,
        embedded_are_allowed: boolean,
        create_new_item_alternatives: NewItemAlternativeArray = [],
        other_item_types: OtherItemTypeCollection = {},
    ): VueWrapper<InstanceType<typeof NewItemMenuOptions>> {
        return shallowMount(NewItemMenuOptions, {
            props: {
                item: CURRENT_FOLDER,
            },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [NEW_ITEMS_ALTERNATIVES.valueOf()]: create_new_item_alternatives,
                    [OTHER_ITEM_TYPES.valueOf()]: other_item_types,
                    [USER_CAN_CREATE_WIKI.valueOf()]: user_can_create_wiki,
                    [EMBEDDED_ARE_ALLOWED.valueOf()]: embedded_are_allowed,
                },
            },
        });
    }

    it("should allow to create a folder", async function () {
        const wrapper = getWrapper(false, false);

        await wrapper.find("[data-test=document-new-folder-creation-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("show-new-folder-modal", {
            detail: { parent: CURRENT_FOLDER },
        });
    });

    it("should allow to create a file", async function () {
        const wrapper = getWrapper(false, false);

        await wrapper.find("[data-test=document-new-file-creation-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("createItem", {
            item: CURRENT_FOLDER,
            type: TYPE_FILE,
        });
    });

    it("should allow to create a link", async function () {
        const wrapper = getWrapper(false, false);

        await wrapper.find("[data-test=document-new-link-creation-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("createItem", {
            item: CURRENT_FOLDER,
            type: TYPE_LINK,
        });
    });

    it("should allow to create an empty", async function () {
        const wrapper = getWrapper(false, false);

        await wrapper.find("[data-test=document-new-empty-creation-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("createItem", {
            item: CURRENT_FOLDER,
            type: TYPE_EMPTY,
        });
    });

    it("should allow to create a wiki", async function () {
        const wrapper = getWrapper(true, false);

        await wrapper.find("[data-test=document-new-wiki-creation-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("createItem", {
            item: CURRENT_FOLDER,
            type: TYPE_WIKI,
        });
    });

    it("should not allow to create wiki if user cannot", function () {
        const wrapper = getWrapper(false, false);

        expect(wrapper.find("[data-test=document-new-wiki-creation-button]").exists()).toBe(false);
    });

    it("should allow to create an embedded", async function () {
        const wrapper = getWrapper(false, true);

        await wrapper.find("[data-test=document-new-embedded-creation-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("createItem", {
            item: CURRENT_FOLDER,
            type: TYPE_EMBEDDED,
        });
    });

    it("should not allow to create embedded if user cannot", function () {
        const wrapper = getWrapper(false, false);

        expect(wrapper.find("[data-test=document-new-embedded-creation-button]").exists()).toBe(
            false,
        );
    });

    it("should display new item alternatives", async function () {
        const wrapper = getWrapper(false, false, [
            {
                title: "section",
                alternatives: [
                    { mime_type: "application/word", title: "Documents" },
                    { mime_type: "application/powerpoint", title: "Presentation" },
                ],
            },
        ]);
        const alternatives = wrapper.findAll("[data-test=alternative]");
        expect(alternatives).toHaveLength(2);

        await alternatives.at(0).trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("createItem", {
            item: CURRENT_FOLDER,
            type: TYPE_FILE,
            from_alternative: { mime_type: "application/word", title: "Documents" },
        });
    });

    it("should display a new other type item", async function () {
        const wrapper = getWrapper(false, false, [], {
            whatever: {
                icon: "my-icon",
                title: "Whatever title",
            },
            another: {
                icon: "another-icon",
                title: "Another title",
            },
        });
        const other_item_types = wrapper.findAll("[data-test=other_item_type]");
        expect(other_item_types).toHaveLength(2);

        await other_item_types.at(0).trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("createItem", {
            item: CURRENT_FOLDER,
            type: "whatever",
            is_other: true,
        });
    });
});
