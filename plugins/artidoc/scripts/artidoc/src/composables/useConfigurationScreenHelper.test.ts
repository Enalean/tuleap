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

import { describe, it, expect, vi } from "vitest";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import { useConfigurationScreenHelper } from "@/composables/useConfigurationScreenHelper";
import type { ConfigurationStore } from "@/stores/configuration-store";
import { ref } from "vue";

describe("useConfigurationScreenHelper", () => {
    it("should act as a proxy for configuration store", () => {
        const store = ConfigurationStoreStub.withSelectedTracker(ConfigurationStoreStub.bugs.id);
        vi.spyOn(strict_inject, "strictInject").mockReturnValue(store);

        const helper = useConfigurationScreenHelper();

        expect(helper.is_success).toBe(store.is_success);
        expect(helper.is_error).toBe(store.is_error);
        expect(helper.allowed_trackers).toBe(helper.allowed_trackers);
        expect(helper.error_message).toBe(store.error_message);
    });

    describe("no_allowed_trackers", () => {
        it("should be true if there is no tracker", () => {
            const store = ConfigurationStoreStub.withoutAllowedTrackers();
            vi.spyOn(strict_inject, "strictInject").mockReturnValue(store);

            const helper = useConfigurationScreenHelper();

            expect(helper.no_allowed_trackers).toBe(true);
        });

        it("should be false if there are trackers", () => {
            const store = ConfigurationStoreStub.withSelectedTracker(
                ConfigurationStoreStub.bugs.id,
            );
            vi.spyOn(strict_inject, "strictInject").mockReturnValue(store);

            const helper = useConfigurationScreenHelper();

            expect(helper.no_allowed_trackers).toBe(false);
        });
    });

    describe("is_submit_button_disabled", () => {
        it("should be false by default", () => {
            const store = ConfigurationStoreStub.withSelectedTracker(
                ConfigurationStoreStub.bugs.id,
            );
            vi.spyOn(strict_inject, "strictInject").mockReturnValue(store);

            const helper = useConfigurationScreenHelper();

            helper.new_selected_tracker.value = String(ConfigurationStoreStub.tasks.id);

            expect(helper.is_submit_button_disabled.value).toBe(false);
        });

        it("should be true if no allowed trackers", () => {
            const store = ConfigurationStoreStub.withoutAllowedTrackers();
            vi.spyOn(strict_inject, "strictInject").mockReturnValue(store);

            const helper = useConfigurationScreenHelper();

            expect(helper.is_submit_button_disabled.value).toBe(true);
        });

        it("should be true if saving is in progress", () => {
            const store = ConfigurationStoreStub.withSavingInProgress();
            vi.spyOn(strict_inject, "strictInject").mockReturnValue(store);

            const helper = useConfigurationScreenHelper();

            expect(helper.is_submit_button_disabled.value).toBe(true);
        });

        it("should be true if selected tracker is NO_SELECTED_TRACKER", () => {
            const store = ConfigurationStoreStub.withSelectedTracker(
                ConfigurationStoreStub.bugs.id,
            );
            vi.spyOn(strict_inject, "strictInject").mockReturnValue(store);

            const helper = useConfigurationScreenHelper();

            helper.new_selected_tracker.value = helper.NO_SELECTED_TRACKER;

            expect(helper.is_submit_button_disabled.value).toBe(true);
        });

        it("should be true if selected tracker does not change", () => {
            const store = ConfigurationStoreStub.withSelectedTracker(
                ConfigurationStoreStub.bugs.id,
            );
            vi.spyOn(strict_inject, "strictInject").mockReturnValue(store);

            const helper = useConfigurationScreenHelper();

            expect(helper.is_submit_button_disabled.value).toBe(true);
        });
    });

    describe("submit_button_icon", () => {
        it("should display default icon", () => {
            const store = ConfigurationStoreStub.withSelectedTracker(
                ConfigurationStoreStub.bugs.id,
            );
            vi.spyOn(strict_inject, "strictInject").mockReturnValue(store);

            const helper = useConfigurationScreenHelper();

            expect(helper.submit_button_icon.value).toBe("fa-solid fa-floppy-disk");
        });

        it("should display spinner icon if saving is in progress", () => {
            const store = ConfigurationStoreStub.withSavingInProgress();
            vi.spyOn(strict_inject, "strictInject").mockReturnValue(store);

            const helper = useConfigurationScreenHelper();

            expect(helper.submit_button_icon.value).toBe("fa-solid fa-spin fa-circle-notch");
        });
    });

    describe("resetSelection", () => {
        it("should reset current selection", () => {
            const store: ConfigurationStore = {
                ...ConfigurationStoreStub.withSuccessfullSave(),
                selected_tracker_id: ref(ConfigurationStoreStub.bugs.id),
            };
            vi.spyOn(strict_inject, "strictInject").mockReturnValue(store);

            const helper = useConfigurationScreenHelper();

            helper.new_selected_tracker.value = String(ConfigurationStoreStub.tasks.id);

            helper.resetSelection();

            expect(helper.new_selected_tracker.value).toBe(String(ConfigurationStoreStub.bugs.id));
        });
    });
});
