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

import type { Mock } from "vitest";
import { describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import AddNewSectionButton from "@/components/AddNewSectionButton.vue";
import type { ConfigurationStore } from "@/stores/configuration-store";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import type { OpenConfigurationModalBusStore } from "@/stores/useOpenConfigurationModalBusStore";
import {
    OPEN_CONFIGURATION_MODAL_BUS,
    useOpenConfigurationModalBusStore,
} from "@/stores/useOpenConfigurationModalBusStore";
import { createGettext } from "vue3-gettext";
import { AT_THE_END } from "@/stores/useSectionsStore";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";
import type { OpenAddExistingSectionModalBus } from "@/composables/useOpenAddExistingSectionModalBus";
import {
    OPEN_ADD_EXISTING_SECTION_MODAL_BUS,
    useOpenAddExistingSectionModalBus,
} from "@/composables/useOpenAddExistingSectionModalBus";

vi.mock("@tuleap/tlp-dropdown");

describe("AddNewSectionButton", () => {
    function getWrapper(
        insert_section_callback: Mock,
        configuration_store: ConfigurationStore,
        configuration_bus: OpenConfigurationModalBusStore,
        add_existing_section_bus: OpenAddExistingSectionModalBus,
    ): VueWrapper {
        return shallowMount(AddNewSectionButton, {
            props: { position: AT_THE_END, insert_section_callback },
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [CONFIGURATION_STORE.valueOf()]: configuration_store,
                    [OPEN_CONFIGURATION_MODAL_BUS.valueOf()]: configuration_bus,
                    [OPEN_ADD_EXISTING_SECTION_MODAL_BUS.valueOf()]: add_existing_section_bus,
                },
            },
        });
    }

    describe("when the tracker is not configured", () => {
        it("should ask to open the configuration modal on click", async () => {
            let has_modal_been_opened = false;

            const add_existing_section_bus = useOpenAddExistingSectionModalBus();
            const configuration_bus = useOpenConfigurationModalBusStore();
            configuration_bus.registerHandler(() => {
                has_modal_been_opened = true;
            });

            const insert_section_callback = vi.fn();

            const wrapper = getWrapper(
                insert_section_callback,
                ConfigurationStoreStub.withSelectedTracker(null),
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-new-section]").trigger("click");

            expect(has_modal_been_opened).toBe(true);
            expect(insert_section_callback).not.toHaveBeenCalled();
        });

        it("should insert a pending artifact section after the configuration is saved", async () => {
            const add_existing_section_bus = useOpenAddExistingSectionModalBus();
            const configuration_bus = useOpenConfigurationModalBusStore();
            const store = ConfigurationStoreStub.withSelectedTracker(null);

            let has_modal_been_opened = false;
            configuration_bus.registerHandler((onSuccessfulSaved: () => void) => {
                has_modal_been_opened = true;
                store.selected_tracker.value = TrackerStub.withTitleAndDescription();
                onSuccessfulSaved();
            });

            const insert_section_callback = vi.fn();

            const wrapper = getWrapper(
                insert_section_callback,
                store,
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-new-section]").trigger("click");

            expect(has_modal_been_opened).toBe(true);
            expect(insert_section_callback).toHaveBeenCalled();
        });
    });

    describe("when the tracker is configured but user cannot submit title", () => {
        it("should ask to open the configuration modal on click when title is not submittable", async () => {
            let has_modal_been_opened = false;

            const add_existing_section_bus = useOpenAddExistingSectionModalBus();
            const configuration_bus = useOpenConfigurationModalBusStore();
            configuration_bus.registerHandler(() => {
                has_modal_been_opened = true;
            });
            const store = ConfigurationStoreStub.withSelectedTracker({
                ...ConfigurationStoreStub.bugs,
                title: null,
                description: {
                    field_id: 1002,
                    label: "Description",
                    type: "text",
                    default_value: { format: "html", content: "" },
                },
            });

            const insert_section_callback = vi.fn();

            const wrapper = getWrapper(
                insert_section_callback,
                store,
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-new-section]").trigger("click");

            expect(has_modal_been_opened).toBe(true);
            expect(insert_section_callback).not.toHaveBeenCalled();
        });

        it("should insert a pending artifact section after the configuration is saved", async () => {
            const add_existing_section_bus = useOpenAddExistingSectionModalBus();
            const configuration_bus = useOpenConfigurationModalBusStore();
            const store = ConfigurationStoreStub.withSelectedTracker({
                ...ConfigurationStoreStub.bugs,
                title: null,
                description: {
                    field_id: 1002,
                    label: "Description",
                    type: "text",
                    default_value: { format: "html", content: "" },
                },
            });

            let has_modal_been_opened = false;
            configuration_bus.registerHandler((onSuccessfulSaved: () => void) => {
                has_modal_been_opened = true;
                store.selected_tracker.value = TrackerStub.withTitleAndDescription();
                onSuccessfulSaved();
            });

            const insert_section_callback = vi.fn();

            const wrapper = getWrapper(
                insert_section_callback,
                store,
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-new-section]").trigger("click");

            expect(has_modal_been_opened).toBe(true);
            expect(insert_section_callback).toHaveBeenCalled();
        });
    });

    describe("when the tracker is configured but user cannot submit description", () => {
        it("should ask to open the configuration modal on click when description is not submittable", async () => {
            let has_modal_been_opened = false;

            const add_existing_section_bus = useOpenAddExistingSectionModalBus();
            const configuration_bus = useOpenConfigurationModalBusStore();
            configuration_bus.registerHandler(() => {
                has_modal_been_opened = true;
            });
            const store = ConfigurationStoreStub.withSelectedTracker({
                ...ConfigurationStoreStub.bugs,
                title: {
                    field_id: 1001,
                    label: "Summary",
                    type: "string",
                    default_value: "",
                },
                description: null,
            });

            const insert_section_callback = vi.fn();

            const wrapper = getWrapper(
                insert_section_callback,
                store,
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-new-section]").trigger("click");

            expect(has_modal_been_opened).toBe(true);
            expect(insert_section_callback).not.toHaveBeenCalled();
        });

        it("should insert a pending artifact section after the configuration is saved", async () => {
            const add_existing_section_bus = useOpenAddExistingSectionModalBus();
            const configuration_bus = useOpenConfigurationModalBusStore();
            const store = ConfigurationStoreStub.withSelectedTracker({
                ...ConfigurationStoreStub.bugs,
                title: {
                    field_id: 1001,
                    label: "Summary",
                    type: "string",
                    default_value: "",
                },
                description: null,
            });

            let has_modal_been_opened = false;
            configuration_bus.registerHandler((onSuccessfulSaved: () => void) => {
                has_modal_been_opened = true;
                store.selected_tracker.value = TrackerStub.withTitleAndDescription();
                onSuccessfulSaved();
            });

            const insert_section_callback = vi.fn();

            const wrapper = getWrapper(
                insert_section_callback,
                store,
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-new-section]").trigger("click");

            expect(has_modal_been_opened).toBe(true);
            expect(insert_section_callback).toHaveBeenCalled();
        });
    });

    describe("when tracker is configured and user can submit new section", () => {
        it("should insert a pending artifact section", async () => {
            let has_modal_been_opened = false;

            const add_existing_section_bus = useOpenAddExistingSectionModalBus();
            const configuration_bus = useOpenConfigurationModalBusStore();
            configuration_bus.registerHandler(() => {
                has_modal_been_opened = true;
            });
            const store = ConfigurationStoreStub.withSelectedTracker({
                ...ConfigurationStoreStub.bugs,
                title: {
                    field_id: 1001,
                    label: "Summary",
                    type: "string",
                    default_value: "",
                },
                description: {
                    field_id: 1002,
                    label: "Description",
                    type: "text",
                    default_value: { format: "html", content: "" },
                },
            });

            const insert_section_callback = vi.fn();

            const wrapper = getWrapper(
                insert_section_callback,
                store,
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-new-section]").trigger("click");

            expect(has_modal_been_opened).toBe(false);
            expect(insert_section_callback).toHaveBeenCalled();
        });

        it("should ask to open the add existing section modal", async () => {
            let has_modal_been_opened = false;

            const configuration_bus = useOpenConfigurationModalBusStore();
            const add_existing_section_bus = useOpenAddExistingSectionModalBus();
            add_existing_section_bus.registerHandler(() => {
                has_modal_been_opened = true;
            });
            const store = ConfigurationStoreStub.withSelectedTracker({
                ...ConfigurationStoreStub.bugs,
                title: {
                    field_id: 1001,
                    label: "Summary",
                    type: "string",
                    default_value: "",
                },
                description: {
                    field_id: 1002,
                    label: "Description",
                    type: "text",
                    default_value: { format: "html", content: "" },
                },
            });

            const insert_section_callback = vi.fn();

            const wrapper = getWrapper(
                insert_section_callback,
                store,
                configuration_bus,
                add_existing_section_bus,
            );

            await wrapper.find("[data-test=add-existing-section]").trigger("click");

            expect(has_modal_been_opened).toBe(true);
            expect(insert_section_callback).not.toHaveBeenCalled();
        });
    });
});
