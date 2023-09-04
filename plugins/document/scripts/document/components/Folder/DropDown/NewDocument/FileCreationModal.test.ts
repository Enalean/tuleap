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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { Modal } from "@tuleap/tlp-modal";
import * as tlp_modal from "@tuleap/tlp-modal";
import FileCreationModal from "./FileCreationModal.vue";
import type { Folder, RootState } from "../../../../type";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import type { ErrorState } from "../../../../store/error/module";
import { nextTick } from "vue";
import { TYPE_FILE } from "../../../../constants";

describe("FileCreationModal", () => {
    const add_event_listener = jest.fn();
    const modal_show = jest.fn();
    const remove_backdrop = jest.fn();
    let create_new_item: jest.Mock;
    let reset_error_modal: jest.Mock;

    function getWrapper(
        dropped_file: File,
        has_modal_error: boolean,
    ): VueWrapper<InstanceType<typeof FileCreationModal>> {
        return shallowMount(FileCreationModal, {
            props: {
                parent: { id: 12, title: "Dacia" },
                droppedFile: dropped_file,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        error: {
                            state: {
                                has_modal_error,
                            } as unknown as ErrorState,
                            namespaced: true,
                            mutations: {
                                resetModalError: reset_error_modal,
                            },
                        },
                        configuration: {
                            state: {
                                is_status_property_used: false,
                                filename_pattern: "",
                            } as unknown as ErrorState,
                            namespaced: true,
                        },
                    },
                    state: {
                        current_folder: { id: 13, title: "Limited Edition" } as Folder,
                    } as RootState,
                    actions: {
                        createNewItem: create_new_item,
                    },
                }),
            },
        });
    }

    beforeEach(() => {
        jest.spyOn(tlp_modal, "createModal").mockImplementation(() => {
            return {
                addEventListener: add_event_listener,
                show: modal_show,
                removeBackdrop: remove_backdrop,
            } as unknown as Modal;
        });

        create_new_item = jest.fn();
        reset_error_modal = jest.fn();
    });

    it("does not close the modal if there is an error during the creation", async () => {
        const dropped_file = new File([], "Duster Pikes Peak.lol");
        const wrapper = getWrapper(dropped_file, true);

        wrapper.get("form").trigger("submit");

        const expected_item = {
            title: "",
            description: "",
            type: TYPE_FILE,
            file_properties: {
                file: dropped_file,
            },
            status: "none",
        };

        await nextTick();
        expect(create_new_item).toHaveBeenCalledWith(expect.anything(), [
            expected_item,
            { id: 12, title: "Dacia" },
            { id: 13, title: "Limited Edition" },
        ]);

        expect(remove_backdrop).not.toHaveBeenCalled();
        expect(wrapper.emitted()).not.toHaveProperty("close-file-creation-modal");
    });

    it("Creates a new file document without error and hide the modal after creation", async () => {
        const dropped_file = new File([], "Duster Pikes Peak.lol");
        const wrapper = getWrapper(dropped_file, false);

        wrapper.get("form").trigger("submit");

        await nextTick();
        await nextTick();

        const expected_item = {
            title: "",
            description: "",
            type: TYPE_FILE,
            file_properties: {
                file: dropped_file,
            },
            status: "none",
        };

        expect(create_new_item).toHaveBeenCalledWith(expect.anything(), [
            expected_item,
            { id: 12, title: "Dacia" },
            { id: 13, title: "Limited Edition" },
        ]);
        expect(wrapper.emitted("close-file-creation-modal")).toBeTruthy();
    });
});
