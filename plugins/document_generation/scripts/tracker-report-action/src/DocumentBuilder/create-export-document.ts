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

import type { DateTimeLocaleInformation, ExportDocument } from "../type";
import { retrieveReportArtifacts } from "./artifacts-retriever";

export async function createExportDocument(
    report_id: number,
    report_has_changed: boolean,
    report_name: string,
    tracker_id: number,
    tracker_shortname: string,
    datetime_locale_information: DateTimeLocaleInformation
): Promise<ExportDocument> {
    const report_artifacts = await retrieveReportArtifacts(
        tracker_id,
        report_id,
        report_has_changed
    );

    const artifact_data = [];
    for (const artifact of report_artifacts) {
        const artifact_id = artifact.id;
        const artifact_title = artifact.title;
        const fields_content = [];
        for (const value of artifact.values) {
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
            } else {
                continue;
            }

            fields_content.push({
                field_name: value.label,
                field_value: artifact_field_value,
            });
        }
        let formatted_title = tracker_shortname + " #" + artifact.id;
        if (artifact_title !== null) {
            formatted_title += " - " + artifact_title;
        }
        artifact_data.push({
            id: artifact_id,
            title: formatted_title,
            fields: fields_content,
        });
    }

    return { name: `${tracker_shortname} - ${report_name}`, artifacts: artifact_data };
}
