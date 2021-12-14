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

import { createVueGettextProviderPassthrough } from "../../vue-gettext-provider-for-test";
import { createExportReport } from "./report-creator";
import type { BacklogItem } from "../../../type";
import * as docgen_docx from "@tuleap/plugin-docgen-docx";
import type {
    ArtifactResponse,
    FieldsStructure,
    TrackerStructure,
    ArtifactFromReport,
    FormattedArtifact,
} from "@tuleap/plugin-docgen-docx";

describe("Create an export report", () => {
    it("generates the report", async () => {
        const gettext_provider = createVueGettextProviderPassthrough();

        const retrieveTrackerStructureMock = jest.spyOn(docgen_docx, "retrieveTrackerStructure");
        retrieveTrackerStructureMock.mockImplementation(
            (tracker_id: number): Promise<TrackerStructure> => {
                if (tracker_id === 101) {
                    return Promise.resolve({
                        fields: new Map([[1, { type: "sb" } as FieldsStructure]]),
                        disposition: [],
                    });
                }
                if (tracker_id === 102) {
                    return Promise.resolve({
                        fields: new Map([[2, { type: "msb" } as FieldsStructure]]),
                        disposition: [],
                    });
                }
                throw Error("Unknown tracker id");
            }
        );

        const getArtifactsMock = jest.spyOn(docgen_docx, "getArtifacts");
        getArtifactsMock.mockResolvedValue(
            new Map([
                [1, { id: 1 } as ArtifactResponse],
                [2, { id: 2 } as ArtifactResponse],
                [3, { id: 3 } as ArtifactResponse],
            ])
        );

        const retrieveArtifactsStructureMock = jest.spyOn(
            docgen_docx,
            "retrieveArtifactsStructure"
        );
        retrieveArtifactsStructureMock.mockResolvedValue([
            { id: 1 } as ArtifactFromReport,
            { id: 2 } as ArtifactFromReport,
            { id: 3 } as ArtifactFromReport,
        ]);

        const formatArtifactMock = jest.spyOn(docgen_docx, "formatArtifact");
        formatArtifactMock.mockImplementation((artifact: ArtifactFromReport): FormattedArtifact => {
            if (artifact.id === 1) {
                return { id: 1 } as FormattedArtifact;
            }
            if (artifact.id === 2) {
                return { id: 2 } as FormattedArtifact;
            }
            if (artifact.id === 3) {
                return { id: 3 } as FormattedArtifact;
            }
            throw Error("Unknown artifact");
        });

        const report = await createExportReport(
            gettext_provider,
            {
                platform_name: "My Tuleap Platform",
                platform_logo_url: "platform/logo/url",
                project_name: "ACME",
                user_display_name: "Jean Dupont",
                user_timezone: "UTC",
                user_locale: "en_US",
                milestone_name: "Tuleap 13.3",
                parent_milestone_name: "",
                milestone_url: "/path/to/13.3",
                base_url: "https://example.com",
                artifact_links_types: [],
            },
            [
                {
                    artifact: {
                        id: 1,
                        tracker: {
                            id: 101,
                        },
                    },
                } as BacklogItem,
                {
                    artifact: {
                        id: 2,
                        tracker: {
                            id: 102,
                        },
                    },
                } as BacklogItem,
                {
                    artifact: {
                        id: 3,
                        tracker: {
                            id: 101,
                        },
                    },
                } as BacklogItem,
            ],
            { locale: "en-US", timezone: "UTC" }
        );

        expect(retrieveTrackerStructureMock).toHaveBeenCalledTimes(2);
        expect(report.backlog.length).toBe(3);
    });
});
