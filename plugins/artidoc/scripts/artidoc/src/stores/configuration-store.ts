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

import { Option } from "@tuleap/option";
import { putConfiguration } from "@/helpers/rest-querier";
import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import { getAvailableFields } from "@/sections/readonly-fields/AvailableReadonlyFields";
import type { Tracker } from "@/configuration/AllowedTrackersCollection";
import type { SelectedTrackerRef } from "@/configuration/SelectedTracker";
import type { ResultAsync } from "neverthrow";
import { okAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { SelectedFieldsCollection } from "@/configuration/SelectedFieldsCollection";
import type { AvailableFieldsCollection } from "@/configuration/AvailableFieldsCollection";

export interface ConfigurationStore {
    saveTrackerConfiguration: (new_selected_tracker: Tracker) => ResultAsync<null, Fault>;
    saveFieldsConfiguration: (
        new_selected_fields: ConfigurationField[],
    ) => ResultAsync<null, Fault>;
}

export const CONFIGURATION_STORE: StrictInjectionKey<ConfigurationStore> =
    Symbol("configuration-store");

export function initConfigurationStore(
    document_id: number,
    selected_tracker: SelectedTrackerRef,
    selected_fields: SelectedFieldsCollection,
    available_fields: AvailableFieldsCollection,
): ConfigurationStore {
    function saveTrackerConfiguration(new_selected_tracker: Tracker): ResultAsync<null, Fault> {
        return putConfiguration(document_id, new_selected_tracker.id, [])
            .andThen(() => getAvailableFields(new_selected_tracker.id, selected_fields.value))
            .map((new_available_fields) => {
                selected_tracker.value = Option.fromValue(new_selected_tracker);
                available_fields.value = new_available_fields;
                selected_fields.value = [];
                return null;
            });
    }

    function saveFieldsConfiguration(
        new_selected_fields: ConfigurationField[],
    ): ResultAsync<null, Fault> {
        return selected_tracker.value.mapOr((currently_selected_tracker) => {
            const selected_tracker_id = currently_selected_tracker.id;

            return putConfiguration(document_id, selected_tracker_id, new_selected_fields)
                .andThen(() => getAvailableFields(selected_tracker_id, new_selected_fields))
                .map((new_available_fields) => {
                    selected_fields.value = new_selected_fields;
                    available_fields.value = new_available_fields;
                    return null;
                });
        }, okAsync(null));
    }

    return {
        saveTrackerConfiguration,
        saveFieldsConfiguration,
    };
}
