/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import type { getTestManagementExecution } from "./rest-querier";
import { getArtifacts, getReportArtifacts, getTrackerDefinition } from "./rest-querier";

import { limitConcurrencyPool } from "@tuleap/concurrency-limit-pool";

export interface ArtifactFromReport {
    readonly id: number;
    readonly title: string | null;
    values: ReadonlyArray<ArtifactReportFieldValue>;
    containers: ReadonlyArray<ArtifactReportContainer>;
}

export async function retrieveReportArtifacts(
    tracker_id: number,
    report_id: number,
    report_has_changed: boolean,
    get_test_execution: typeof getTestManagementExecution
): Promise<ReadonlyArray<ArtifactFromReport>> {
    const tracker_structure_promise = retrieveTrackerStructure(tracker_id);
    const report_artifacts: ArtifactResponse[] = await getReportArtifacts(
        report_id,
        report_has_changed
    );

    const tracker_structure = await tracker_structure_promise;
    const all_linked_artifacts_ids: Set<number> = new Set();
    const already_retrieved_artifacts: Map<number, ArtifactResponse> = new Map();

    const report_artifacts_with_additional_info: ArtifactFromReport[] = await limitConcurrencyPool(
        5,
        report_artifacts,
        async (report_artifact: ArtifactResponse): Promise<ArtifactFromReport> => {
            already_retrieved_artifacts.set(report_artifact.id, report_artifact);
            const values_by_field_id = new Map(
                report_artifact.values.map((value) => [value.field_id, value])
            );

            return {
                ...report_artifact,
                ...(await extractFieldValuesWithAdditionalInfoInStructuredContainers(
                    report_artifact.id,
                    tracker_structure.disposition,
                    values_by_field_id,
                    tracker_structure.fields,
                    get_test_execution,
                    all_linked_artifacts_ids
                )),
            };
        }
    );

    const missing_artifacts_ids = new Set(
        [...all_linked_artifacts_ids].filter((id) => already_retrieved_artifacts.has(id) === false)
    );
    (await getArtifacts(missing_artifacts_ids)).forEach((artifact) =>
        already_retrieved_artifacts.set(artifact.id, artifact)
    );

    return report_artifacts_with_additional_info.map((report_artifact) => {
        return {
            ...report_artifact,
            values: injectLinkTitleInValues(report_artifact.values, already_retrieved_artifacts),
            containers: injectLinkTitleInContainers(
                report_artifact.containers,
                already_retrieved_artifacts
            ),
        };
    });
}

function injectLinkTitleInValues(
    values: ReadonlyArray<ArtifactReportFieldValue>,
    all_artifacts: Map<number, ArtifactResponse>
): ReadonlyArray<ArtifactReportFieldValue> {
    return values.map((value) => {
        if (value.type === "art_link") {
            return {
                ...value,
                links: injectLinkTitle(value.links, all_artifacts),
                reverse_links: injectLinkTitle(value.reverse_links, all_artifacts),
            };
        }
        return value;
    });
}

function injectLinkTitle(
    links: ReadonlyArray<ArtifactLinkWithTitle>,
    all_artifacts: Map<number, ArtifactResponse>
): ReadonlyArray<ArtifactLinkWithTitle> {
    return links.map((link) => {
        return {
            ...link,
            title: all_artifacts.get(link.id)?.title ?? "",
        };
    });
}

function injectLinkTitleInContainers(
    containers: ReadonlyArray<ArtifactReportContainer>,
    all_artifacts: Map<number, ArtifactResponse>
): ReadonlyArray<ArtifactReportContainer> {
    return containers.map((container) => {
        return {
            ...container,
            values: injectLinkTitleInValues(container.values, all_artifacts),
            containers: injectLinkTitleInContainers(container.containers, all_artifacts),
        };
    });
}

