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
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import { getAvailableFields } from "@/sections/readonly-fields/AvailableReadonlyFields";
import type { ResultAsync } from "neverthrow";
import { okAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { putConfiguration } from "@/helpers/rest-querier";
import type { SelectedTrackerRef } from "@/configuration/SelectedTracker";
import type { SelectedFieldsCollection } from "@/configuration/SelectedFieldsCollection";
import type { AvailableFieldsCollection } from "@/configuration/AvailableFieldsCollection";

export interface SaveFieldsConfiguration {
    saveFieldsConfiguration: (
        new_selected_fields: ConfigurationField[],
    ) => ResultAsync<null, Fault>;
}

export function buildFieldsConfigurationSaver(
    document_id: number,
    selected_tracker: SelectedTrackerRef,
    selected_fields: SelectedFieldsCollection,
    available_fields: AvailableFieldsCollection,
): SaveFieldsConfiguration {
    return {
        saveFieldsConfiguration: (
            new_selected_fields: ConfigurationField[],
        ): ResultAsync<null, Fault> => {
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
        },
    };
}
