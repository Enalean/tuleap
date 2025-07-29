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

import type { ResultAsync } from "neverthrow";
import { okAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { PRETTY_TITLE_CELL } from "../../src/domain/ArtifactsTable";
import type { RetrieveArtifactLinks } from "../../src/domain/RetrieveArtifactLinks";
import type { ArtifactsTableWithTotal } from "../../src/domain/RetrieveArtifactsTable";
import { ArtifactsTableBuilder } from "../builders/ArtifactsTableBuilder";
import { ArtifactRowBuilder } from "../builders/ArtifactRowBuilder";
import { PRETTY_TITLE_COLUMN_NAME } from "../../src/domain/ColumnName";
import { MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED } from "../../src/api/ArtifactLinksRetriever";

const direct_parent_row = new ArtifactRowBuilder()
    .addCell(PRETTY_TITLE_COLUMN_NAME, {
        type: PRETTY_TITLE_CELL,
        title: "earthmaking",
        tracker_name: "lifesome",
        artifact_id: 512,
        color: "inca-silver",
    })
    .withRowId(512)
    .buildWithExpectedNumberOfLinks(1, 1);

const artifact_row = new ArtifactRowBuilder()
    .addCell(PRETTY_TITLE_COLUMN_NAME, {
        type: PRETTY_TITLE_CELL,
        title: "earthmaking",
        tracker_name: "lifesome",
        artifact_id: 512,
        color: "inca-silver",
    })
    .buildWithExpectedNumberOfLinks(1, 1);

export const RetrieveArtifactLinksStub = {
    withTotalNumberOfLinks(
        total_number_of_forward_links: number,
        total_number_of_reverse_links: number,
    ): RetrieveArtifactLinks {
        return {
            getForwardLinks: () =>
                okAsync(
                    new ArtifactsTableBuilder()
                        .withTotalNumberOfRow(
                            artifact_row,
                            Math.min(
                                MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED,
                                total_number_of_forward_links,
                            ),
                        )
                        .buildWithTotal(total_number_of_forward_links),
                ),
            getReverseLinks: () =>
                okAsync(
                    new ArtifactsTableBuilder()
                        .withTotalNumberOfRow(direct_parent_row, 1)
                        .withTotalNumberOfRow(
                            artifact_row,
                            Math.min(
                                MAXIMAL_LIMIT_OF_ARTIFACT_LINKS_FETCHED,
                                total_number_of_reverse_links - 1,
                            ),
                        )
                        .buildWithTotal(total_number_of_reverse_links),
                ),
            getAllForwardLinks: () =>
                okAsync([
                    new ArtifactsTableBuilder()
                        .withTotalNumberOfRow(artifact_row, total_number_of_forward_links)
                        .build(),
                ]),
            getAllReverseLinks: () =>
                okAsync([
                    new ArtifactsTableBuilder()
                        .withTotalNumberOfRow(direct_parent_row, 1)
                        .withTotalNumberOfRow(artifact_row, total_number_of_reverse_links - 1)
                        .build(),
                ]),
        };
    },

    withForwardAndReverseContent(
        forward_links: ResultAsync<ArtifactsTableWithTotal, Fault>,
        reverse_links: ResultAsync<ArtifactsTableWithTotal, Fault>,
    ): RetrieveArtifactLinks {
        return {
            getForwardLinks: () => forward_links,
            getReverseLinks: () => reverse_links,
            getAllForwardLinks: () => okAsync([new ArtifactsTableBuilder().build()]),
            getAllReverseLinks: () => okAsync([new ArtifactsTableBuilder().build()]),
        };
    },

    withDefaultContent(): RetrieveArtifactLinks {
        return {
            getForwardLinks: () => okAsync(new ArtifactsTableBuilder().buildWithTotal(1)),
            getReverseLinks: () => okAsync(new ArtifactsTableBuilder().buildWithTotal(1)),
            getAllForwardLinks: () => okAsync([new ArtifactsTableBuilder().build()]),
            getAllReverseLinks: () => okAsync([new ArtifactsTableBuilder().build()]),
        };
    },
};
