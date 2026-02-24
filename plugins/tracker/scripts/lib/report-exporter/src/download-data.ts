/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

import type { Fault } from "@tuleap/fault";
import { ResultAsync, okAsync, Result } from "neverthrow";
import type { ArtifactValue, FieldValue, ReportData, ListValue, ReportLevel } from "./type-data";
import { OTHER_FIELD } from "./type-data";
import type { LinkedArtifact, ReportArtifact, TrackerStructure } from "./api/rest-querier";
import { getLinkedArtifacts, getReportArtifacts, getTrackerStructure } from "./api/rest-querier";
import {
    ARTIFACT_ID_FIELD,
    ARTIFACT_ID_IN_TRACKER_FIELD,
    ARTIFACT_LINK_FIELD,
    CHECKBOX_FIELD,
    COMPUTED_FIELD,
    CROSS_REFERENCE_FIELD,
    DATE_FIELD,
    FLOAT_FIELD,
    INT_FIELD,
    LAST_UPDATE_DATE_FIELD,
    LAST_UPDATED_BY_FIELD,
    LIST_BIND_STATIC,
    LIST_BIND_UGROUPS,
    LIST_BIND_USERS,
    MULTI_SELECTBOX_FIELD,
    OPEN_LIST_FIELD,
    PRIORITY_FIELD,
    RADIO_BUTTON_FIELD,
    SELECTBOX_FIELD,
    PERMISSION_FIELD,
    STRING_FIELD,
    SUBMISSION_DATE_FIELD,
    SUBMITTED_BY_FIELD,
    TEXT_FIELD,
} from "@tuleap/plugin-tracker-constants";
import type {
    ListFieldStructure,
    OpenListValueRepresentation,
    UserGroupRepresentation,
    UserWithEmailAndStatus,
    StaticValueRepresentation,
    StaticBoundListField,
    UserBoundListField,
    UserGroupBoundListField,
    OpenListFieldStructure,
} from "@tuleap/plugin-tracker-rest-api-types";
import { limitConcurrencyPool } from "@tuleap/concurrency-limit-pool";

type IntermediateDownloadData = {
    structure: TrackerStructure;
    report_artifacts: ReadonlyArray<ReportArtifact>;
};

function isStaticBoundField(
    field: ListFieldStructure | OpenListFieldStructure,
): field is StaticBoundListField {
    return field.bindings.type === LIST_BIND_STATIC;
}

function isUserBoundField(
    field: ListFieldStructure | OpenListFieldStructure,
): field is UserBoundListField {
    return field.bindings.type === LIST_BIND_USERS;
}

function isUserGroupBoundField(
    field: ListFieldStructure | OpenListFieldStructure,
): field is UserGroupBoundListField {
    return field.bindings.type === LIST_BIND_UGROUPS;
}

function isStaticValue(
    value:
        | UserWithEmailAndStatus
        | OpenListValueRepresentation
        | StaticValueRepresentation
        | UserGroupRepresentation,
): value is StaticValueRepresentation {
    return "tlp_color" in value;
}

function isUserValue(
    value:
        | UserWithEmailAndStatus
        | OpenListValueRepresentation
        | StaticValueRepresentation
        | UserGroupRepresentation,
): value is UserWithEmailAndStatus {
    return "email" in value;
}

function isUserGroupValue(
    value:
        | UserWithEmailAndStatus
        | OpenListValueRepresentation
        | StaticValueRepresentation
        | UserGroupRepresentation,
): value is UserGroupRepresentation {
    return "users_uri" in value;
}

function isOpenValue(
    value:
        | UserWithEmailAndStatus
        | OpenListValueRepresentation
        | StaticValueRepresentation
        | UserGroupRepresentation,
): value is OpenListValueRepresentation {
    return !isStaticValue(value) && !isUserValue(value) && !isUserGroupValue(value);
}

export interface DownloadLevelSettings {
    readonly tracker_id: number;
    readonly report_id: number;
    readonly table_renderer_id?: number | undefined;
    readonly artifact_link_types: ReadonlyArray<string>;
    readonly all_columns: boolean;
}