async function extractFieldValuesWithAdditionalInfoInStructuredContainers(
    artifact_id: number,
    structure_elements: ReadonlyArray<StructureFormat>,
    field_values: ReadonlyMap<number, ArtifactReportResponseFieldValue>,
    fields_structure: ReadonlyMap<number, FieldsStructure>,
    get_test_execution: typeof getTestManagementExecution,
    all_linked_artifacts_ids: Set<number>
): Promise<Omit<ArtifactReportContainer, "name">> {
    const values_with_additional_information: ArtifactReportFieldValue[] = [];
    const containers: ArtifactReportContainer[] = [];
    for (const structure_element of structure_elements) {
        if (structure_element.content === null) {
            const field = fields_structure.get(structure_element.id);
            if (field && field.type === "ttmstepexec") {
                const test_execution_value = await getStepExecutionsFieldValue(
                    artifact_id,
                    field,
                    get_test_execution
                );
                values_with_additional_information.push(test_execution_value);
                continue;
            }

            const field_value = field_values.get(structure_element.id);
            if (!field_value) {
                continue;
            }
            values_with_additional_information.push(
                getFieldValueWithAdditionalInformation(
                    field_value,
                    fields_structure,
                    all_linked_artifacts_ids
                )
            );
            continue;
        }

        const container_field_definition = fields_structure.get(structure_element.id);
        if (container_field_definition && container_field_definition.type === "fieldset") {
            containers.push({
                name: container_field_definition.label,
                ...(await extractFieldValuesWithAdditionalInfoInStructuredContainers(
                    artifact_id,
                    structure_element.content,
                    field_values,
                    fields_structure,
                    get_test_execution,
                    all_linked_artifacts_ids
                )),
            });
            continue;
        }

        const children_structured_information =
            await extractFieldValuesWithAdditionalInfoInStructuredContainers(
                artifact_id,
                structure_element.content,
                field_values,
                fields_structure,
                get_test_execution,
                all_linked_artifacts_ids
            );
        values_with_additional_information.push(...children_structured_information.values);
        containers.push(...children_structured_information.containers);
    }

    return {
        values: values_with_additional_information,
        containers: containers,
    };
}

async function getStepExecutionsFieldValue(
    artifact_id: number,
    field: StepExecutionFieldStructure,
    get_test_execution: typeof getTestManagementExecution
): Promise<ArtifactStepExecutionFieldValue> {
    try {
        const test_execution: TestExecutionResponse = await get_test_execution(artifact_id);

        const test_execution_status: Array<TestExecStatus | null> = [];
        const test_executions: Array<ArtifactReportResponseStepRepresentationEnhanced> = [];

        for (const test_definition of test_execution.definition.steps) {
            if (test_definition.id.toString() in test_execution.steps_results) {
                test_execution_status.push(
                    test_execution.steps_results[test_definition.id.toString()].status
                );
                test_executions.push({
                    ...test_definition,
                    status: test_execution.steps_results[test_definition.id.toString()].status,
                });
            } else {
                test_execution_status.push(null);
                test_executions.push({
                    ...test_definition,
                    status: null,
                });
            }
        }

        return {
            field_id: field.field_id,
            type: "ttmstepexec",
            label: field.label,
            value: {
                steps: test_executions,
                steps_values: test_execution_status,
            },
        };
    } catch (e) {
        return {
            field_id: field.field_id,
            type: "ttmstepexec",
            label: field.label,
            value: null,
        };
    }
}

