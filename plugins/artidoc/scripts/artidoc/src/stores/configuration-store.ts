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

import type { Ref } from "vue";
import { ref } from "vue";
import { Option } from "@tuleap/option";
import { putConfiguration } from "@/helpers/rest-querier";
import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";
import type { Project } from "@/helpers/project.type";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import { getAvailableFields } from "@/sections/readonly-fields/AvailableReadonlyFields";
import type { Tracker } from "@/configuration/AllowedTrackersCollection";
import type { SelectedTrackerRef } from "@/configuration/SelectedTracker";

export interface ConfigurationStore {
    selected_fields: Ref<ConfigurationField[]>;
    available_fields: Ref<ConfigurationField[]>;
    is_saving: Ref<boolean>;
    is_error: Ref<boolean>;
    is_success: Ref<boolean>;
    error_message: Ref<string>;
    saveTrackerConfiguration: (new_selected_tracker: Tracker) => void;
    saveFieldsConfiguration: (new_selected_fields: ConfigurationField[]) => void;
    resetSuccessFlagFromPreviousCalls: () => void;
    current_project: Ref<Project | null>;
}

export const CONFIGURATION_STORE: StrictInjectionKey<ConfigurationStore> =
    Symbol("configuration-store");

export function initConfigurationStore(
    document_id: number,
    selected_tracker: SelectedTrackerRef,
    selected_fields: ConfigurationField[],
): ConfigurationStore {
    const currently_selected_fields = ref(selected_fields);
    const is_saving = ref(false);
    const is_error = ref(false);
    const is_success = ref(false);
    const error_message = ref("");
    const current_project: Ref<Project | null> = ref(
        selected_tracker.value.mapOr((tracker) => tracker.project, null),
    );

    const available_fields: Ref<ConfigurationField[]> = ref([]);

    selected_tracker.value.apply((currently_selected_tracker) => {
        getAvailableFields(currently_selected_tracker.id, currently_selected_fields.value).match(
            (fields) => {
                available_fields.value = fields;
            },
            (fault) => {
                error_message.value = String(fault);
            },
        );
    });

    function saveTrackerConfiguration(new_selected_tracker: Tracker): void {
        is_saving.value = true;
        is_error.value = false;
        is_success.value = false;
        current_project.value = new_selected_tracker.project;

        putConfiguration(document_id, new_selected_tracker.id, [])
            .andThen(() =>
                getAvailableFields(new_selected_tracker.id, currently_selected_fields.value),
            )
            .match(
                (new_available_fields) => {
                    selected_tracker.value = Option.fromValue(new_selected_tracker);
                    available_fields.value = new_available_fields;
                    currently_selected_fields.value = [];
                    is_saving.value = false;
                    is_success.value = true;
                },
                (fault) => {
                    is_saving.value = false;
                    is_error.value = true;
                    error_message.value = String(fault);
                },
            );
    }

    function saveFieldsConfiguration(new_selected_fields: ConfigurationField[]): void {
        is_saving.value = true;
        is_error.value = false;
        is_success.value = false;

        selected_tracker.value.apply((currently_selected_tracker) => {
            const selected_tracker_id = currently_selected_tracker.id;

            putConfiguration(document_id, selected_tracker_id, new_selected_fields)
                .andThen(() => getAvailableFields(selected_tracker_id, new_selected_fields))
                .match(
                    (new_available_fields) => {
                        currently_selected_fields.value = new_selected_fields;
                        available_fields.value = new_available_fields;
                        is_saving.value = false;
                        is_success.value = true;
                    },
                    (fault) => {
                        is_saving.value = false;
                        is_error.value = true;
                        error_message.value = String(fault);
                    },
                );
        });
    }

    function resetSuccessFlagFromPreviousCalls(): void {
        is_success.value = false;
    }

    return {
        selected_fields: currently_selected_fields,
        available_fields,
        is_saving,
        is_error,
        is_success,
        error_message,
        current_project,
        saveTrackerConfiguration,
        saveFieldsConfiguration,
        resetSuccessFlagFromPreviousCalls,
    };
}
