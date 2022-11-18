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

import type { Wrapper } from "@vue/test-utils";
import NewVersionEmptyMenuOptions from "./NewVersionEmptyMenuOptions.vue";
import { shallowMount } from "@vue/test-utils";
import type { Empty } from "../../../../type";
import type { ConfigurationState } from "../../../../store/configuration";
import localVue from "../../../../helpers/local-vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import { TYPE_EMBEDDED, TYPE_EMPTY, TYPE_FILE, TYPE_LINK, TYPE_WIKI } from "../../../../constants";
import emitter from "../../../../helpers/emitter";

jest.mock("../../../../helpers/emitter");

describe("NewVersionEmptyMenuOptions", function () {
    const CURRENT_ITEM: Empty = {
        id: 123,
        type: TYPE_EMPTY,
    } as Empty;

    afterEach(() => {
        jest.clearAllMocks();
    });

    function getWrapper(
        configuration: Pick<ConfigurationState, "embedded_are_allowed" | "user_can_create_wiki"> = {
            embedded_are_allowed: false,
            user_can_create_wiki: false,
        }
    ): Wrapper<NewVersionEmptyMenuOptions> {
        return shallowMount(NewVersionEmptyMenuOptions, {
            localVue,
            propsData: {
                item: CURRENT_ITEM,
            },
            mocks: {
                $store: createStoreMock({
                    state: { configuration },
                }),
            },
        });
    }

    it("should not allow to create a folder", function () {
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=document-new-folder-creation-button]").exists()).toBe(
            false
        );
    });

    it("should allow to create a file", async function () {
        const wrapper = getWrapper();

        await wrapper.find("[data-test=document-new-file-creation-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("show-create-new-version-modal-for-empty", {
            item: CURRENT_ITEM,
            type: TYPE_FILE,
        });
    });

    it("should allow to create a link", async function () {
        const wrapper = getWrapper();

        await wrapper.find("[data-test=document-new-link-creation-button]").trigger("click");

        expect(emitter.emit).toHaveBeenCalledWith("show-create-new-version-modal-for-empty", {
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

        expect(emitter.emit).toHaveBeenCalledWith("show-create-new-version-modal-for-empty", {
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

        expect(emitter.emit).toHaveBeenCalledWith("show-create-new-version-modal-for-empty", {
            item: CURRENT_ITEM,
            type: TYPE_EMBEDDED,
        });
    });

    it("should not allow to create embedded if user cannot", function () {
        const wrapper = getWrapper({ user_can_create_wiki: false, embedded_are_allowed: false });

        expect(wrapper.find("[data-test=document-new-embedded-creation-button]").exists()).toBe(
            false
        );
    });
});
