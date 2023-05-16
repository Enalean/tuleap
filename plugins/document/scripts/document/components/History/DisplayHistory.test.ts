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

import { RouterLinkStub, shallowMount } from "@vue/test-utils";
import DisplayHistory from "./DisplayHistory.vue";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../constants";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import type { RouteLocationNormalizedLoaded } from "vue-router";
import { nextTick } from "vue";

import * as router from "vue-router";
import type { Item } from "../../type";
import * as strict_inject from "@tuleap/vue-strict-inject";

jest.mock("vue-router");
describe("DisplayHistory", () => {
    let load_document: jest.Mock;

    beforeEach(() => {
        jest.spyOn(router, "useRoute").mockReturnValue({
            params: { item_id: "101" },
        } as unknown as RouteLocationNormalizedLoaded);
        load_document = jest.fn();
    });

    it("should not display anything if user tries direct access while feature flag is off", async () => {
        load_document.mockReturnValue({ id: 10 } as Item);
        jest.spyOn(strict_inject, "strictInject").mockReturnValue(false);
        const wrapper = shallowMount(DisplayHistory, {
            global: {
                ...getGlobalTestOptions({
                    actions: {
                        loadDocumentWithAscendentHierarchy: load_document,
                    },
                }),
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        // wait for loadDocumentWithAscendentHierarchy() to be called
        await nextTick();
        await nextTick();

        expect(wrapper.element).toMatchInlineSnapshot(`<!--v-if-->`);
    });

    it("should display logs", async () => {
        load_document.mockReturnValue({ id: 10 } as Item);
        jest.spyOn(strict_inject, "strictInject").mockReturnValue(true);
        const wrapper = shallowMount(DisplayHistory, {
            global: {
                ...getGlobalTestOptions({
                    actions: {
                        loadDocumentWithAscendentHierarchy: load_document,
                    },
                }),
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        // wait for loadDocumentWithAscendentHierarchy() to be called
        await nextTick();
        await nextTick();

        expect(wrapper.html()).toContain("history-logs-stub");
    });

    it.each([
        [TYPE_FOLDER, false],
        [TYPE_FILE, true],
        [TYPE_LINK, true],
        [TYPE_EMBEDDED, true],
        [TYPE_WIKI, false],
        [TYPE_EMPTY, false],
    ])(
        `should display a Versions link for %s: %s`,
        async (type, should_versions_link_be_displayed) => {
            load_document.mockReturnValue({ id: 10, type } as Item);
            jest.spyOn(strict_inject, "strictInject").mockReturnValue(true);
            const wrapper = shallowMount(DisplayHistory, {
                global: {
                    ...getGlobalTestOptions({
                        actions: {
                            loadDocumentWithAscendentHierarchy: load_document,
                        },
                    }),
                    stubs: {
                        RouterLink: RouterLinkStub,
                    },
                },
            });

            // wait for loadDocumentWithAscendentHierarchy() to be called
            await nextTick();
            await nextTick();

            expect(wrapper.vm.item_has_versions).toBe(should_versions_link_be_displayed);
        }
    );
});
