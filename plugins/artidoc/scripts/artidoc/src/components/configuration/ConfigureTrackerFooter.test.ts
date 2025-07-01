/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import SuccessFeedback from "@/components/configuration/SuccessFeedback.vue";
import ErrorFeedback from "@/components/configuration/ErrorFeedback.vue";
import ConfigureTrackerFooter from "@/components/configuration/ConfigureTrackerFooter.vue";
import { CLOSE_CONFIGURATION_MODAL } from "@/components/configuration/configuration-modal";
import { SelectedTrackerStub } from "@/helpers/stubs/SelectedTrackerStub";
import type { SaveTrackerConfiguration } from "@/configuration/TrackerConfigurationSaver";
import { SaveTrackerConfigurationStub } from "@/configuration/stubs/SaveTrackerConfigurationStub";
import {
    ALLOWED_TRACKERS,
    buildAllowedTrackersCollection,
} from "@/configuration/AllowedTrackersCollection";
import { SELECTED_TRACKER } from "@/configuration/SelectedTracker";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";

describe("ConfigureTrackerFooter", () => {
    let closeModal: () => void;

    beforeEach(() => {
        closeModal = vi.fn();
    });

    const getWrapper = (configuration_saver: SaveTrackerConfiguration): VueWrapper => {
        return shallowMount(ConfigureTrackerFooter, {
            props: {
                new_selected_tracker: SelectedTrackerStub.withTracker(TrackerStub.build(12, "Epic"))
                    .value,
                configuration_saver,
            },
            global: {
                provide: {
                    [CLOSE_CONFIGURATION_MODAL.valueOf()]: closeModal,
                    [ALLOWED_TRACKERS.valueOf()]: buildAllowedTrackersCollection([
                        TrackerStub.withTitleAndDescription(),
                    ]),
                    [SELECTED_TRACKER.valueOf()]: SelectedTrackerStub.withTracker(
                        TrackerStub.build(13, "Story"),
                    ),
                },
                plugins: [createGettext({ silent: true })],
            },
        });
    };

    it("should display success feedback", async () => {
        const wrapper = getWrapper(SaveTrackerConfigurationStub.buildSuccess());
        await wrapper.get("[data-test=submit]").trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(SuccessFeedback).exists()).toBe(true);
        expect(wrapper.findComponent(ErrorFeedback).exists()).toBe(false);
    });

    it("should display error feedback", async () => {
        const wrapper = getWrapper(SaveTrackerConfigurationStub.buildError());
        await wrapper.get("[data-test=submit]").trigger("click");

        expect(wrapper.findComponent(SuccessFeedback).exists()).toBe(false);
        expect(wrapper.findComponent(ErrorFeedback).exists()).toBe(true);
    });

    it("When the cancel button is clicked, then it should execute the closeModal() callback", async () => {
        const wrapper = getWrapper(SaveTrackerConfigurationStub.buildSuccess());
        await wrapper.get("[data-test=cancel-modal-button]").trigger("click");

        expect(closeModal).toHaveBeenCalledOnce();
    });

    it("When the close button is clicked, then it should execute the closeModal() callback", async () => {
        const wrapper = getWrapper(SaveTrackerConfigurationStub.buildSuccess());
        await wrapper.get("[data-test=submit]").trigger("click");
        await wrapper.get("[data-test=close-modal-after-success]").trigger("click");

        expect(closeModal).toHaveBeenCalledOnce();
    });

    it("When the submit button is clicked, then it should emit the after-save event", async () => {
        const wrapper = getWrapper(SaveTrackerConfigurationStub.buildSuccess());

        await wrapper.get("[data-test=submit]").trigger("click");

        expect(wrapper.emitted()).toHaveProperty("after-save");
    });
});
