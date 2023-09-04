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
import { createLocalVueForTests } from "../../support/local-vue.js";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import TransitionModal from "./TransitionModal.vue";
import PreConditionsSkeleton from "./Skeletons/PreConditionsSkeleton.vue";
import storeOptions from "../../store/transition-modal/module.js";
import * as tlp_modal from "@tuleap/tlp-modal";
import FilledPreConditionsSection from "./FilledPreConditionsSection.vue";

describe(`TransitionModal`, () => {
    let modal;
    function mockStore() {
        const store = createStoreMock({
            state: {
                transitionModal: storeOptions,
            },
        });
        // eslint-disable-next-line jest/prefer-spy-on
        store.watch = jest.fn();
        return store;
    }

    const createWrapper = async (store) =>
        shallowMount(TransitionModal, {
            localVue: await createLocalVueForTests(),
            mocks: { $store: store },
        });

    beforeEach(() => {
        modal = {
            addEventListener: jest.fn(),
            show: jest.fn(),
            hide: jest.fn(),
        };
        jest.spyOn(tlp_modal, "createModal").mockReturnValue(modal);
    });

    it(`when mounted(), it will create a TLP modal`, async () => {
        const createModal = jest.spyOn(tlp_modal, "createModal");
        await createWrapper(mockStore());
        expect(createModal).toHaveBeenCalled();
    });

    it(`when the modal is hidden (through ESC or a close button),
        it will clear the modal shown flag`, async () => {
        jest.spyOn(modal, "addEventListener").mockImplementation((event_name, callback) =>
            callback(),
        );
        const store = mockStore();
        await createWrapper(store);

        expect(store.commit).toHaveBeenCalledWith("transitionModal/clearModalShown");
    });

    it(`when the modal shown flag becomes true, it will show the modal`, async () => {
        const showModal = jest.spyOn(modal, "show");
        const store = mockStore();
        jest.spyOn(store, "watch").mockImplementation((watchFunction, callback) => callback(true));
        await createWrapper(store);

        expect(showModal).toHaveBeenCalled();
    });

    it(`when the modal shown flag becomes false, it will hide the modal`, async () => {
        const hideModal = jest.spyOn(modal, "hide");
        const store = mockStore();
        jest.spyOn(store, "watch").mockImplementation((watchFunction, callback) => callback(false));
        await createWrapper(store);

        expect(hideModal).toHaveBeenCalled();
    });

    it(`when the modal form is submitted,
        it will dispatch an action to save the transition rules`, async () => {
        const store = mockStore();
        const wrapper = await createWrapper(store);

        wrapper.trigger("submit");
        expect(store.dispatch).toHaveBeenCalledWith("transitionModal/saveTransitionRules");
    });

    it(`when the modal is loading, it will show a skeleton for Pre-conditions`, async () => {
        const store = mockStore();
        store.state.transitionModal.is_loading_modal = true;
        const wrapper = await createWrapper(store);

        expect(wrapper.findComponent(PreConditionsSkeleton).exists()).toBe(true);
    });

    it(`when the modal is loaded and shown, it will show the Pre-conditions section`, async () => {
        const store = mockStore();
        store.state.transitionModal.is_loading_modal = false;
        store.state.transitionModal.is_modal_shown = true;
        const wrapper = await createWrapper(store);

        expect(wrapper.findComponent(FilledPreConditionsSection).exists()).toBe(true);
    });

    describe(`when the modal is saving`, () => {
        let wrapper;
        beforeEach(async () => {
            const store = mockStore();
            store.state.transitionModal.is_modal_save_running = true;
            wrapper = await createWrapper(store);
        });

        it(`will disable the Cancel button`, () => {
            const cancel_button = wrapper.get("[data-test=cancel-button]");
            expect(cancel_button.attributes("disabled")).toBe("disabled");
        });

        it(`will disable the "Save configuration" button`, () => {
            const save_button = wrapper.get("[data-test=save-button]");
            expect(save_button.attributes("disabled")).toBe("disabled");
        });

        it(`will show a spinner icon on the "Save configuration" button`, () => {
            const save_spinner_icon = wrapper.get("[data-test=save-button-spinner]");
            expect(save_spinner_icon.exists()).toBe(true);
        });
    });
});
