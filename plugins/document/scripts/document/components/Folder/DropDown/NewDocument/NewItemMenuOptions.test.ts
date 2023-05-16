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

import type { VueWrapper } from "@vue/test-utils";
import NewItemMenuOptions from "./NewItemMenuOptions.vue";
import { shallowMount } from "@vue/test-utils";
import type { Item, NewItemAlternativeArray } from "../../../../type";
import type { ConfigurationState } from "../../../../store/configuration";
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
import * as strict_inject from "@tuleap/vue-strict-inject";

jest.mock("../../../../helpers/emitter");

describe("NewItemMenuOptions", function () {
    const CURRENT_FOLDER: Item = {
        id: 123,
        type: TYPE_FOLDER,
    } as Item;

    afterEach(() => {
        jest.clearAllMocks();
    });

    function getWrapper(
        configuration: Pick<ConfigurationState, "embedded_are_allowed" | "user_can_create_wiki"> = {
            embedded_are_allowed: false,
            user_can_create_wiki: false,
        },
        create_new_item_alternatives: NewItemAlternativeArray = []
    ): VueWrapper<InstanceType<typeof NewItemMenuOptions>> {
        jest.spyOn(strict_inject, "strictInject").mockReturnValue(create_new_item_alternatives);
        return shallowMount(NewItemMenuOptions, {
            props: {
                item: CURRENT_FOLDER,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            namespaced: true,
                            state: {
                                embedded_are_allowed: configuration.embedded_are_allowed,
                                user_can_create_wiki: configuration.user_can_create_wiki,
                            } as ConfigurationState,
                        },
                    },
                }),
            },
        });
    }

    it("should allow to create a folder", async function () {
        const wrapper = getWrapper();

        await wrapper.find("[data-test=document-new-folder-creation-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("show-new-folder-modal", {
            detail: { parent: CURRENT_FOLDER },
        });
    });

    it("should allow to create a file", async function () {
        const wrapper = getWrapper();

        await wrapper.find("[data-test=document-new-file-creation-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("createItem", {
            item: CURRENT_FOLDER,
            type: TYPE_FILE,
        });
    });

    it("should allow to create a link", async function () {
        const wrapper = getWrapper();

        await wrapper.find("[data-test=document-new-link-creation-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("createItem", {
            item: CURRENT_FOLDER,
            type: TYPE_LINK,
        });
    });

    it("should allow to create an empty", async function () {
        const wrapper = getWrapper();

        await wrapper.find("[data-test=document-new-empty-creation-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("createItem", {
            item: CURRENT_FOLDER,
            type: TYPE_EMPTY,
        });
    });

    it("should allow to create a wiki", async function () {
        const wrapper = getWrapper({ user_can_create_wiki: true, embedded_are_allowed: false });

        await wrapper.find("[data-test=document-new-wiki-creation-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("createItem", {
            item: CURRENT_FOLDER,
            type: TYPE_WIKI,
        });
    });

    it("should not allow to create wiki if user cannot", function () {
        const wrapper = getWrapper({ user_can_create_wiki: false, embedded_are_allowed: false });

        expect(wrapper.find("[data-test=document-new-wiki-creation-button]").exists()).toBe(false);
    });

    it("should allow to create an embedded", async function () {
        const wrapper = getWrapper({ user_can_create_wiki: false, embedded_are_allowed: true });

        await wrapper.find("[data-test=document-new-embedded-creation-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("createItem", {
            item: CURRENT_FOLDER,
            type: TYPE_EMBEDDED,
        });
    });

    it("should not allow to create embedded if user cannot", function () {
        const wrapper = getWrapper({ user_can_create_wiki: false, embedded_are_allowed: false });

        expect(wrapper.find("[data-test=document-new-embedded-creation-button]").exists()).toBe(
            false
        );
    });

    it("should display new item alternatives", async function () {
        const wrapper = getWrapper({ user_can_create_wiki: false, embedded_are_allowed: false }, [
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
});
