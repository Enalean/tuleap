/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import type { ArtifactContainer, DateTimeLocaleInformation, ExportDocument } from "../type";
import type { ArtifactReportContainer, ArtifactReportFieldValue } from "./artifacts-retriever";
import { retrieveReportArtifacts } from "./artifacts-retriever";
import type { ArtifactFieldValue, FormattedArtifact } from "../type";

export async function createExportDocument(
    report_id: number,
    report_has_changed: boolean,
    report_name: string,
    tracker_id: number,
    tracker_shortname: string,
    datetime_locale_information: DateTimeLocaleInformation,
    base_url: string
): Promise<ExportDocument> {
    const report_artifacts = await retrieveReportArtifacts(
        tracker_id,
        report_id,
        report_has_changed
    );

    const artifact_data: FormattedArtifact[] = [];
    for (const artifact of report_artifacts) {
        const artifact_id = artifact.id;
        const artifact_title = artifact.title;

        let formatted_title = tracker_shortname + " #" + artifact.id;
        if (artifact_title !== null) {
            formatted_title += " - " + artifact_title;
        }
        artifact_data.push({
            id: artifact_id,
            title: formatted_title,
            fields: formatFieldValues(artifact.values, datetime_locale_information, base_url),
            containers: formatContainers(
                artifact.containers,
                datetime_locale_information,
                base_url
            ),
        });
    }

    return { name: `${tracker_shortname} - ${report_name}`, artifacts: artifact_data };
}

function formatFieldValues(
    values: ReadonlyArray<ArtifactReportFieldValue>,
    datetime_locale_information: DateTimeLocaleInformation,
    base_url: string
): ReadonlyArray<ArtifactFieldValue> {
    return values.flatMap((value) => {
        const formatted_field_value = formatFieldValue(
            value,
            datetime_locale_information,
            base_url
        );
        if (formatted_field_value === null) {
            return [];
        }
        return [formatted_field_value];
    });
}

function formatFieldValue(
    value: ArtifactReportFieldValue,
    datetime_locale_information: DateTimeLocaleInformation,
    base_url: string
): ArtifactFieldValue | null {
    if (value.type === "text") {
        return {
            field_name: value.label,
            field_value: value.value ?? "",
            content_length: "long",
            content_format: value.format === "html" ? "html" : "plaintext",
            value_type: "string",
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
    } else if (value.type === "sb") {
        artifact_field_value = value.formatted_values.join(", ");
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

function formatContainers(
    containers: ReadonlyArray<ArtifactReportContainer>,
    datetime_locale_information: DateTimeLocaleInformation,
    base_url: string
): ReadonlyArray<ArtifactContainer> {
    return containers.map((container) => {
        return {
            name: container.name,
            fields: formatFieldValues(container.values, datetime_locale_information, base_url),
            containers: formatContainers(
                container.containers,
                datetime_locale_information,
                base_url
            ),
        };
    });
}
