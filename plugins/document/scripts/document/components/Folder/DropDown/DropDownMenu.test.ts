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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../helpers/local-vue";
import DropDownMenu from "./DropDownMenu.vue";
import type { Item, State } from "../../../type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { ConfigurationState } from "../../../store/configuration";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../../constants";

describe("DropDownMenu", () => {
    function createWrapper(
        item: Item,
        should_display_history_in_document = false
    ): Wrapper<DropDownMenu> {
        const state = {
            configuration: {
                project_id: 101,
                max_files_dragndrop: 10,
                max_size_upload: 10000,
            } as unknown as ConfigurationState,
        } as unknown as State;
        const store_options = {
            state,
        };
        const store = createStoreMock(store_options);
        return shallowMount(DropDownMenu, {
            localVue,
            mocks: { $store: store },
            propsData: { isInFolderEmptyState: false, isInQuickLookMode: false, item },
            provide: {
                should_display_history_in_document,
            },
        });
    }

    describe("Approval table menu option -", () => {
        it(`Given item type is empty
            When we display the menu
            Then the approval table link should not be available`, async () => {
            const wrapper = createWrapper({
                id: 4,
                title: "my item title",
                type: "empty",
                can_user_manage: false,
            } as Item);
            await wrapper.vm.$nextTick();
            expect(
                wrapper.find("[data-test=document-dropdown-approval-tables]").exists()
            ).toBeFalsy();
        });
        it(`Given item type is a file
            When we display the menu
            Then the approval table link should be available`, () => {
            const wrapper = createWrapper({
                id: 4,
                title: "my item title",
                type: "file",
                can_user_manage: false,
            } as Item);
            expect(
                wrapper.find("[data-test=document-dropdown-approval-tables]").exists()
            ).toBeTruthy();
        });
    });

    describe("Download folder as zip", () => {
        it("Displays the button if the item is a folder", async () => {
            const wrapper = createWrapper({
                id: 69,
                title: "NSFW",
                type: "folder",
            } as Item);

            await wrapper.vm.$nextTick();

            expect(
                wrapper.find("[data-test=document-dropdown-download-folder-as-zip]").exists()
            ).toBeTruthy();
        });

        it("Does not display the button if the item is not a folder", async () => {
            const wrapper = createWrapper({
                id: 4,
                title: "my item title",
                type: "file",
                can_user_manage: false,
            } as Item);

            await wrapper.vm.$nextTick();

            expect(
                wrapper.find("[data-test=document-dropdown-download-folder-as-zip]").exists()
            ).toBeFalsy();
        });
    });

    describe("History", () => {
        it("should display a link to the legacy history by default", async () => {
            const wrapper = createWrapper({
                id: 4,
                title: "my item title",
                type: "file",
                can_user_manage: false,
            } as Item);

            await wrapper.vm.$nextTick();

            const link = wrapper.find("[data-test=document-history]").element;
            if (!(link instanceof HTMLAnchorElement)) {
                throw Error("Unable to find link");
            }

            expect(link.href).toContain("action=details&section=history");
        });

        it("should display a link to the Document history when feature flag is on", async () => {
            const wrapper = createWrapper(
                {
                    id: 4,
                    title: "my item title",
                    type: "file",
                    can_user_manage: false,
                } as Item,
                true
            );

            await wrapper.vm.$nextTick();

            expect(wrapper.find("[data-test=document-history]").props("to")).toStrictEqual({
                name: "history",
                params: { item_id: 4 },
            });
        });

        it.each([TYPE_FOLDER, TYPE_FILE, TYPE_LINK, TYPE_EMBEDDED, TYPE_WIKI, TYPE_EMPTY])(
            "should not display a %s with versions link by default",
            async (type) => {
                const wrapper = createWrapper({
                    id: 4,
                    title: "my item title",
                    type,
                    can_user_manage: false,
                } as Item);

                await wrapper.vm.$nextTick();

                expect(wrapper.find("[data-test=document-versions]").exists()).toBe(false);
            }
        );

        it.each([
            [TYPE_FOLDER, false],
            [TYPE_FILE, true],
            [TYPE_LINK, true],
            [TYPE_EMBEDDED, true],
            [TYPE_WIKI, false],
            [TYPE_EMPTY, false],
        ])(
            "should display a %s with versions link: %s when feature flag is on",
            async (type, should_versions_be_displayed) => {
                const wrapper = createWrapper(
                    {
                        id: 4,
                        title: "my item title",
                        type,
                        can_user_manage: false,
                    } as Item,
                    true
                );

                await wrapper.vm.$nextTick();

                expect(wrapper.find("[data-test=document-versions]").exists()).toBe(
                    should_versions_be_displayed
                );
            }
        );
    });
});
