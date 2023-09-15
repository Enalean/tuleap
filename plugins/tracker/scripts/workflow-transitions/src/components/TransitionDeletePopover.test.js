/*
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

import { mount } from "@vue/test-utils";
import * as tlp_popovers from "@tuleap/tlp-popovers";

import TransitionDeletePopover from "./TransitionDeletePopover.vue";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests.js";

jest.useFakeTimers();

describe("TransitionDeletePopover", () => {
    let destroyPopover, transition, deleteTransition, is_transition_updated, create_popover_spy;

    beforeEach(() => {
        destroyPopover = jest.fn();
        create_popover_spy = jest.spyOn(tlp_popovers, "createPopover").mockImplementation(() => ({
            destroy: destroyPopover,
        }));

        transition = {
            from_id: 18,
            to_id: 83,
        };
        deleteTransition = jest.fn();
        is_transition_updated = false;
    });

    const getWrapper = () => {
        return mount(TransitionDeletePopover, {
            global: {
                ...getGlobalTestOptions({
                    state: { is_operation_running: false },
                    getters: {
                        current_workflow_transitions: () => [],
                        is_workflow_advanced: () => false,
                    },
                }),
            },
            propsData: {
                transition,
                deleteTransition,
                is_transition_updated,
                is_confirmation_needed: true,
            },
        });
    };

    const confirm_delete_transition_selector = '[data-test-action="confirm-delete-transition"]';

    it("will create a popover", async () => {
        getWrapper();
        await jest.runOnlyPendingTimersAsync();
        expect(create_popover_spy).toHaveBeenCalled();
    });

    it("will destroy its popover on destroy", async () => {
        const wrapper = getWrapper();
        await jest.runOnlyPendingTimersAsync();
        wrapper.unmount();
        await jest.runOnlyPendingTimersAsync();

        expect(destroyPopover).toHaveBeenCalled();
    });

    it("shows an animation when the transition has just been updated", async () => {
        const wrapper = getWrapper();
        wrapper.setProps({ is_transition_updated: true });
        await jest.runOnlyPendingTimersAsync();

        const confirm_button = wrapper.get(confirm_delete_transition_selector);
        expect(confirm_button.classes()).toContain("tracker-workflow-transition-action-updated");
    });
});
