/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import ConfigurationModal from "@/components/configuration/ConfigurationModal.vue";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import { createGettext } from "vue3-gettext";
import SuccessFeedback from "@/components/configuration/SuccessFeedback.vue";
import ErrorFeedback from "@/components/configuration/ErrorFeedback.vue";

vi.mock("@tuleap/vue-strict-inject");

describe("ConfigurationModal", () => {
    it("should display error if there is no allowed trackers", () => {
        vi.spyOn(strict_inject, "strictInject").mockReturnValue(
            ConfigurationStoreStub.withoutAllowedTrackers(),
        );

        const wrapper = shallowMount(ConfigurationModal, {
            global: { plugins: [createGettext({ silent: true })] },
        });

        expect(
            wrapper.find("[data-test=artidoc-configuration-modal-form-element-trackers]").classes(),
        ).toContain("tlp-form-element-error");
    });

    it("should display submit button as disabled if user do not change the selected tracker", async () => {
        vi.spyOn(strict_inject, "strictInject").mockReturnValue(
            ConfigurationStoreStub.withSelectedTracker(ConfigurationStoreStub.bugs.id),
        );

        const wrapper = shallowMount(ConfigurationModal, {
            global: { plugins: [createGettext({ silent: true })] },
        });

        const submit_button = wrapper.find("[data-test=submit]");
        expect(submit_button.attributes("disabled")).toBe("");

        await wrapper.find("select").setValue(ConfigurationStoreStub.tasks.id);

        expect(submit_button.attributes("disabled")).toBeUndefined();
    });

    it("should display submit button as disabled if saving is in progress", () => {
        vi.spyOn(strict_inject, "strictInject").mockReturnValue(
            ConfigurationStoreStub.withSavingInProgress(),
        );

        const wrapper = shallowMount(ConfigurationModal, {
            global: { plugins: [createGettext({ silent: true })] },
        });

        const submit_button = wrapper.find("[data-test=submit]");
        expect(submit_button.attributes("disabled")).toBe("");
    });

    it("should display success feedback", () => {
        vi.spyOn(strict_inject, "strictInject").mockReturnValue(
            ConfigurationStoreStub.withSuccessfullSave(),
        );

        const wrapper = shallowMount(ConfigurationModal, {
            global: { plugins: [createGettext({ silent: true })] },
        });

        expect(wrapper.findComponent(SuccessFeedback).exists()).toBe(true);
        expect(wrapper.findComponent(ErrorFeedback).exists()).toBe(false);
    });

    it("should display error feedback", () => {
        vi.spyOn(strict_inject, "strictInject").mockReturnValue(ConfigurationStoreStub.withError());

        const wrapper = shallowMount(ConfigurationModal, {
            global: { plugins: [createGettext({ silent: true })] },
        });

        expect(wrapper.findComponent(SuccessFeedback).exists()).toBe(false);
        expect(wrapper.findComponent(ErrorFeedback).exists()).toBe(true);
    });
});
