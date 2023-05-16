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
import { RouterLinkStub, shallowMount } from "@vue/test-utils";
import DropDownMenu from "./DropDownMenu.vue";
import type { Item } from "../../../type";
import type { ConfigurationState } from "../../../store/configuration";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "../../../constants";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

import { nextTick } from "vue";
import * as strict_inject from "@tuleap/vue-strict-inject";

describe("DropDownMenu", () => {
    function createWrapper(
        item: Item,
        should_display_history_in_document = false
    ): VueWrapper<InstanceType<typeof DropDownMenu>> {
        jest.spyOn(strict_inject, "strictInject").mockReturnValue(
            should_display_history_in_document
        );
        return shallowMount(DropDownMenu, {
            props: { item },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                project_id: 101,
                                is_deletion_allowed: true,
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                }),
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });
    }

    describe("Dropdown menu", () => {
        it(`Detects empty document type`, async () => {
            const wrapper = createWrapper({
                id: 4,
                title: "my item title",
                type: "empty",
                can_user_manage: false,
            } as Item);
            await nextTick();
            expect(wrapper.vm.is_item_an_empty_document).toBeTruthy();
        });
        it(`Other types are not empty documents`, () => {
            const wrapper = createWrapper({
                id: 4,
                title: "my item title",
                type: "file",
                can_user_manage: false,
            } as Item);
            expect(wrapper.vm.is_item_an_empty_document).toBeFalsy();
        });

        it("Detects folder", async () => {
            const wrapper = createWrapper({
                id: 69,
                title: "NSFW",
                type: "folder",
            } as Item);

            await nextTick();

            expect(wrapper.vm.is_item_a_folder).toBeTruthy();
        });

        it("Other types are not folders", async () => {
            const wrapper = createWrapper({
                id: 4,
                title: "my item title",
                type: "file",
                can_user_manage: false,
            } as Item);

            await nextTick();

            expect(wrapper.vm.is_item_a_folder).toBeFalsy();
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

            await nextTick();

            expect(wrapper.vm.should_display_versions_link).toBe(false);
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

            await nextTick();

            expect(wrapper.vm.should_display_versions_link).toBe(true);
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

                await nextTick();

                expect(wrapper.vm.should_display_versions_link).toBe(false);
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

                await nextTick();

                expect(wrapper.vm.should_display_versions_link).toBe(should_versions_be_displayed);
            }
        );
    });
});
