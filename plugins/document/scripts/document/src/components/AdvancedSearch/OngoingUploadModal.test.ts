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
import type { Mock } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import GlobalUploadProgressBar from "../Folder/ProgressBar/GlobalUploadProgressBar.vue";
import OngoingUploadModal from "./OngoingUploadModal.vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { Modal } from "@tuleap/tlp-modal";
import type { ItemFile, RootState } from "../../type";
import { EVENT_TLP_MODAL_HIDDEN } from "@tuleap/tlp-modal";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import * as tlp_modal from "@tuleap/tlp-modal";

describe("OngoingUploadModal", () => {
    let modal: Modal, addEventListener: vi.SpyInstance, show: vi.SpyInstance;
    let createModal: Mock;

    function getWrapper(
        files_uploads_list: Array<ItemFile>,
    ): VueWrapper<InstanceType<typeof OngoingUploadModal>> {
        return shallowMount(OngoingUploadModal, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        files_uploads_list,
                    } as RootState,
                }),
            },
        });
    }

    beforeEach(() => {
        show = vi.fn();
        addEventListener = vi.fn();

        modal = {
            show,
            addEventListener,
            removeEventListener: vi.fn(),
        } as unknown as Modal;

        createModal = vi.spyOn(tlp_modal, "createModal");
        createModal.mockImplementation(() => {
            return modal;
        });
    });

    it("should display a blocking modal, with no way to escape it", () => {
        getWrapper([]);

        expect(createModal).toHaveBeenCalledWith(expect.anything(), {
            keyboard: false,
            dismiss_on_backdrop_click: false,
            destroy_on_hide: true,
        });
        expect(show).toHaveBeenCalled();
    });

    it("should warn parent component that the modal has been closed so that it can reopen it again if needed", () => {
        let close_callback = (): void => {
            // do nothing
        };
        addEventListener.mockImplementation((event_type, callback) => {
            if (EVENT_TLP_MODAL_HIDDEN === event_type) {
                close_callback = callback;
            }
        });

        const wrapper = getWrapper([]);

        if (close_callback === null) {
            throw Error("No close callback to close the modal");
        }
        close_callback();

        expect(wrapper.emitted().close).toBeTruthy();
    });

    it("should display the percentage of uploading files", () => {
        const wrapper = getWrapper([
            {
                progress: 50,
                upload_error: null,
            } as ItemFile,
            {
                progress: 10,
                upload_error: null,
            } as ItemFile,
        ]);

        const progress_bar = wrapper.findComponent(GlobalUploadProgressBar);
        expect(progress_bar.props().progress).toBe(30);
        expect(progress_bar.props().nb_uploads_in_error).toBe(0);
    });

    it("should indicate that there is an error", () => {
        const wrapper = getWrapper([
            {
                progress: 50,
                upload_error: null,
            } as ItemFile,
            {
                progress: 10,
                upload_error: "Lorem ipsum",
            } as ItemFile,
        ]);

        const progress_bar = wrapper.findComponent(GlobalUploadProgressBar);
        expect(progress_bar.props().progress).toBe(30);
        expect(progress_bar.props().nb_uploads_in_error).toBe(1);
        expect(wrapper.text()).toContain("Lorem ipsum");
    });

    it(`should keep the continue button has disabled when there isn't any upload`, () => {
        const wrapper = getWrapper([]);

        expect(
            wrapper.find<HTMLButtonElement>("[data-test=continue-button]").element.disabled,
        ).toBe(true);
    });

    it(`should keep the continue button has disabled when the upload is occurring`, () => {
        const wrapper = getWrapper([
            {
                progress: 100,
                upload_error: null,
            } as ItemFile,
        ]);

        expect(
            wrapper.find<HTMLButtonElement>("[data-test=continue-button]").element.disabled,
        ).toBe(true);
    });

    it(`should keep the continue button has disabled when the upload is complete`, () => {
        const wrapper = getWrapper([
            {
                progress: 100,
                upload_error: null,
            } as ItemFile,
        ]);

        expect(
            wrapper.find<HTMLButtonElement>("[data-test=continue-button]").element.disabled,
        ).toBe(true);
    });

    it(`should enable the button when there is an error`, () => {
        const wrapper = getWrapper([
            {
                progress: 50,
                upload_error: "Lorem ipsum",
            } as ItemFile,
        ]);

        expect(
            wrapper.find<HTMLButtonElement>("[data-test=continue-button]").element.disabled,
        ).toBe(false);
    });

    it("should keep the continue button as disabled if there is an error but another file is still being uploaded", () => {
        const wrapper = getWrapper([
            {
                progress: 50,
                upload_error: null,
            } as ItemFile,
            {
                progress: 10,
                upload_error: "Lorem ipsum",
            } as ItemFile,
        ]);

        expect(
            wrapper.find<HTMLButtonElement>("[data-test=continue-button]").element.disabled,
        ).toBe(true);
    });

    it("should enable the continue button if there is an error and all files are uploaded", () => {
        const wrapper = getWrapper([
            {
                progress: 100,
                upload_error: null,
            } as ItemFile,
            {
                progress: 10,
                upload_error: "Lorem ipsum",
            } as ItemFile,
        ]);

        expect(
            wrapper.find<HTMLButtonElement>("[data-test=continue-button]").element.disabled,
        ).toBe(false);
    });
});