function getFieldValueWithAdditionalInformation(
    value: ArtifactReportResponseFieldValue,
    fields_structure: ReadonlyMap<number, FieldsStructure>,
    all_linked_artifacts_ids: Set<number>
): ArtifactReportFieldValue {
    const field_structure = fields_structure.get(value.field_id);
    switch (value.type) {
        case "date":
        case "lud":
        case "subon":
            if (field_structure && field_structure.type === value.type) {
                return {
                    ...value,
                    is_time_displayed: field_structure.is_time_displayed,
                };
            }
            return { ...value, is_time_displayed: true };
        case "rb":
        case "msb":
        case "cb":
        case "sb": {
            const formatted_values: string[] = [];
            for (const list_value of value.values) {
                if ("display_name" in list_value && list_value.id !== null) {
                    formatted_values.push(list_value.display_name);
                } else if ("label" in list_value) {
                    formatted_values.push(list_value.label);
                }
            }
            return { ...value, formatted_values: formatted_values };
        }
        case "tbl": {
            const formatted_open_values: string[] = [];
            for (const open_list_value of value.bind_value_objects) {
                if ("display_name" in open_list_value) {
                    formatted_open_values.push(open_list_value.display_name);
                } else if ("label" in open_list_value) {
                    formatted_open_values.push(open_list_value.label);
                }
            }
            return { ...value, formatted_open_values: formatted_open_values };
        }
        case "perm": {
            const formatted_granted_ugroups: string[] = [];
            for (const ugroup_id of value.granted_groups_ids) {
                if (field_structure && field_structure.type === value.type) {
                    for (const structure_ugroup of field_structure.values.ugroup_representations) {
                        if (structure_ugroup.id === ugroup_id) {
                            formatted_granted_ugroups.push(structure_ugroup.label);
                        }
                    }
                }
            }
            return { ...value, formatted_granted_ugroups: formatted_granted_ugroups };
        }
        case "art_link": {
            [...value.links, ...value.reverse_links].forEach((link) =>
                all_linked_artifacts_ids.add(link.id)
            );

            return {
                ...value,
                links: value.links.map((link) => {
                    return { ...link, title: "" };
                }),
                reverse_links: value.reverse_links.map((link) => {
                    return { ...link, title: "" };
                }),
            };
        }
        default:
            return value;
    }
}

interface TrackerStructure {
    fields: ReadonlyMap<number, FieldsStructure>;
    disposition: ReadonlyArray<StructureFormat>;
}

async function retrieveTrackerStructure(tracker_id: number): Promise<TrackerStructure> {
    const tracker_structure: TrackerDefinition = await getTrackerDefinition(tracker_id);

    const fields_map: Map<number, FieldsStructure> = new Map();

    for (const field of tracker_structure.fields) {
        switch (field.type) {
            case "date":
            case "lud":
            case "subon":
            case "fieldset":
            case "sb":
            case "rb":
            case "msb":
            case "cb":
            case "tbl":
            case "perm":
            case "ttmstepexec":
                fields_map.set(field.field_id, field);
                break;
            default:
        }
    }

    return { fields: fields_map, disposition: tracker_structure.structure };
}

export interface ArtifactResponse {
    readonly id: number;
    readonly title: string | null;
    readonly values: ReadonlyArray<ArtifactReportResponseFieldValue>;
}

export interface ArtifactReportContainer {
    name: string;
    values: ReadonlyArray<ArtifactReportFieldValue>;
    containers: ReadonlyArray<this>;
}

export type ArtifactReportFieldValue =
    | ArtifactReportResponseUnknownFieldValue
    | ArtifactReportResponseNumericFieldValue
    | ArtifactReportResponseStringFieldValue
    | ArtifactReportResponseTextFieldValue
    | (ArtifactReportResponseDateFieldValue & { is_time_displayed: boolean })
    | ArtifactReportResponseComputedFieldValue
    | ArtifactReportResponseFileFieldValue
    | ArtifactReportResponseSubmittedByFieldValue
    | ArtifactReportResponseLastUpdateByFieldValue
    | (ArtifactReportResponseSimpleListFieldValue & { formatted_values: string[] })
    | (ArtifactReportResponseOpenListFieldValue & { formatted_open_values: string[] })
    | (ArtifactReportResponsePermissionsOnArtifactFieldValue & {
          formatted_granted_ugroups: string[];
      })
    | ArtifactReportResponseCrossReferencesFieldValue
    | ArtifactReportResponseStepDefinitionFieldValue
    | ArtifactStepExecutionFieldValue
    | ArtifactReportArtifactLinksFieldValue;

