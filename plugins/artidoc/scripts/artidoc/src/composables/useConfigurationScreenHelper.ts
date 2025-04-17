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

import type { ComputedRef, Ref } from "vue";
import { ref, computed } from "vue";
import type { Tracker, ConfigurationStore } from "@/stores/configuration-store";

export interface ConfigurationScreenHelper {
    allowed_trackers: readonly Tracker[];
    NO_SELECTED_TRACKER: null;
    no_allowed_trackers: boolean;
    is_submit_button_disabled: ComputedRef<boolean>;
    is_success: Ref<boolean>;
    is_error: Ref<boolean>;
    error_message: Ref<string>;
    submit_button_icon: ComputedRef<string>;
    new_selected_tracker: Ref<Tracker | null>;
    onSubmit(): void;
    resetSelection(): void;
}

export function useConfigurationScreenHelper(
    configuration_store: ConfigurationStore,
): ConfigurationScreenHelper {
    const {
        allowed_trackers,
        selected_tracker,
        is_saving,
        is_error,
        is_success,
        error_message,
        saveConfiguration,
        resetSuccessFlagFromPreviousCalls,
    } = configuration_store;

    const NO_SELECTED_TRACKER = null;

    const no_allowed_trackers = allowed_trackers.length === 0;

    const new_selected_tracker = ref(selected_tracker.value);

    const is_submit_button_disabled = computed(
        () =>
            no_allowed_trackers ||
            is_saving.value ||
            new_selected_tracker.value === NO_SELECTED_TRACKER ||
            new_selected_tracker.value === selected_tracker.value,
    );
    const submit_button_icon = computed(() =>
        is_saving.value ? "fa-solid fa-spin fa-circle-notch" : "fa-solid fa-floppy-disk",
    );

    function onSubmit(): void {
        if (new_selected_tracker.value) {
            saveConfiguration(new_selected_tracker.value);
        }
    }

    function resetSelection(): void {
        new_selected_tracker.value = selected_tracker.value;

        resetSuccessFlagFromPreviousCalls();
    }

    return {
        allowed_trackers,
        NO_SELECTED_TRACKER,
        no_allowed_trackers,
        submit_button_icon,
        new_selected_tracker,
        is_submit_button_disabled,
        is_error,
        is_success,
        error_message,
        onSubmit,
        resetSelection,
    };
}
