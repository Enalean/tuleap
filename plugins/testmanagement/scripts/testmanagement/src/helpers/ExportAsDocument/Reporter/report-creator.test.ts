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

import { createExportReport } from "./report-creator";
import type { Campaign } from "../../../type";
import * as docgen_docx from "@tuleap/plugin-docgen-docx";
import type {
    ArtifactResponse,
    FieldsStructure,
    TrackerStructure,
    ArtifactFromReport,
    FormattedArtifact,
    TestExecutionResponse,
} from "@tuleap/plugin-docgen-docx";
import * as querier from "../../../../../../../testmanagement/scripts/testmanagement/src/helpers/ExportAsDocument/Reporter/execution-querier";
import type { ArtifactFieldValueStepDefinitionContent } from "@tuleap/plugin-docgen-docx";
import { createGettextProviderPassthrough } from "../../create-gettext-provider-passthrough-for-tests";

describe("Create an export report", () => {
    it("generates the report with backlog coming from requirements", async () => {
        const gettext_provider = createGettextProviderPassthrough();

        const retrieveTrackerStructureMock = jest.spyOn(docgen_docx, "retrieveTrackerStructure");
        retrieveTrackerStructureMock.mockImplementation(
            (tracker_id: number): Promise<TrackerStructure> => {
                if (tracker_id === 111) {
                    return Promise.resolve({
                        fields: new Map([[1, { type: "sb" } as FieldsStructure]]),
                        disposition: [],
                    });
                }
                if (tracker_id === 112) {
                    return Promise.resolve({
                        fields: new Map([[2, { type: "msb" } as FieldsStructure]]),
                        disposition: [],
                    });
                }
                if (tracker_id === 10) {
                    return Promise.resolve({
                        fields: new Map([[3, { type: "sb" } as FieldsStructure]]),
                        disposition: [],
                    });
                }
                throw Error("Unknown tracker id");
            }
        );

        const getArtifactsMock = jest.spyOn(docgen_docx, "getArtifacts");
        getArtifactsMock.mockResolvedValue(
            new Map([
                [123, { id: 123 } as ArtifactResponse],
                [1231, { id: 1231 } as ArtifactResponse],
                [1232, { id: 1232 } as ArtifactResponse],
            ])
        );

        const retrieveArtifactsStructureMock = jest.spyOn(
            docgen_docx,
            "retrieveArtifactsStructure"
        );
        retrieveArtifactsStructureMock.mockResolvedValue([
            { id: 123 } as ArtifactFromReport,
            { id: 1231 } as ArtifactFromReport,
            { id: 1232 } as ArtifactFromReport,
        ]);

        const formatArtifactMock = jest.spyOn(docgen_docx, "formatArtifact");
        formatArtifactMock.mockImplementation(
            (
                artifact: ArtifactFromReport
            ): FormattedArtifact<ArtifactFieldValueStepDefinitionContent> => {
                if (artifact.id === 123) {
                    return {
                        id: 123,
                    } as FormattedArtifact<ArtifactFieldValueStepDefinitionContent>;
                }
                if (artifact.id === 1231) {
                    return {
                        id: 1231,
                    } as FormattedArtifact<ArtifactFieldValueStepDefinitionContent>;
                }
                if (artifact.id === 1232) {
                    return {
                        id: 1231,
                    } as FormattedArtifact<ArtifactFieldValueStepDefinitionContent>;
                }
                throw Error("Unknown artifact");
            }
        );

        jest.spyOn(querier, "getExecutions").mockResolvedValue([
            {
                definition: {
                    artifact: {
                        id: 123,
                    } as ArtifactResponse,
                    id: 123,
                    summary: "Test A",
                    all_requirements: [
                        {
                            id: 1231,
                            title: "Lorem",
                            tracker: {
                                id: 111,
                            },
                        },
                        {
                            id: 1232,
                            title: "Ipsum",
                            tracker: {
                                id: 112,
                            },
                        },
                    ],
                },
                previous_result: {
                    status: "passed",
                    submitted_on: "2020-06-23T08:01:04-04:00",
                    submitted_by: {
                        display_name: "John Doe",
                    },
                },
            } as unknown as TestExecutionResponse,
        ]);

        const report = await createExportReport(
            gettext_provider,
            {
                platform_name: "My Tuleap Platform",
                platform_logo_url: "platform/logo/url",
                project_name: "ACME",
                user_display_name: "Jean Dupont",
                user_timezone: "UTC",
                user_locale: "en_US",
                title: "Tuleap 13.5",
                base_url: "https://example.com",
                artifact_links_types: [],
                testdefinition_tracker_id: 10,
            },
            { id: 101, label: "Tuleap 13.5" } as Campaign,
            { locale: "en-US", timezone: "UTC" }
        );

        expect(retrieveTrackerStructureMock).toHaveBeenCalledTimes(3);
        expect(report.tests).toHaveLength(1);
        expect(report.backlog).toHaveLength(2);
        expect(report.name).toBe("Test campaign Tuleap 13.5");
    });
});