type ArtifactReportResponseFieldValue =
    | ArtifactReportResponseUnknownFieldValue
    | ArtifactReportResponseNumericFieldValue
    | ArtifactReportResponseStringFieldValue
    | ArtifactReportResponseTextFieldValue
    | ArtifactReportResponseDateFieldValue
    | ArtifactReportResponseComputedFieldValue
    | ArtifactReportResponseFileFieldValue
    | ArtifactReportResponseSubmittedByFieldValue
    | ArtifactReportResponseLastUpdateByFieldValue
    | ArtifactReportResponseSimpleListFieldValue
    | ArtifactReportResponseOpenListFieldValue
    | ArtifactReportResponsePermissionsOnArtifactFieldValue
    | ArtifactReportResponseCrossReferencesFieldValue
    | ArtifactReportResponseStepDefinitionFieldValue
    | ArtifactReportResponseArtifactLinksFieldValue;

interface ArtifactReportResponseNumericFieldValue {
    field_id: number;
    type: "aid" | "atid" | "int" | "float" | "priority";
    label: string;
    value: number | null;
}

interface ArtifactReportResponseStringFieldValue {
    field_id: number;
    type: "string";
    label: string;
    value: string | null;
}

interface ArtifactReportResponseTextFieldValue {
    field_id: number;
    type: "text";
    label: string;
    value: string | null;
    format: "text" | "html";
}

interface ArtifactReportResponseDateFieldValue {
    field_id: number;
    type: "date" | "lud" | "subon";
    label: string;
    value: string | null;
}

interface ArtifactReportResponseComputedFieldValue {
    field_id: number;
    type: "computed";
    label: string;
    value: number | null;
    manual_value: number | null;
    is_autocomputed: boolean;
}

interface ArtifactReportResponseFileFieldValue {
    field_id: number;
    type: "file";
    label: string;
    file_descriptions: Array<ArtifactReportResponseFileDescriptionFieldValue>;
}

interface ArtifactReportResponseSubmittedByFieldValue {
    field_id: number;
    type: "subby";
    label: string;
    value: ArtifactReportResponseUserRepresentation;
}

interface ArtifactReportResponseLastUpdateByFieldValue {
    field_id: number;
    type: "luby";
    label: string;
    value: ArtifactReportResponseUserRepresentation;
}

interface ArtifactReportResponseSimpleListFieldValue {
    field_id: number;
    type: "sb" | "rb" | "msb" | "cb";
    label: string;
    values:
        | Array<ArtifactReportResponseUserRepresentation>
        | Array<ArtifactReportResponseStaticValueRepresentation>
        | Array<ArtifactReportResponseUserGroupRepresentation>;
}

interface ArtifactReportResponseOpenListFieldValue {
    field_id: number;
    type: "tbl";
    label: string;
    bind_value_objects:
        | Array<ArtifactReportResponseUserRepresentation>
        | Array<
              | ArtifactReportResponseOpenListValueRepresentation
              | ArtifactReportResponseStaticValueRepresentation
          >
        | Array<ArtifactReportResponseUserGroupRepresentation>;
}

export interface ArtifactReportResponseUserRepresentation {
    email: string;
    status: string;
    id: number | null;
    uri: string;
    user_url: string;
    real_name: string;
    display_name: string;
    username: string;
    ldap_id: string;
    avatar_url: string;
    is_anonymous: boolean;
    has_avatar: boolean;
}

interface ArtifactReportResponseStaticValueRepresentation {
    id: number;
    label: string;
    color: string | null;
    tlp_color: string | null;
}

interface ArtifactReportResponseOpenListValueRepresentation {
    id: number;
    label: string;
}

interface ArtifactReportResponseUserGroupRepresentation {
    id: string;
    uri: string;
    label: string;
    users_uri: string;
    short_name: string;
    key: string;
}

interface ArtifactReportResponsePermissionsOnArtifactFieldValue {
    field_id: number;
    type: "perm";
    label: string;
    granted_groups: string[];
    granted_groups_ids: string[];
}

interface ArtifactReportResponseCrossReferencesFieldValue {
    field_id: number;
    type: "cross";
    label: string;
    value: Array<{
        ref: string;
        url: string;
        direction: string;
    }>;
}

