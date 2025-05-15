/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import { shallowMount } from "@vue/test-utils";
import ModalSizeThresholdExceeded from "./ModalMaxArchiveSizeThresholdExceeded.vue";
import * as tlp_modal from "@tuleap/tlp-modal";
import type { Modal } from "@tuleap/tlp-modal";
import { EVENT_TLP_MODAL_HIDDEN } from "@tuleap/tlp-modal";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";

describe("ModalSizeThresholdExceeded", () => {
    function getWrapper(): VueWrapper<InstanceType<typeof ModalSizeThresholdExceeded>> {
        return shallowMount(ModalSizeThresholdExceeded, {
            props: {
                size: 1050000,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: { max_archive_size: 1 },
                            namespaced: true,
                        },
                    },
                }),
            },
        });
    }

    let fake_modal: Modal;
    let close_callback: () => void;

    beforeEach(() => {
        fake_modal = {
            addEventListener: (event: string, callback: () => void) => {
                if (event === EVENT_TLP_MODAL_HIDDEN) {
                    close_callback = callback;
                }
            },
            show: jest.fn(),
            hide: jest.fn(),
        } as unknown as Modal;
        jest.spyOn(tlp_modal, "createModal").mockReturnValue(fake_modal);
    });

    it("shows itself when it is mounted", () => {
        getWrapper();

        expect(fake_modal.show).toHaveBeenCalled();
    });

    it("displays the size of the folder in MB", () => {
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=download-as-zip-folder-size]").html()).toContain("1.05 MB");
    });

    it("Emits an event when it is closed", () => {
        const wrapper = getWrapper();

        expect(
            wrapper
                .find("[data-test=close-max-archive-size-threshold-exceeded-modal]")
                .attributes("data-dismiss"),
        ).toBe("modal");
        close_callback();

        expect(wrapper.emitted("download-as-zip-modal-closed")?.length).toBe(1);
    });
});