export interface DownloadSettings {
    readonly first_level: DownloadLevelSettings;
    readonly second_level?: DownloadLevelSettings;
    readonly third_level?: Omit<DownloadLevelSettings, "artifact_link_types">;
}

function mapListValues(
    values:
        | ReadonlyArray<UserWithEmailAndStatus>
        | ReadonlyArray<OpenListValueRepresentation | StaticValueRepresentation>
        | ReadonlyArray<UserGroupRepresentation>,
    field: ListFieldStructure | OpenListFieldStructure,
): ListValue {
    if (isStaticBoundField(field)) {
        return {
            type: LIST_BIND_STATIC,
            value: values
                .filter((value) => isStaticValue(value) || isOpenValue(value))
                .map((value) => ({ label: value.label })),
        };
    }

    if (isUserBoundField(field)) {
        return {
            type: LIST_BIND_USERS,
            value: values
                .filter((value) => isUserValue(value) || isOpenValue(value))
                .map((value) => {
                    if (isUserValue(value)) {
                        return {
                            display_name: value.display_name,
                            username: value.username,
                        };
                    }
                    return {
                        display_name: value.label,
                        username: value.label,
                    };
                }),
        };
    }

    if (isUserGroupBoundField(field)) {
        return {
            type: LIST_BIND_UGROUPS,
            value: values.filter(isUserGroupValue).map((value) => ({
                label: value.label,
                key: value.key,
            })),
        };
    }

    throw Error(`Unknown binding type: ${field.bindings.type}`);
}

function formatArtifactValue(artifact: ReportArtifact, structure: TrackerStructure): ArtifactValue {
    const values = artifact.values.map((value): FieldValue => {
        const field = structure.fields.find((field) => field.field_id === value.field_id);
        if (field === undefined) {
            throw Error(
                `Field #${value.field_id} not found in tracker #${structure.id} structure, this is not normal!`,
            );
        }

        const base_field_info = {
            label: field.label,
            name: field.name,
        };

        switch (value.type) {
            case TEXT_FIELD:
                if ("commonmark" in value) {
                    return {
                        ...base_field_info,
                        type: value.type,
                        value: value.value,
                        commonmark: value.commonmark,
                    };
                }

                return {
                    ...base_field_info,
                    type: value.type,
                    value: value.value,
                };
            case STRING_FIELD:
                return {
                    ...base_field_info,
                    type: value.type,
                    value: value.value,
                };
            case INT_FIELD:
            case FLOAT_FIELD:
            case COMPUTED_FIELD:
            case ARTIFACT_ID_FIELD:
            case ARTIFACT_ID_IN_TRACKER_FIELD:
            case PRIORITY_FIELD:
                return {
                    ...base_field_info,
                    type: value.type,
                    value: value.value,
                };
            case DATE_FIELD:
                if (field.type !== DATE_FIELD) {
                    throw Error(`Expected date field but ${field.type} found`);
                }
                return {
                    ...base_field_info,
                    type: value.type,
                    with_time: field.is_time_displayed,
                    value: value.value !== null ? new Date(value.value) : null,
                };
            case LAST_UPDATE_DATE_FIELD:
            case SUBMISSION_DATE_FIELD:
                return {
                    ...base_field_info,
                    type: value.type,
                    value: value.value !== null ? new Date(value.value) : null,
                };
            case SELECTBOX_FIELD:
            case MULTI_SELECTBOX_FIELD:
            case RADIO_BUTTON_FIELD:
            case CHECKBOX_FIELD:
                if (
                    field.type !== SELECTBOX_FIELD &&
                    field.type !== MULTI_SELECTBOX_FIELD &&
                    field.type !== RADIO_BUTTON_FIELD &&
                    field.type !== CHECKBOX_FIELD
                ) {
                    throw Error(`Expected list field but ${field.type} found`);
                }
                return {
                    ...base_field_info,
                    type: value.type,
                    value: mapListValues(value.values, field),
                };
            case OPEN_LIST_FIELD:
                if (field.type !== OPEN_LIST_FIELD) {
                    throw Error(`Expected open list field but ${field.type} found`);
                }
                return {
                    ...base_field_info,
                    type: value.type,
                    value: mapListValues(value.bind_value_objects, field),
                };
            case SUBMITTED_BY_FIELD:
            case LAST_UPDATED_BY_FIELD:
                return {
                    ...base_field_info,
                    type: value.type,
                    value: {
                        display_name: value.value.display_name,
                        username: value.value.username,
                    },
                };
            case ARTIFACT_LINK_FIELD:
                return {
                    ...base_field_info,
                    type: value.type,
                    forward: value.links.map((link) => ({
                        nature: link.type,
                        target: link.id,
                    })),
                    reverse: value.reverse_links.map((link) => ({
                        nature: link.type,
                        target: link.id,
                    })),
                };
            case CROSS_REFERENCE_FIELD:
                return {
                    ...base_field_info,
                    type: value.type,
                    value: value.value.map((reference) => ({
                        reference: reference.ref,
                        url: reference.url,
                    })),
                };
            case PERMISSION_FIELD:
                return {
                    ...base_field_info,
                    type: value.type,
                    value: value.granted_groups,
                };
            default:
                return {
                    ...base_field_info,
                    type: OTHER_FIELD,
                    value: "",
                };
        }
    });

    return {
        id: artifact.id,
        values,
    };
}