export interface ArtifactReportResponseStepDefinitionFieldValue {
    field_id: number;
    type: "ttmstepdef";
    label: string;
    value: Array<ArtifactReportResponseStepRepresentation>;
}

export interface ArtifactLink {
    type: string | null;
    id: number;
}

export interface ArtifactLinkWithTitle extends ArtifactLink {
    title: string;
}

interface ArtifactReportResponseArtifactLinksFieldValue {
    field_id: number;
    type: "art_link";
    label: string;
    links: ArtifactLink[];
    reverse_links: ArtifactLink[];
}

interface ArtifactReportArtifactLinksFieldValue {
    field_id: number;
    type: "art_link";
    label: string;
    links: ReadonlyArray<ArtifactLinkWithTitle>;
    reverse_links: ReadonlyArray<ArtifactLinkWithTitle>;
}

interface ArtifactReportResponseStepRepresentation {
    id: number;
    description: string;
    description_format: string;
    expected_results: string;
    expected_results_format: string;
    rank: number;
}

type TestExecStatus = "notrun" | "passed" | "failed" | "blocked";

interface ArtifactReportResponseStepRepresentationEnhanced
    extends ArtifactReportResponseStepRepresentation {
    status: TestExecStatus | null;
}

interface ArtifactStepExecutionFieldValue {
    field_id: number;
    type: "ttmstepexec";
    label: string;
    value: null | {
        steps: Array<ArtifactReportResponseStepRepresentationEnhanced>;
        steps_values: Array<TestExecStatus | null>;
    };
}

interface ArtifactReportResponseFileDescriptionFieldValue {
    id: number;
    submitted_by: number;
    description: string;
    name: string;
    size: number;
    type: string;
    html_url: string;
    html_preview_url: string;
    uri: string;
}

export interface ArtifactReportResponseUnknownFieldValue {
    field_id: number;
    type: never;
    label: string;
    value: never;
}

type FieldsStructure =
    | UnknownFieldStructure
    | DateFieldStructure
    | ContainerFieldStructure
    | ListFieldStructure
    | PermissionsOnArtifactFieldStructure
    | StepExecutionFieldStructure
    | ArtifactLinkFieldStructure;

interface BaseFieldStructure {
    field_id: number;
}

interface UnknownFieldStructure extends BaseFieldStructure {
    type: never;
}

interface DateFieldStructure extends BaseFieldStructure {
    type: "date" | "lud" | "subon";
    is_time_displayed: boolean;
}

interface ContainerFieldStructure extends BaseFieldStructure {
    type: "column" | "fieldset";
    label: string;
}

interface ListFieldStructure extends BaseFieldStructure {
    type: "sb" | "rb" | "msb" | "cb" | "tbl";
}

interface StepExecutionFieldStructure extends BaseFieldStructure {
    type: "ttmstepexec";
    label: string;
}

interface PermissionsOnArtifactFieldStructure extends BaseFieldStructure {
    type: "perm";
    values: {
        is_used_by_default: boolean;
        ugroup_representations: Array<ArtifactReportResponseUserGroupRepresentation>;
    };
}

interface ArtifactLinkFieldStructure extends BaseFieldStructure {
    type: "art_link";
    label: string;
}

interface StructureFormat {
    id: number;
    content: null | ReadonlyArray<this>;
}

export interface TrackerDefinition {
    fields: ReadonlyArray<FieldsStructure>;
    structure: ReadonlyArray<StructureFormat>;
}

export interface TestExecutionResponse {
    definition: {
        summary: string;
        description: string;
        description_format: string;
        steps: Array<ArtifactReportResponseStepRepresentation>;
        requirement: {
            id: number;
            title: string | null;
        } | null;
    };
    steps_results: {
        [key: string]: {
            step_id: number;
            status: TestExecStatus;
        };
    };
    previous_result: {
        status: TestExecStatus | null;
        submitted_on: string;
        submitted_by: ArtifactReportResponseUserRepresentation;
    } | null;
}
