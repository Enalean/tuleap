/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

import { getJSON, putJSON, uri } from "@tuleap/fetch-result";
import { type ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { Report } from "../type";
import type { ReportRepresentation } from "./cross-tracker-rest-api-types";

export function getReports(report_id: number): ResultAsync<ReadonlyArray<Report>, Fault> {
    return getJSON<ReadonlyArray<ReportRepresentation>>(
        uri`/api/v1/cross_tracker_reports/${report_id}`,
    ).map((reports): ReadonlyArray<Report> => {
        return reports.map((report) => {
            return {
                uuid: report.uuid,
                expert_query: report.expert_query,
                title: report.title,
                description: report.description,
            };
        });
    });
}

export function updateReport(report_id: number, expert_query: string): ResultAsync<Report, Fault> {
    return putJSON<ReportRepresentation>(uri`/api/v1/cross_tracker_reports/${report_id}`, {
        expert_query,
    }).map((report): Report => {
        return {
            uuid: report.uuid,
            expert_query: report.expert_query,
            title: report.title,
            description: report.description,
        };
    });
}
