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
    ArtifactContainer,
    ArtifactFieldValue,
    ArtifactFieldValueArtifactLink,
    ArtifactFieldValueStepDefinitionEnhanced,
    ArtifactFromReport,
    ArtifactLink,
    ArtifactLinkType,
    ArtifactReportContainer,
    ArtifactReportFieldValue,
    DateTimeLocaleInformation,
    FormattedArtifact,
    TransformStepDefFieldValue,
} from "../type";

export function formatArtifact<StepDefFieldValue>(
    artifact: ArtifactFromReport,
    datetime_locale_information: DateTimeLocaleInformation,
    base_url: string,
    artifact_links_types: ReadonlyArray<ArtifactLinkType>,
    transform_step_def_field: TransformStepDefFieldValue<StepDefFieldValue>,
): FormattedArtifact<StepDefFieldValue> {
    const artifact_id = artifact.id;
    const artifact_title = artifact.title;

    const xref = artifact.xref;
    let formatted_title = xref;
    if (artifact_title) {
        formatted_title += " - " + artifact_title;
    }
    const short_title = artifact_title ? artifact_title : xref;

    return {
        id: artifact_id,
        title: formatted_title,
        short_title,
        fields: formatFieldValues(
            artifact.values,
            datetime_locale_information,
            base_url,
            artifact_links_types,
            transform_step_def_field,
        ),
        containers: formatContainers(
            artifact.containers,
            datetime_locale_information,
            base_url,
            artifact_links_types,
            transform_step_def_field,
        ),
    };
}

function formatFieldValues<StepDefFieldValue>(
    values: ReadonlyArray<ArtifactReportFieldValue>,
    datetime_locale_information: DateTimeLocaleInformation,
    base_url: string,
    artifact_links_types: ReadonlyArray<ArtifactLinkType>,
    transform_step_def_field: TransformStepDefFieldValue<StepDefFieldValue>,
): ReadonlyArray<ArtifactFieldValue<StepDefFieldValue>> {
    return values.flatMap((value) => {
        const formatted_field_value = formatFieldValue(
            value,
            datetime_locale_information,
            base_url,
            artifact_links_types,
            transform_step_def_field,
        );
        if (formatted_field_value === null) {
            return [];
        }
        return [formatted_field_value];
    });
}

