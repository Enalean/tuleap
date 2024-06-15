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
import AddNewSectionButton from "@/components/AddNewSectionButton.vue";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import { mockStrictInject } from "@/helpers/mock-strict-inject";
import {
    OPEN_CONFIGURATION_MODAL_BUS,
    useOpenConfigurationModalBus,
} from "@/composables/useOpenConfigurationModalBus";
import { createGettext } from "vue3-gettext";
import { AT_THE_END } from "@/stores/useSectionsStore";

describe("AddNewSectionButton", () => {
    describe("when the tracker is not configured", () => {
        it("should ask to open the configuration modal on click", async () => {
            let has_been_called = false;

            const bus = useOpenConfigurationModalBus();
            bus.registerHandler(() => {
                has_been_called = true;
            });

            mockStrictInject([
                [CONFIGURATION_STORE, ConfigurationStoreStub.withSelectedTracker(null)],
                [OPEN_CONFIGURATION_MODAL_BUS, bus],
            ]);

            const insert_section_callback = vi.fn();

            const wrapper = shallowMount(AddNewSectionButton, {
                props: { position: AT_THE_END, insert_section_callback },
                global: { plugins: [createGettext({ silent: true })] },
            });

            await wrapper.find("button").trigger("click");

            expect(has_been_called).toBe(true);
            expect(insert_section_callback).not.toHaveBeenCalled();
        });
    });

    describe("when the tracker is configured but user cannot submit title or description", () => {
        it("should ask to open the configuration modal on click when title is not submittable", async () => {
            let has_been_called = false;

            const bus = useOpenConfigurationModalBus();
            bus.registerHandler(() => {
                has_been_called = true;
            });

            mockStrictInject([
                [
                    CONFIGURATION_STORE,
                    ConfigurationStoreStub.withSelectedTracker({
                        ...ConfigurationStoreStub.bugs,
                        title: null,
                        description: {
                            field_id: 1002,
                            label: "Description",
                            type: "text",
                        },
                    }),
                ],
                [OPEN_CONFIGURATION_MODAL_BUS, bus],
            ]);

            const insert_section_callback = vi.fn();

            const wrapper = shallowMount(AddNewSectionButton, {
                props: { position: AT_THE_END, insert_section_callback },
                global: { plugins: [createGettext({ silent: true })] },
            });

            await wrapper.find("button").trigger("click");

            expect(has_been_called).toBe(true);
            expect(insert_section_callback).not.toHaveBeenCalled();
        });

        it("should ask to open the configuration modal on click when description is not submittable", async () => {
            let has_been_called = false;

            const bus = useOpenConfigurationModalBus();
            bus.registerHandler(() => {
                has_been_called = true;
            });

            mockStrictInject([
                [
                    CONFIGURATION_STORE,
                    ConfigurationStoreStub.withSelectedTracker({
                        ...ConfigurationStoreStub.bugs,
                        title: {
                            field_id: 1001,
                            label: "Summary",
                            type: "string",
                        },
                        description: null,
                    }),
                ],
                [OPEN_CONFIGURATION_MODAL_BUS, bus],
            ]);

            const insert_section_callback = vi.fn();

            const wrapper = shallowMount(AddNewSectionButton, {
                props: { position: AT_THE_END, insert_section_callback },
                global: { plugins: [createGettext({ silent: true })] },
            });

            await wrapper.find("button").trigger("click");

            expect(has_been_called).toBe(true);
            expect(insert_section_callback).not.toHaveBeenCalled();
        });
    });

    describe("when tracker is configured and user can submit new section", () => {
        it("should insert a pending artifact section", async () => {
            let has_modal_been_opened = false;

            const bus = useOpenConfigurationModalBus();
            bus.registerHandler(() => {
                has_modal_been_opened = true;
            });

            mockStrictInject([
                [
                    CONFIGURATION_STORE,
                    ConfigurationStoreStub.withSelectedTracker({
                        ...ConfigurationStoreStub.bugs,
                        title: {
                            field_id: 1001,
                            label: "Summary",
                            type: "string",
                        },
                        description: {
                            field_id: 1002,
                            label: "Description",
                            type: "text",
                        },
                    }),
                ],
                [OPEN_CONFIGURATION_MODAL_BUS, bus],
            ]);

            const insert_section_callback = vi.fn();

            const wrapper = shallowMount(AddNewSectionButton, {
                props: { position: AT_THE_END, insert_section_callback },
                global: { plugins: [createGettext({ silent: true })] },
            });

            await wrapper.find("button").trigger("click");

            expect(has_modal_been_opened).toBe(false);
            expect(insert_section_callback).toHaveBeenCalled();
        });
    });
});
