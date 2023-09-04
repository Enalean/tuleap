/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import type {
    ArtifactFromReport,
    ArtifactResponse,
    TrackerStructure,
    ArtifactLinkWithTitle,
    ArtifactReportContainer,
    ArtifactReportFieldValue,
    ArtifactReportResponseFieldValue,
    ArtifactReportResponseStepRepresentationEnhanced,
    ArtifactStepExecutionFieldValue,
    TestExecStatus,
    StructureFormat,
    FieldsStructure,
    TestExecutionResponse,
    StepExecutionFieldStructure,
} from "../type";
import { limitConcurrencyPool } from "@tuleap/concurrency-limit-pool";
import type { getTestManagementExecution } from "./test-execution-retriever";
import { getArtifacts } from "./artifacts-retriever";

export async function retrieveArtifactsStructure(
    tracker_structure_map: Map<number, TrackerStructure>,
    artifacts_from_response: ReadonlyArray<ArtifactResponse>,
    get_test_execution: typeof getTestManagementExecution,
): Promise<ReadonlyArray<ArtifactFromReport>> {
    const all_linked_artifacts_ids: Set<number> = new Set();
    const exported_artifacts: Map<number, ArtifactResponse> = new Map();

    const report_artifacts_with_additional_info: ArtifactFromReport[] = await limitConcurrencyPool(
        5,
        artifacts_from_response,
        async (report_artifact: ArtifactResponse): Promise<ArtifactFromReport> => {
            exported_artifacts.set(report_artifact.id, report_artifact);
            const values_by_field_id = new Map(
                report_artifact.values.map((value) => [value.field_id, value]),
            );

            const tracker_structure = tracker_structure_map.get(report_artifact.tracker.id);
            if (!tracker_structure) {
                throw new Error("Missing tracker structure");
            }

            return {
                ...report_artifact,
                ...(await extractFieldValuesWithAdditionalInfoInStructuredContainers(
                    report_artifact.id,
                    tracker_structure.disposition,
                    values_by_field_id,
                    tracker_structure.fields,
                    get_test_execution,
                    all_linked_artifacts_ids,
                )),
            };
        },
    );

    const already_retrieved_artifacts = new Map([...exported_artifacts]);

    const missing_artifacts_ids = new Set(
        [...all_linked_artifacts_ids].filter((id) => already_retrieved_artifacts.has(id) === false),
    );
    (await getArtifacts(missing_artifacts_ids)).forEach((artifact) =>
        already_retrieved_artifacts.set(artifact.id, artifact),
    );

    return report_artifacts_with_additional_info.map((report_artifact) => {
        return {
            ...report_artifact,
            values: injectLinkInformationInValues(
                report_artifact.values,
                already_retrieved_artifacts,
                exported_artifacts,
            ),
            containers: injectLinkInformationInContainers(
                report_artifact.containers,
                already_retrieved_artifacts,
                exported_artifacts,
            ),
        };
    });
}

function injectLinkInformationInValues(
    values: ReadonlyArray<ArtifactReportFieldValue>,
    all_artifacts: Map<number, ArtifactResponse>,
    exported_artifacts: Map<number, ArtifactResponse>,
): ReadonlyArray<ArtifactReportFieldValue> {
    return values.map((value) => {
        if (value.type === "art_link") {
            return {
                ...value,
                links: injectLinkInformation(value.links, all_artifacts, exported_artifacts),
                reverse_links: injectLinkInformation(
                    value.reverse_links,
                    all_artifacts,
                    exported_artifacts,
                ),
            };
        }
        return value;
    });
}

function injectLinkInformation(
    links: ReadonlyArray<ArtifactLinkWithTitle>,
    all_artifacts: Map<number, ArtifactResponse>,
    exported_artifacts: Map<number, ArtifactResponse>,
): ReadonlyArray<ArtifactLinkWithTitle> {
    return links.map((link) => {
        return {
            ...link,
            title: all_artifacts.get(link.id)?.title ?? "",
            html_url: all_artifacts.get(link.id)?.html_url ?? "",
            is_linked_artifact_part_of_document: exported_artifacts.has(link.id),
        };
    });
}

function injectLinkInformationInContainers(
    containers: ReadonlyArray<ArtifactReportContainer>,
    all_artifacts: Map<number, ArtifactResponse>,
    exported_artifacts: Map<number, ArtifactResponse>,
): ReadonlyArray<ArtifactReportContainer> {
    return containers.map((container) => {
        return {
            ...container,
            values: injectLinkInformationInValues(
                container.values,
                all_artifacts,
                exported_artifacts,
            ),
            containers: injectLinkInformationInContainers(
                container.containers,
                all_artifacts,
                exported_artifacts,
            ),
        };
    });
}

async function extractFieldValuesWithAdditionalInfoInStructuredContainers(
    artifact_id: number,
    structure_elements: ReadonlyArray<StructureFormat>,
    field_values: ReadonlyMap<number, ArtifactReportResponseFieldValue>,
    fields_structure: ReadonlyMap<number, FieldsStructure>,
    get_test_execution: typeof getTestManagementExecution,
    all_linked_artifacts_ids: Set<number>,
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
                    get_test_execution,
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
                    all_linked_artifacts_ids,
                ),
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
                    all_linked_artifacts_ids,
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
                all_linked_artifacts_ids,
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
    get_test_execution: typeof getTestManagementExecution,
): Promise<ArtifactStepExecutionFieldValue> {
    try {
        const test_execution: TestExecutionResponse = await get_test_execution(artifact_id);

        const test_execution_status: Array<TestExecStatus | null> = [];
        const test_executions: Array<ArtifactReportResponseStepRepresentationEnhanced> = [];

        for (const test_definition of test_execution.definition.steps) {
            if (test_definition.id.toString() in test_execution.steps_results) {
                test_execution_status.push(
                    test_execution.steps_results[test_definition.id.toString()].status,
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
    all_linked_artifacts_ids: Set<number>,
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
                all_linked_artifacts_ids.add(link.id),
            );

            return {
                ...value,
                links: value.links.map((link) => {
                    return {
                        ...link,
                        title: "",
                        html_url: "",
                        is_linked_artifact_part_of_document: false,
                    };
                }),
                reverse_links: value.reverse_links.map((link) => {
                    return {
                        ...link,
                        title: "",
                        html_url: "",
                        is_linked_artifact_part_of_document: false,
                    };
                }),
            };
        }
        default:
            return value;
    }
}
