/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import TransitionModal from "./TransitionModal.vue";
import PreConditionsSkeleton from "./Skeletons/PreConditionsSkeleton.vue";
import * as tlp_modal from "@tuleap/tlp-modal";
import FilledPreConditionsSection from "./FilledPreConditionsSection.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests.js";

describe(`TransitionModal`, () => {
    let modal;
    let is_loading_modal, is_modal_shown, is_modal_save_running;

    const saveTransitionRulesMock = jest.fn();
    const clearModalShownMock = jest.fn();

    beforeEach(() => {
        is_loading_modal = false;
        is_modal_shown = false;
        is_modal_save_running = false;
    });

    const createWrapper = () =>
        shallowMount(TransitionModal, {
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        transitionModal: {
                            state: {
                                is_loading_modal,
                                is_modal_shown,
                                is_modal_save_running,
                            },
                            actions: {
                                saveTransitionRules: saveTransitionRulesMock,
                            },
                            mutations: {
                                clearModalShown: clearModalShownMock,
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
        });

    beforeEach(() => {
        modal = {
            addEventListener: jest.fn(),
            show: jest.fn(),
            hide: jest.fn(),
        };
        jest.spyOn(tlp_modal, "createModal").mockReturnValue(modal);
    });

    it(`when mounted(), it will create a TLP modal`, () => {
        const createModal = jest.spyOn(tlp_modal, "createModal");
        createWrapper();
        expect(createModal).toHaveBeenCalled();
    });

    it(`when the modal is hidden (through ESC or a close button),
        it will clear the modal shown flag`, () => {
        jest.spyOn(modal, "addEventListener").mockImplementation((event_name, callback) =>
            callback(),
        );
        createWrapper();

        expect(clearModalShownMock).toHaveBeenCalled();
    });

    it(`when the modal form is submitted,
        it will dispatch an action to save the transition rules`, () => {
        const wrapper = createWrapper();

        wrapper.trigger("submit");
        expect(saveTransitionRulesMock).toHaveBeenCalled();
    });

    it(`when the modal is loading, it will show a skeleton for Pre-conditions`, () => {
        is_loading_modal = true;
        const wrapper = createWrapper();

        expect(wrapper.findComponent(PreConditionsSkeleton).exists()).toBe(true);
    });

    it(`when the modal is loaded and shown, it will show the Pre-conditions section`, () => {
        is_modal_shown = true;
        const wrapper = createWrapper();

        expect(wrapper.findComponent(FilledPreConditionsSection).exists()).toBe(true);
    });

    describe(`when the modal is saving`, () => {
        let wrapper;
        beforeEach(() => {
            is_modal_save_running = true;
            wrapper = createWrapper();
        });

        it(`will disable the Cancel button`, () => {
            const cancel_button = wrapper.get("[data-test=cancel-button]");
            expect(cancel_button.attributes("disabled")).toBe("");
        });

        it(`will disable the "Save configuration" button`, () => {
            const save_button = wrapper.get("[data-test=save-button]");
            expect(save_button.attributes("disabled")).toBe("");
        });

        it(`will show a spinner icon on the "Save configuration" button`, () => {
            const save_spinner_icon = wrapper.get("[data-test=save-button-spinner]");
            expect(save_spinner_icon.exists()).toBe(true);
        });
    });
});