function formatFieldValue<StepDefFieldValue>(
    value: ArtifactReportFieldValue,
    datetime_locale_information: DateTimeLocaleInformation,
    base_url: string,
    artifact_links_types: ReadonlyArray<ArtifactLinkType>,
    transform_step_def_field: TransformStepDefFieldValue<StepDefFieldValue>,
): ArtifactFieldValue<StepDefFieldValue> | null {
    if (value.type === "text") {
        return {
            field_name: value.label,
            field_value: value.value ?? "",
            content_length: "long",
            content_format: value.format === "html" ? "html" : "plaintext",
            value_type: "string",
        };
    }

    if (value.type === "ttmstepdef") {
        return transform_step_def_field(base_url, value);
    }

    if (value.type === "ttmstepexec") {
        if (value.value === null) {
            return null;
        }

        const steps: ArtifactFieldValueStepDefinitionEnhanced[] = [];
        for (const step of value.value.steps) {
            steps.push({
                description: step.description,
                description_format: step.description_format === "html" ? "html" : "plaintext",
                expected_results: step.expected_results,
                expected_results_format:
                    step.expected_results_format === "html" ? "html" : "plaintext",
                rank: step.rank,
                status: step.status ?? "notrun",
            });
        }

        return {
            field_name: value.label,
            content_length: "blockttmstepexec",
            value_type: "string",
            steps: steps,
            steps_values: value.value.steps_values.map((status) => status ?? "notrun"),
        };
    }

    let artifact_field_value = "";
    if (
        value.type === "aid" ||
        value.type === "atid" ||
        value.type === "int" ||
        value.type === "float" ||
        value.type === "priority"
    ) {
        if (value.value !== null) {
            artifact_field_value = value.value.toString();
        }
    } else if (value.type === "string") {
        if (value.value !== null) {
            artifact_field_value = value.value;
        }
    } else if (value.type === "date" || value.type === "lud" || value.type === "subon") {
        if (value.value !== null) {
            const date_value = new Date(value.value);
            const { locale, timezone } = datetime_locale_information;
            artifact_field_value = date_value.toLocaleDateString(locale, {
                timeZone: timezone,
            });
            if (value.is_time_displayed) {
                artifact_field_value += ` ${date_value.toLocaleTimeString(locale, {
                    timeZone: timezone,
                })}`;
            }
        }
    } else if (value.type === "computed") {
        if (!value.is_autocomputed && value.manual_value !== null) {
            artifact_field_value = value.manual_value.toString();
        } else if (value.is_autocomputed && value.value !== null) {
            artifact_field_value = value.value.toString();
        }
    } else if (value.type === "file") {
        const file_descriptions_content = [];
        for (const file_description of value.file_descriptions) {
            file_descriptions_content.push({
                link_label: file_description.name,
                link_url: new URL(base_url.replace(/\/$/, "") + file_description.html_url).href,
            });
        }
        return {
            field_name: value.label,
            field_value: file_descriptions_content,
            content_length: "short",
            value_type: "links",
        };
    } else if (value.type === "subby" || value.type === "luby") {
        artifact_field_value = value.value.display_name;
    } else if (
        value.type === "sb" ||
        value.type === "rb" ||
        value.type === "msb" ||
        value.type === "cb"
    ) {
        artifact_field_value = value.formatted_values.join(", ");
    } else if (value.type === "tbl") {
        artifact_field_value = value.formatted_open_values.join(", ");
    } else if (value.type === "perm") {
        artifact_field_value = value.formatted_granted_ugroups.join(", ");
    } else if (value.type === "cross") {
        const references_content = [];
        for (const cross_reference of value.value) {
            references_content.push({
                link_label: cross_reference.ref,
                link_url: cross_reference.url,
            });
        }
        return {
            field_name: value.label,
            field_value: references_content,
            content_length: "short",
            value_type: "links",
        };
    } else if (value.type === "art_link") {
        const links: ArtifactFieldValueArtifactLink[] = [];
        const reverse_links: ArtifactFieldValueArtifactLink[] = [];

        for (const link of value.links) {
            links.push({
                artifact_id: link.id,
                title: link.title,
                type: getArtifactLinkLabel(link, artifact_links_types),
                is_linked_artifact_part_of_document:
                    link?.is_linked_artifact_part_of_document || false,
                html_url: link.html_url
                    ? new URL(base_url.replace(/\/$/, "") + link.html_url)
                    : null,
            });
        }

        for (const reverse_link of value.reverse_links) {
            reverse_links.push({
                artifact_id: reverse_link.id,
                title: reverse_link.title,
                type: getArtifactLinkReverseLabel(reverse_link, artifact_links_types),
                is_linked_artifact_part_of_document:
                    reverse_link?.is_linked_artifact_part_of_document || false,
                html_url: reverse_link.html_url
                    ? new URL(base_url.replace(/\/$/, "") + reverse_link.html_url)
                    : null,
            });
        }

        return {
            field_name: value.label,
            content_length: "artlinktable",
            value_type: "string",
            links: links,
            reverse_links: reverse_links,
        };
    } else {
        return null;
    }

    return {
        field_name: value.label,
        field_value: artifact_field_value,
        content_length: "short",
        value_type: "string",
    };
}

function getArtifactLinkLabel(
    link: ArtifactLink,
    artifact_links_types: ReadonlyArray<ArtifactLinkType>,
): string {
    if (link.type === null) {
        return "";
    }

    for (const artifact_link_type of artifact_links_types) {
        if (artifact_link_type.shortname === link.type) {
            return artifact_link_type.forward_label;
        }
    }

    return "";
}

function getArtifactLinkReverseLabel(
    reverse_link: ArtifactLink,
    artifact_links_types: ReadonlyArray<ArtifactLinkType>,
): string {
    if (reverse_link.type === null) {
        return "";
    }

    for (const artifact_link_type of artifact_links_types) {
        if (artifact_link_type.shortname === reverse_link.type) {
            return artifact_link_type.reverse_label;
        }
    }

    return "";
}

function formatContainers<StepDefFieldValue>(
    containers: ReadonlyArray<ArtifactReportContainer>,
    datetime_locale_information: DateTimeLocaleInformation,
    base_url: string,
    artifact_links_types: ReadonlyArray<ArtifactLinkType>,
    transform_step_def_field: TransformStepDefFieldValue<StepDefFieldValue>,
): ReadonlyArray<ArtifactContainer<StepDefFieldValue>> {
    return containers.map((container) => {
        return {
            name: container.name,
            fields: formatFieldValues(
                container.values,
                datetime_locale_information,
                base_url,
                artifact_links_types,
                transform_step_def_field,
            ),
            containers: formatContainers(
                container.containers,
                datetime_locale_information,
                base_url,
                artifact_links_types,
                transform_step_def_field,
            ),
        };
    });
}