function downloadLevelData(
    settings: Omit<DownloadLevelSettings, "artifact_link_types">,
): ResultAsync<ReportLevel, Fault> {
    return getReportArtifacts(settings.report_id, settings.table_renderer_id, settings.all_columns)
        .andThen(
            (report_artifacts): ResultAsync<IntermediateDownloadData, Fault> =>
                getTrackerStructure(settings.tracker_id).map((structure) => ({
                    structure,
                    report_artifacts,
                })),
        )
        .andThen(({ structure, report_artifacts }) =>
            okAsync({
                artifacts: report_artifacts.map((artifact) =>
                    formatArtifactValue(artifact, structure),
                ),
            }),
        );
}

function downloadOtherLevelData(
    previous_settings: DownloadLevelSettings,
    current_settings: Omit<DownloadLevelSettings, "artifact_link_types">,
    previous_level: ReportLevel,
): ResultAsync<ReportLevel, Fault> {
    return downloadLevelData(current_settings).andThen((current_level) =>
        ResultAsync.fromSafePromise(
            limitConcurrencyPool(
                5,
                previous_level.artifacts,
                (artifact: ArtifactValue): ResultAsync<ReadonlyArray<LinkedArtifact>, Fault> => {
                    const results = [];
                    for (const link_type of previous_settings.artifact_link_types) {
                        results.push(getLinkedArtifacts(artifact.id, link_type));
                    }

                    return ResultAsync.combine(results).map((linked_artifacts) =>
                        linked_artifacts.flat(1),
                    );
                },
            ),
        )
            .andThen((results) =>
                Result.combine(results).map((linked_artifacts) => linked_artifacts.flat(1)),
            )
            .map((linked_artifacts) => ({
                artifacts: current_level.artifacts.filter(
                    (artifact) =>
                        linked_artifacts.find(
                            (linked_artifact) => linked_artifact.id === artifact.id,
                        ) !== undefined,
                ),
            })),
    );
}

export function downloadData(download_settings: DownloadSettings): ResultAsync<ReportData, Fault> {
    return downloadLevelData(download_settings.first_level).andThen((first_level) => {
        if (download_settings.second_level) {
            return downloadOtherLevelData(
                download_settings.first_level,
                download_settings.second_level,
                first_level,
            ).andThen((second_level) => {
                if (download_settings.second_level && download_settings.third_level) {
                    return downloadOtherLevelData(
                        download_settings.second_level,
                        download_settings.third_level,
                        second_level,
                    ).map((third_level) => ({ first_level, second_level, third_level }));
                }

                return okAsync({ first_level, second_level });
            });
        }

        return okAsync({ first_level });
    });
}
