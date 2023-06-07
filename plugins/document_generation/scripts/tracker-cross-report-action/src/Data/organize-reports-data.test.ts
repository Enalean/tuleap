/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { describe, it, expect, vi } from "vitest";
import { organizeReportsData } from "./organize-reports-data";
import * as rest_querier from "../rest-querier";
import type {
    OrganizedReportsData,
    LinkedArtifactsResponse,
    ArtifactForCrossReportDocGen,
} from "../type";

describe("organize-reports-data", () => {
    it("organizes the reports data with all 3 levels that will be used to create the XLSX document", async (): Promise<void> => {
        const artifacts_first_report_response: ArtifactForCrossReportDocGen[] = [
            {
                id: 74,
            } as ArtifactForCrossReportDocGen,
            {
                id: 4,
            } as ArtifactForCrossReportDocGen,
        ];

        const linked_artifacts_collection_first_level_4: LinkedArtifactsResponse[] = [
            {
                collection: [
                    {
                        id: 75,
                    } as ArtifactForCrossReportDocGen,
                    {
                        id: 750,
                    } as ArtifactForCrossReportDocGen,
                ],
            },
        ];
        const linked_artifacts_collection_first_level_74: LinkedArtifactsResponse[] = [
            {
                collection: [
                    {
                        id: 76,
                    } as ArtifactForCrossReportDocGen,
                    {
                        id: 77,
                    } as ArtifactForCrossReportDocGen,
                ],
            },
        ];

        const artifacts_second_report_response: ArtifactForCrossReportDocGen[] = [
            {
                id: 75,
            } as ArtifactForCrossReportDocGen,
            {
                id: 76,
            } as ArtifactForCrossReportDocGen,
        ];

        const artifacts_third_report_response: ArtifactForCrossReportDocGen[] = [
            {
                id: 80,
            } as ArtifactForCrossReportDocGen,
        ];
        const linked_artifacts_collection_second_level_76: LinkedArtifactsResponse[] = [
            {
                collection: [
                    {
                        id: 80,
                    } as ArtifactForCrossReportDocGen,
                ],
            },
        ];

        const getReportArtifactsMock = vi.spyOn(rest_querier, "getReportArtifacts");
        getReportArtifactsMock.mockImplementation(
            (report_id: number): Promise<ArtifactForCrossReportDocGen[]> => {
                if (report_id === 1) {
                    return Promise.resolve(artifacts_first_report_response);
                }
                if (report_id === 2) {
                    return Promise.resolve(artifacts_second_report_response);
                }
                if (report_id === 3) {
                    return Promise.resolve(artifacts_third_report_response);
                }
                throw Error("Unknown report id");
            }
        );

        const getLinkedArtifactsMock = vi.spyOn(rest_querier, "getLinkedArtifacts");
        getLinkedArtifactsMock.mockImplementation(
            (artifact_id: number): Promise<LinkedArtifactsResponse[]> => {
                if (artifact_id === 74) {
                    return Promise.resolve(linked_artifacts_collection_first_level_74);
                }
                if (artifact_id === 4) {
                    return Promise.resolve(linked_artifacts_collection_first_level_4);
                }
                if (artifact_id === 76) {
                    return Promise.resolve(linked_artifacts_collection_second_level_76);
                }
                if (artifact_id === 75) {
                    return Promise.resolve([]);
                }
                throw Error("Unknown artifact id " + artifact_id);
            }
        );

        const organized_reports_data: OrganizedReportsData = await organizeReportsData({
            first_level: {
                tracker_name: "tracker01",
                report_id: 1,
                report_name: "report01",
                artifact_link_types: ["_is_child"],
            },
            second_level: {
                tracker_name: "tracker02",
                report_id: 2,
                report_name: "report02",
                artifact_link_types: [""],
            },
            third_level: {
                tracker_name: "tracker03",
                report_id: 3,
                report_name: "report03",
            },
        });

        const expected_first_level_artifact_representations_map: Map<
            number,
            ArtifactForCrossReportDocGen
        > = new Map();
        expected_first_level_artifact_representations_map.set(74, {
            id: 74,
        } as ArtifactForCrossReportDocGen);
        expected_first_level_artifact_representations_map.set(4, {
            id: 4,
        } as ArtifactForCrossReportDocGen);

        const expected_second_level_artifact_representations_map: Map<
            number,
            ArtifactForCrossReportDocGen
        > = new Map();
        expected_second_level_artifact_representations_map.set(76, {
            id: 76,
        } as ArtifactForCrossReportDocGen);
        expected_second_level_artifact_representations_map.set(75, {
            id: 75,
        } as ArtifactForCrossReportDocGen);

        const expected_third_level_artifact_representations_map: Map<
            number,
            ArtifactForCrossReportDocGen
        > = new Map();
        expected_third_level_artifact_representations_map.set(80, {
            id: 80,
        } as ArtifactForCrossReportDocGen);

        expect(organized_reports_data).toStrictEqual({
            first_level: {
                tracker_name: "tracker01",
                artifact_representations: expected_first_level_artifact_representations_map,
                linked_artifacts: new Map([
                    [74, [76]],
                    [4, [75]],
                ]),
            },
            second_level: {
                tracker_name: "tracker02",
                artifact_representations: expected_second_level_artifact_representations_map,
                linked_artifacts: new Map([[76, [80]]]),
            },
            third_level: {
                tracker_name: "tracker03",
                artifact_representations: expected_third_level_artifact_representations_map,
            },
        });
    });
    it("organizes the reports data with the 2 first levels that will be used to create the XLSX document", async (): Promise<void> => {
        const artifacts_first_report_response: ArtifactForCrossReportDocGen[] = [
            {
                id: 74,
            } as ArtifactForCrossReportDocGen,
            {
                id: 4,
            } as ArtifactForCrossReportDocGen,
        ];

        const linked_artifacts_collection: LinkedArtifactsResponse[] = [
            {
                collection: [
                    {
                        id: 75,
                    } as ArtifactForCrossReportDocGen,
                    {
                        id: 750,
                    } as ArtifactForCrossReportDocGen,
                ],
            },
        ];

        const artifacts_second_report_response: ArtifactForCrossReportDocGen[] = [
            {
                id: 75,
            } as ArtifactForCrossReportDocGen,
        ];

        const getReportArtifactsMock = vi.spyOn(rest_querier, "getReportArtifacts");
        getReportArtifactsMock.mockImplementation(
            (report_id: number): Promise<ArtifactForCrossReportDocGen[]> => {
                if (report_id === 1) {
                    return Promise.resolve(artifacts_first_report_response);
                }
                if (report_id === 2) {
                    return Promise.resolve(artifacts_second_report_response);
                }
                throw Error("Unknown report id");
            }
        );

        const getLinkedArtifactsMock = vi.spyOn(rest_querier, "getLinkedArtifacts");
        getLinkedArtifactsMock.mockImplementation(
            (artifact_id: number): Promise<LinkedArtifactsResponse[]> => {
                if (artifact_id === 74) {
                    return Promise.resolve(linked_artifacts_collection);
                }
                if (artifact_id === 4) {
                    return Promise.resolve([]);
                }
                throw Error("Unknown artifact id");
            }
        );

        const organized_reports_data: OrganizedReportsData = await organizeReportsData({
            first_level: {
                tracker_name: "tracker01",
                report_id: 1,
                report_name: "report01",
                artifact_link_types: ["_is_child"],
            },
            second_level: {
                tracker_name: "tracker02",
                report_id: 2,
                report_name: "report02",
                artifact_link_types: [],
            },
        });

        const expected_first_level_artifact_representations_map: Map<
            number,
            ArtifactForCrossReportDocGen
        > = new Map();
        expected_first_level_artifact_representations_map.set(74, {
            id: 74,
        } as ArtifactForCrossReportDocGen);
        expected_first_level_artifact_representations_map.set(4, {
            id: 4,
        } as ArtifactForCrossReportDocGen);

        const expected_second_level_artifact_representations_map: Map<
            number,
            ArtifactForCrossReportDocGen
        > = new Map();
        expected_second_level_artifact_representations_map.set(75, {
            id: 75,
        } as ArtifactForCrossReportDocGen);

        expect(organized_reports_data).toStrictEqual({
            first_level: {
                tracker_name: "tracker01",
                artifact_representations: expected_first_level_artifact_representations_map,
                linked_artifacts: new Map([[74, [75]]]),
            },
            second_level: {
                tracker_name: "tracker02",
                artifact_representations: expected_second_level_artifact_representations_map,
                linked_artifacts: new Map(),
            },
        });
    });
    it("organizes the reports data with the 2 first levels that will be used to create the XLSX document with multiple link types", async (): Promise<void> => {
        const artifacts_first_report_response: ArtifactForCrossReportDocGen[] = [
            {
                id: 74,
            } as ArtifactForCrossReportDocGen,
            {
                id: 4,
            } as ArtifactForCrossReportDocGen,
        ];

        const linked_child_artifacts_collection: LinkedArtifactsResponse[] = [
            {
                collection: [
                    {
                        id: 75,
                    } as ArtifactForCrossReportDocGen,
                    {
                        id: 750,
                    } as ArtifactForCrossReportDocGen,
                ],
            },
        ];

        const linked_another_artifacts_collection: LinkedArtifactsResponse[] = [
            {
                collection: [
                    {
                        id: 76,
                    } as ArtifactForCrossReportDocGen,
                ],
            },
        ];

        const artifacts_second_report_response: ArtifactForCrossReportDocGen[] = [
            {
                id: 75,
            } as ArtifactForCrossReportDocGen,
            {
                id: 76,
            } as ArtifactForCrossReportDocGen,
        ];

        const getReportArtifactsMock = vi.spyOn(rest_querier, "getReportArtifacts");
        getReportArtifactsMock.mockImplementation(
            (report_id: number): Promise<ArtifactForCrossReportDocGen[]> => {
                if (report_id === 1) {
                    return Promise.resolve(artifacts_first_report_response);
                }
                if (report_id === 2) {
                    return Promise.resolve(artifacts_second_report_response);
                }
                throw Error("Unknown report id");
            }
        );

        const getLinkedArtifactsMock = vi.spyOn(rest_querier, "getLinkedArtifacts");
        getLinkedArtifactsMock.mockImplementation(
            (
                artifact_id: number,
                artifact_link_type: string
            ): Promise<LinkedArtifactsResponse[]> => {
                if (artifact_id === 74 && artifact_link_type === "_is_child") {
                    return Promise.resolve(linked_child_artifacts_collection);
                }
                if (artifact_id === 74 && artifact_link_type === "another") {
                    return Promise.resolve(linked_another_artifacts_collection);
                }
                if (artifact_id === 4) {
                    return Promise.resolve([]);
                }
                throw Error("Unknown artifact id");
            }
        );

        const organized_reports_data: OrganizedReportsData = await organizeReportsData({
            first_level: {
                tracker_name: "tracker01",
                report_id: 1,
                report_name: "report01",
                artifact_link_types: ["_is_child", "another"],
            },
            second_level: {
                tracker_name: "tracker02",
                report_id: 2,
                report_name: "report02",
                artifact_link_types: [],
            },
        });

        const expected_first_level_artifact_representations_map: Map<
            number,
            ArtifactForCrossReportDocGen
        > = new Map();
        expected_first_level_artifact_representations_map.set(74, {
            id: 74,
        } as ArtifactForCrossReportDocGen);
        expected_first_level_artifact_representations_map.set(4, {
            id: 4,
        } as ArtifactForCrossReportDocGen);

        const expected_second_level_artifact_representations_map: Map<
            number,
            ArtifactForCrossReportDocGen
        > = new Map();
        expected_second_level_artifact_representations_map.set(75, {
            id: 75,
        } as ArtifactForCrossReportDocGen);
        expected_second_level_artifact_representations_map.set(76, {
            id: 76,
        } as ArtifactForCrossReportDocGen);

        expect(organized_reports_data).toStrictEqual({
            first_level: {
                tracker_name: "tracker01",
                artifact_representations: expected_first_level_artifact_representations_map,
                linked_artifacts: new Map([[74, [76, 75]]]),
            },
            second_level: {
                tracker_name: "tracker02",
                artifact_representations: expected_second_level_artifact_representations_map,
                linked_artifacts: new Map(),
            },
        });
    });
    it("generates empty organized data if no artifact found", async (): Promise<void> => {
        const artifacts_report_response: ArtifactForCrossReportDocGen[] = [];
        vi.spyOn(rest_querier, "getReportArtifacts").mockResolvedValue(artifacts_report_response);

        const organized_reports_data: OrganizedReportsData = await organizeReportsData({
            first_level: {
                tracker_name: "tracker01",
                report_id: 1,
                report_name: "report01",
                artifact_link_types: [],
            },
        });

        expect(organized_reports_data).toStrictEqual({
            first_level: {
                artifact_representations: new Map(),
                tracker_name: "tracker01",
                linked_artifacts: new Map(),
            },
        });
    });
});
