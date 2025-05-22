/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import * as tlp_modal from "@tuleap/tlp-modal";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import GlobalErrorModal from "./GlobalErrorModal.vue";
import type { Modal } from "@tuleap/tlp-modal";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import type { ErrorState } from "../../../store/error/module";

let reset_error: vi.Mock;

function createWrapper(error_message: string): VueWrapper<InstanceType<typeof GlobalErrorModal>> {
    return shallowMount(GlobalErrorModal, {
        global: {
            ...getGlobalTestOptions({
                modules: {
                    error: {
                        state: {
                            global_modal_error_message: error_message,
                        } as unknown as ErrorState,
                        mutations: {
                            resetErrors: reset_error,
                        },
                        namespaced: true,
                    },
                },
            }),
        },
    });
}

describe(`GlobalErrorModal`, () => {
    beforeEach(() => {
        reset_error = vi.fn();
    });
    it(`shows the modal when mounted`, () => {
        const modal_show = vi.fn();
        vi.spyOn(tlp_modal, "createModal").mockImplementation(() => {
            return {
                show: modal_show,
                addEventListener: vi.fn(),
            } as unknown as Modal;
        });
        createWrapper("Full error message with details");
        expect(modal_show).toHaveBeenCalledTimes(1);
    });

    it(`displays more details when user clicks on show error`, async () => {
        vi.spyOn(tlp_modal, "createModal").mockImplementation(() => {
            return {
                show: vi.fn(),
                addEventListener: vi.fn(),
            } as unknown as Modal;
        });

        const error_message = "Full error message with details";
        const wrapper = createWrapper(error_message);

        await wrapper.get("[data-test=show-details]").trigger("click");

        const details = wrapper.get("[data-test=details]");
        expect(details.text()).toEqual(error_message);
    });

    it(`warns user that something is wrong without any details`, () => {
        vi.spyOn(tlp_modal, "createModal").mockImplementation(() => {
            return {
                show: vi.fn(),
                addEventListener: vi.fn(),
            } as unknown as Modal;
        });

        const wrapper = createWrapper("");
        expect(wrapper.find("[data-test=show-details]").exists()).toBe(false);
        expect(wrapper.find("[data-test=details]").exists()).toBe(false);
    });

    it(`when I hide the modal, it resets the error`, () => {
        vi.spyOn(tlp_modal, "createModal").mockImplementation(() => {
            return {
                show: vi.fn(),
                addEventListener: (event_name: string, handler: () => void) => handler(),
            } as unknown as Modal;
        });
        createWrapper("");

        expect(reset_error).toHaveBeenCalled();
    });

    it(`when I click on the "reload" button, it reloads the page`, () => {
        vi.spyOn(tlp_modal, "createModal").mockImplementation(() => {
            return {
                show: vi.fn(),
                addEventListener: vi.fn(),
            } as unknown as Modal;
        });

        const location = window.location;

        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        delete window.location;

        window.location = {
            reload: vi.fn(),
        } as unknown as Location;
        const wrapper = createWrapper("");
        wrapper.get("[data-test=reload]").trigger("click");

        expect(window.location.reload).toHaveBeenCalled();

        window.location = location;
    });
});
