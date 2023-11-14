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

import { beforeEach, describe, expect, it, jest } from "@jest/globals";
import { shallowMount } from "@vue/test-utils";
import { createTestingPinia } from "@pinia/testing";
import { useRootStore } from "../../stores/root";
import SwitchToFilter from "./SwitchToFilter.vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import type { Project } from "../../type";
import { ARE_RESTRICTED_USERS_ALLOWED } from "../../injection-keys";
import { useKeyboardNavigationStore } from "../../stores/keyboard-navigation";
import type { KeyboardNavigationState } from "../../stores/type";

jest.useFakeTimers();

describe("SwitchToFilter", () => {
    let modal: Modal;
    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        modal = createModal(doc.createElement("div"));
    });

    it("Saves the entered value in the store", async () => {
        const wrapper = shallowMount(SwitchToFilter, {
            global: getGlobalTestOptions(),
            props: {
                modal,
            },
        });

        if (wrapper.element instanceof HTMLInputElement) {
            wrapper.element.value = "abc";
        }
        await wrapper.trigger("keyup");

        expect(useRootStore().updateFilterValue).toHaveBeenCalledWith("abc");
    });

    it("Reset the value if the modal is closed", () => {
        shallowMount(SwitchToFilter, {
            props: {
                modal,
            },
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        root: {
                            filter_value: "abc",
                        },
                    },
                }),
            ),
        });
        modal.hide();

        // There is a TRANSITION_DURATION before listeners are awakened
        jest.advanceTimersByTime(300);

        expect(useRootStore().updateFilterValue).toHaveBeenCalledWith("");
    });

    it("Closes the modal if the user hit [esc]", async () => {
        const hide = jest.spyOn(modal, "hide");

        const wrapper = shallowMount(SwitchToFilter, {
            props: {
                modal,
            },
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        root: {
                            filter_value: "abc",
                        },
                    },
                }),
            ),
        });

        await wrapper.trigger("keyup", { key: "Escape" });

        expect(useRootStore().updateFilterValue).toHaveBeenCalledWith("");
        expect(hide).toHaveBeenCalled();
    });

    it("Changes the focus with arrow down key", async () => {
        const wrapper = shallowMount(SwitchToFilter, {
            props: {
                modal,
            },
            global: getGlobalTestOptions(
                createTestingPinia({
                    initialState: {
                        root: {
                            filter_value: "abc",
                        },
                    },
                }),
            ),
        });

        await wrapper.trigger("keyup", { key: "ArrowDown" });

        expect(useKeyboardNavigationStore().changeFocusFromFilterInput).toHaveBeenCalled();
    });

    it("Forces the focus from the outside", async () => {
        const project = {
            is_public: true,
            project_name: "Guinea Pig",
            project_uri: "/pojects/gpig",
        } as Project;

        const wrapper = shallowMount(SwitchToFilter, {
            props: {
                modal,
            },
            global: {
                ...getGlobalTestOptions(
                    createTestingPinia({
                        initialState: {
                            "keyboard-navigation": {
                                programmatically_focused_element: project,
                            } as KeyboardNavigationState,
                        },
                    }),
                ),
                provide: {
                    [ARE_RESTRICTED_USERS_ALLOWED as symbol]: true,
                },
            },
        });

        const input = wrapper.find("[data-test=switch-to-filter]");
        if (!(input.element instanceof HTMLInputElement)) {
            throw Error("Unable to find the input");
        }

        const focus = jest.spyOn(input.element, "focus");

        await useKeyboardNavigationStore().$patch({ programmatically_focused_element: null });

        expect(focus).toHaveBeenCalled();
    });
});
