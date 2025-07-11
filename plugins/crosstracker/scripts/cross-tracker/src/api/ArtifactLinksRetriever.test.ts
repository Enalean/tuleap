/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { describe, it, vi, expect } from "vitest";
import { okAsync } from "neverthrow";
import * as fetch_result from "@tuleap/fetch-result";
import { SelectableQueryContentRepresentationStub } from "../../tests/builders/SelectableQueryContentRepresentationStub";
import { ArtifactRepresentationStub } from "../../tests/builders/ArtifactRepresentationStub";
import type { RetrieveArtifactLinks } from "../domain/RetrieveArtifactLinks";
import { ArtifactLinksRetriever } from "./ArtifactLinksRetriever";
import { ArtifactsTableBuilder } from "./ArtifactsTableBuilder";
import type { ArtifactsTable } from "../domain/ArtifactsTable";
import { FORWARD_DIRECTION, REVERSE_DIRECTION } from "../domain/ArtifactsTable";

describe("ArtifactsLinksRetriever", () => {
    const widget_id = 109;
    const artifact_id = 34;

    const getRetriever = (): RetrieveArtifactLinks => {
        return ArtifactLinksRetriever(ArtifactsTableBuilder());
    };

    it.each([
        [
            FORWARD_DIRECTION,
            getRetriever().getForwardLinks,
            {
                source_artifact_id: artifact_id,
                tql_query: 'SELECT @pretty_title FROM @project="self"',
                limit: 50,
            },
        ],
        [
            REVERSE_DIRECTION,
            getRetriever().getReverseLinks,
            {
                target_artifact_id: artifact_id,
                tql_query: 'SELECT @pretty_title FROM @project="self"',
                limit: 50,
            },
        ],
    ])(
        "should call for the %s links linked to an artifact and return an ArtifactTable accordingly",
        async (direction, retriever_call, params) => {
            const date_field_name = "start_date";
            const first_date_value = "2022-04-27T11:54:15+07:00";
            const query_content = SelectableQueryContentRepresentationStub.build(
                [{ type: "date", name: date_field_name }],
                [
                    ArtifactRepresentationStub.build({
                        [date_field_name]: { value: first_date_value, with_time: true },
                    }),
                    ArtifactRepresentationStub.build({
                        [date_field_name]: { value: null, with_time: false },
                    }),
                ],
            );
            const getAllJSON = vi
                .spyOn(fetch_result, "getAllJSON")
                .mockReturnValue(okAsync([query_content]));

            const result = await retriever_call(
                widget_id,
                artifact_id,
                'SELECT @pretty_title FROM @project="self"',
            );

            expect(getAllJSON).toHaveBeenCalledWith(
                fetch_result.uri`/api/v1/crosstracker_widget/${widget_id}/${direction}_links`,
                {
                    params,
                },
            );
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            result.match(
                (artifacts: ArtifactsTable[]) => {
                    artifacts.forEach((artifact) => {
                        expect(artifact.columns).toHaveLength(2);
                        expect(artifact.rows).toHaveLength(2);
                    });
                },
                (error) => {
                    throw new Error(`Unexpected error: ${error}`);
                },
            );
        },
    );
});
