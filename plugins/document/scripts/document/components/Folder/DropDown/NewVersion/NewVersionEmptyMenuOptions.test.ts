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
import NewVersionEmptyMenuOptions from "./NewVersionEmptyMenuOptions.vue";
import { shallowMount } from "@vue/test-utils";
import type { Empty, ItemFile, NewItemAlternativeArray } from "../../../../type";
import type { ConfigurationState } from "../../../../store/configuration";
import { TYPE_EMBEDDED, TYPE_EMPTY, TYPE_FILE, TYPE_LINK, TYPE_WIKI } from "../../../../constants";
import emitter, { default as real_emitter } from "../../../../helpers/emitter";
import * as get_office_file from "../../../../helpers/office/get-empty-office-file";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import { nextTick } from "vue";

describe("NewVersionEmptyMenuOptions", function () {
    const CURRENT_ITEM: Empty = {
        id: 123,
        type: TYPE_EMPTY,
        title: "Specs",
    } as Empty;
    let emit: jest.SpyInstance;
    let location: Location;
    let create_version_from_empty: jest.Mock;

    beforeEach(() => {
        emit = jest.spyOn(emitter, "emit");
        location = { href: "" } as Location;
        create_version_from_empty = jest.fn();
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    function getWrapper(
        configuration: Pick<ConfigurationState, "embedded_are_allowed" | "user_can_create_wiki"> = {
            embedded_are_allowed: false,
            user_can_create_wiki: false,
        },
        create_new_item_alternatives: NewItemAlternativeArray = [],
    ): VueWrapper<InstanceType<typeof NewVersionEmptyMenuOptions>> {
        return shallowMount(NewVersionEmptyMenuOptions, {
            props: {
                item: CURRENT_ITEM,
                location,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                embedded_are_allowed: configuration.embedded_are_allowed,
                                user_can_create_wiki: configuration.user_can_create_wiki,
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                    actions: {
                        createNewVersionFromEmpty: create_version_from_empty,
                    },
                }),
                provide: {
                    create_new_item_alternatives,
                },
            },
        });
    }

    it("should not allow to create a folder", function () {
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=document-new-folder-creation-button]").exists()).toBe(
            false,
        );
    });

    it("should allow to create a file", async function () {
        const wrapper = getWrapper();

        await wrapper.find("[data-test=document-new-file-creation-button]").trigger("click");

        expect(emit).toHaveBeenCalledWith("show-create-new-version-modal-for-empty", {
            item: CURRENT_ITEM,
            type: TYPE_FILE,
        });
    });

    it("should allow to create a link", async function () {
        const wrapper = getWrapper();

        await wrapper.find("[data-test=document-new-link-creation-button]").trigger("click");

        expect(emit).toHaveBeenCalledWith("show-create-new-version-modal-for-empty", {
            item: CURRENT_ITEM,
            type: TYPE_LINK,
        });
    });

    it("should not allow to create an empty", function () {
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=document-new-empty-creation-button]").exists()).toBe(false);
    });

    it("should allow to create a wiki", async function () {
        const wrapper = getWrapper({ user_can_create_wiki: true, embedded_are_allowed: false });

        await wrapper.find("[data-test=document-new-wiki-creation-button]").trigger("click");

        expect(emit).toHaveBeenCalledWith("show-create-new-version-modal-for-empty", {
            item: CURRENT_ITEM,
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

        expect(emit).toHaveBeenCalledWith("show-create-new-version-modal-for-empty", {
            item: CURRENT_ITEM,
            type: TYPE_EMBEDDED,
        });
    });

    it("should not allow to create embedded if user cannot", function () {
        const wrapper = getWrapper({ user_can_create_wiki: false, embedded_are_allowed: false });

        expect(wrapper.find("[data-test=document-new-embedded-creation-button]").exists()).toBe(
            false,
        );
    });

    it("should convert an empty to an alternative and open the file after conversion", async function () {
        const file = new File([], "document.docx", { type: "application/docx" });
        jest.spyOn(get_office_file, "getEmptyOfficeFileFromMimeType").mockResolvedValue({
            badge_class: "document-document-badge",
            extension: "docx",
            file,
        });

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

        await nextTick();
        await nextTick();

        expect(create_version_from_empty).toHaveBeenCalled();
        expect(create_version_from_empty.mock.calls[0][1][2].file_properties.file.name).toBe(
            "Specs.docx",
        );

        real_emitter.emit("item-has-just-been-updated", {
            item: {
                ...CURRENT_ITEM,
                type: TYPE_FILE,
                file_properties: {
                    open_href: "/path/to/123",
                },
            } as ItemFile,
        });
        expect(location.href).toBe("/path/to/123");
    });
});
