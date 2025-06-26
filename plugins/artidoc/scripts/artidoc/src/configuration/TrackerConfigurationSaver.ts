/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { Tracker } from "@/configuration/AllowedTrackersCollection";
import type { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import type { SelectedTrackerRef } from "@/configuration/SelectedTracker";
import type { SelectedFieldsCollection } from "@/configuration/SelectedFieldsCollection";
import type { AvailableFieldsCollection } from "@/configuration/AvailableFieldsCollection";
import { Option } from "@tuleap/option";
import { putConfiguration } from "@/helpers/rest-querier";
import { getAvailableFields } from "@/sections/readonly-fields/AvailableReadonlyFields";

export interface SaveTrackerConfiguration {
    saveTrackerConfiguration: (new_selected_tracker: Tracker) => ResultAsync<null, Fault>;
}

export function buildTrackerConfigurationSaver(
    document_id: number,
    selected_tracker: SelectedTrackerRef,
    selected_fields: SelectedFieldsCollection,
    available_fields: AvailableFieldsCollection,
): SaveTrackerConfiguration {
    return {
        saveTrackerConfiguration: (new_selected_tracker: Tracker): ResultAsync<null, Fault> => {
            return putConfiguration(document_id, new_selected_tracker.id, [])
                .andThen(() => getAvailableFields(new_selected_tracker.id, selected_fields.value))
                .map((new_available_fields) => {
                    selected_tracker.value = Option.fromValue(new_selected_tracker);
                    available_fields.value = new_available_fields;
                    selected_fields.value = [];
                    return null;
                });
        },
    };
}
