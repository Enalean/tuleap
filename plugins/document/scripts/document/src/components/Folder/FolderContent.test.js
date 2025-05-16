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

import { shallowMount } from "@vue/test-utils";
import FolderContent from "./FolderContent.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("FolderContent", () => {
    let factory,
        state = {},
        item;

    const mockRoute = {
        params: {
            id: 1,
        },
    };
    const replace = jest.fn();
    const mockRouter = {
        replace,
    };
    let update_currently_previewed_item_mock = jest.fn();
    let toggle_quick_look_mock = jest.fn();

    beforeEach(() => {
        factory = () => {
            replace.mockReset();
            return shallowMount(FolderContent, {
                global: {
                    ...getGlobalTestOptions({
                        state,
                        mutations: {
                            updateCurrentlyPreviewedItem: update_currently_previewed_item_mock,
                            toggleQuickLook: toggle_quick_look_mock,
                        },
                    }),
                    mocks: {
                        $route: mockRoute,
                        $router: mockRouter,
                    },
                },
            });
        };

        item = {
            id: 42,
            title: "my item title",
            parent_id: 0,
        };
    });

    it(`Should not display preview when component is rendered`, () => {
        const wrapper = factory();

        expect(wrapper.find("[data-test=document-quick-look]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-folder-owner-information]").exists()).toBeTruthy();
    });

    describe("toggleQuickLook", () => {
        it(`Given no item is currently previewed, then it directly displays quick look`, async () => {
            state.currently_previewed_item = null;
            state.current_folder = item;

            const wrapper = factory();
            const event = {
                details: { item },
            };

            await wrapper.vm.toggleQuickLook(event);

            expect(update_currently_previewed_item_mock).toHaveBeenCalledWith(
                expect.anything(),
                item,
            );
            expect(toggle_quick_look_mock).toHaveBeenCalledWith(expect.anything(), true);
        });

        it(`Given user toggle quicklook from an item to an other, the it displays the quick look of new item`, async () => {
            state.currently_previewed_item = {
                id: 105,
                title: "my previewed item",
            };

            state.current_folder = item;

            const wrapper = factory();
            const event = {
                details: { item },
            };
            await wrapper.vm.toggleQuickLook(event);

            expect(update_currently_previewed_item_mock).toHaveBeenCalledWith(
                expect.anything(),
                item,
            );
            expect(toggle_quick_look_mock).toHaveBeenCalledWith(expect.anything(), true);
        });

        it(`Given user toggle quick look, then it open quick look`, async () => {
            state.currently_previewed_item = item;

            state.current_folder = item;
            state.toggle_quick_look = false;

            const wrapper = factory();
            const event = {
                details: { item },
            };
            await wrapper.vm.toggleQuickLook(event);

            expect(update_currently_previewed_item_mock).toHaveBeenCalledWith(
                expect.anything(),
                item,
            );
            expect(toggle_quick_look_mock).toHaveBeenCalledWith(expect.anything(), true);
        });

        it(`Given user toggle quick look on a previewed item, then it closes quick look`, async () => {
            state.currently_previewed_item = item;

            state.current_folder = item;
            state.toggle_quick_look = true;

            const wrapper = factory();
            const event = {
                details: { item },
            };
            await wrapper.vm.toggleQuickLook(event);

            expect(toggle_quick_look_mock).toHaveBeenCalledWith(expect.anything(), false);
        });
    });

    describe("closeQuickLook", () => {
        it(`Given closed quick look is called on root_folder, then it calls the "root_folder" route`, () => {
            state.current_folder = {
                id: 25,
                parent_id: 0,
            };

            state.currently_previewed_item = item;

            const wrapper = factory();
            wrapper.vm.closeQuickLook();

            expect(replace).toHaveBeenCalledWith({ name: "root_folder" });
        });

        it(`Given closed quick look is called on a subtree item, then it calls the parent folder route`, () => {
            state.current_folder = {
                id: 25,
                parent_id: 100,
            };

            state.currently_previewed_item = item;

            const wrapper = factory();
            wrapper.vm.closeQuickLook();

            expect(replace).toHaveBeenCalledWith({ name: "folder", params: { item_id: 25 } });
        });
    });
});
