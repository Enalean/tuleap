/*
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../../helpers/local-vue";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import type { Modal } from "tlp";
import * as tlp from "tlp";
import FileCreationModal from "./FileCreationModal.vue";
import { TYPE_FILE } from "../../../../constants";
import type { State } from "../../../../type";

jest.mock("tlp");

describe("FileVersionChangelogModal", () => {
    const add_event_listener = jest.fn();
    const modal_show = jest.fn();
    const remove_backdrop = jest.fn();

    function getWrapper(dropped_file: File, has_modal_error: boolean): Wrapper<FileCreationModal> {
        const state = {
            current_folder: { id: 13, title: "Limited Edition" },
            error: { has_modal_error },
        } as unknown as State;
        const store_option = { state };
        const store = createStoreMock(store_option);

        return shallowMount(FileCreationModal, {
            localVue,
            propsData: {
                parent: { id: 12, title: "Dacia" },
                droppedFile: dropped_file,
            },
            mocks: { $store: store },
        });
    }

    beforeEach(() => {
        jest.spyOn(tlp, "createModal").mockImplementation(() => {
            return {
                addEventListener: add_event_listener,
                show: modal_show,
                removeBackdrop: remove_backdrop,
            } as unknown as Modal;
        });
    });

    it("does not close the modal if there is an error during the creation", async () => {
        const dropped_file = new File([], "Duster Pikes Peak.lol");
        const wrapper = getWrapper(dropped_file, true);
        wrapper.setData({
            item: {
                title: "Faaaast",
                description: "It's fast",
                type: TYPE_FILE,
                file_properties: {
                    file: {},
                },
            },
        });

        const expected_item = {
            title: "Faaaast",
            description: "It's fast",
            type: TYPE_FILE,
            file_properties: {
                file: dropped_file,
            },
        };
        wrapper.get("form").trigger("submit");

        await wrapper.vm.$nextTick();
        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("createNewItem", [
            expected_item,
            { id: 12, title: "Dacia" },
            { id: 13, title: "Limited Edition" },
        ]);

        expect(remove_backdrop).not.toHaveBeenCalled();
        expect(wrapper.emitted()).not.toHaveProperty("close-file-creation-modal");
        expect(wrapper.vm.$data.item).toMatchObject(expected_item);
    });

    it("Creates a new file document without error and hide the modal after creation", async () => {
        const dropped_file = new File([], "Duster Pikes Peak.lol");
        const wrapper = getWrapper(dropped_file, false);
        wrapper.setData({
            item: {
                title: "Faaaast",
                description: "It's fast",
                type: TYPE_FILE,
                file_properties: {
                    file: {},
                },
            },
        });

        const expected_item = {
            title: "Faaaast",
            description: "It's fast",
            type: TYPE_FILE,
            file_properties: {
                file: dropped_file,
            },
        };
        wrapper.get("form").trigger("submit");

        await wrapper.vm.$nextTick();
        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("createNewItem", [
            expected_item,
            { id: 12, title: "Dacia" },
            { id: 13, title: "Limited Edition" },
        ]);
        expect(wrapper.emitted()).toHaveProperty("close-file-creation-modal");
        const default_item = {
            title: "",
            description: "",
            type: TYPE_FILE,
            file_properties: {
                file: {},
            },
        };
        expect(wrapper.vm.$data.item).toMatchObject(default_item);
    });
});
