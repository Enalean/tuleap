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

import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../constants";
import { shallowMount } from "@vue/test-utils";
import DisplayVersions from "./DisplayVersions.vue";
import HistoryVersions from "./HistoryVersions.vue";
import { nextTick } from "vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import type { Item } from "../../type";
import * as router from "vue-router";
import type { RouteLocationNormalizedLoaded } from "vue-router";
import * as strict_inject from "@tuleap/vue-strict-inject";

jest.mock("vue-router");

describe("DisplayVersions", () => {
    beforeEach(() => {
        jest.spyOn(router, "useRoute").mockReturnValue({
            params: { item_id: "101" },
        } as unknown as RouteLocationNormalizedLoaded);
    });

    it.each([
        [TYPE_FOLDER, false],
        [TYPE_FILE, true],
        [TYPE_LINK, true],
        [TYPE_EMBEDDED, true],
        [TYPE_WIKI, false],
        [TYPE_EMPTY, false],
    ])("should display a %s with versions: %s", async (type, should_versions_be_displayed) => {
        const load_with_hierarchy = jest.fn().mockImplementation(() => {
            return Promise.resolve({
                id: 42,
                type,
            } as Item);
        });

        jest.spyOn(strict_inject, "strictInject").mockReturnValue(true);

        const wrapper = shallowMount(DisplayVersions, {
            global: {
                ...getGlobalTestOptions({
                    actions: {
                        loadDocumentWithAscendentHierarchy: load_with_hierarchy,
                    },
                }),
                stubs: ["router-link"],
            },
        });

        // wait for loadDocumentWithAscendentHierarchy() to be called
        await nextTick();
        await nextTick();

        expect(wrapper.findComponent(HistoryVersions).exists()).toBe(should_versions_be_displayed);
    });

    it("should not display anything if user tries direct access while feature flag is off", async () => {
        const load_with_hierarchy = jest.fn().mockImplementation(() => {
            return Promise.resolve({
                id: 42,
                type: TYPE_FILE,
            } as Item);
        });

        jest.spyOn(strict_inject, "strictInject").mockReturnValue(false);

        const wrapper = shallowMount(DisplayVersions, {
            global: {
                ...getGlobalTestOptions({
                    actions: {
                        loadDocumentWithAscendentHierarchy: load_with_hierarchy,
                    },
                }),
                stubs: ["router-link"],
            },
        });

        // wait for loadDocumentWithAscendentHierarchy() to be called
        await nextTick();
        await nextTick();

        expect(wrapper.element).toMatchInlineSnapshot(`<!--v-if-->`);
    });
});
