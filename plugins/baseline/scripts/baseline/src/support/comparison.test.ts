/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

import type { ArtifactsListComparison } from "./comparison";
import { compareArtifacts } from "./comparison";
import type { Artifact } from "../type";

describe("comparison :", () => {
    describe("ArtifactsListComparison", () => {
        let comparison: ArtifactsListComparison;

        describe("when one artifact added", () => {
            const added_artifact = { id: 1 } as Artifact;
            beforeEach(() => (comparison = compareArtifacts([], [added_artifact])));

            it("#identical_or_modified returns empty array", () => {
                expect(comparison.identical_or_modified).toStrictEqual([]);
            });
            it("#modified returns empty array", () => {
                expect(comparison.modified).toStrictEqual([]);
            });
            it("#removed returns empty array", () => {
                expect(comparison.removed).toStrictEqual([]);
            });
            it("#added returns this artifact", () => {
                expect(comparison.added).toStrictEqual([added_artifact]);
            });
        });

        describe("when one artifact was removed", () => {
            const removed_artifact = { id: 1 } as Artifact;
            beforeEach(() => (comparison = compareArtifacts([removed_artifact], [])));

            it("#identical_or_modified returns empty array", () => {
                expect(comparison.identical_or_modified).toStrictEqual([]);
            });
            it("#modified returns empty array", () => {
                expect(comparison.modified).toStrictEqual([]);
            });
            it("#removed returns this artifact", () => {
                expect(comparison.removed).toStrictEqual([removed_artifact]);
            });
            it("#added returns empty array", () => {
                expect(comparison.added).toStrictEqual([]);
            });
        });

        describe("when one artifact was modified", () => {
            const base_artifact = {
                id: 1,
                description: "old description",
            } as Artifact;
            const compared_to_artifact = {
                id: 1,
                description: "new description",
            } as Artifact;

            beforeEach(
                () => (comparison = compareArtifacts([base_artifact], [compared_to_artifact])),
            );

            it("#identical_or_modified returns old and new artifact", () => {
                expect(comparison.identical_or_modified).toStrictEqual([
                    { base: base_artifact, compared_to: compared_to_artifact },
                ]);
            });
            it("#modified returns old and new artifact", () => {
                expect(comparison.modified).toStrictEqual([
                    { base: base_artifact, compared_to: compared_to_artifact },
                ]);
            });
            it("#removed returns empty array", () => {
                expect(comparison.removed).toStrictEqual([]);
            });
            it("#added returns empty array", () => {
                expect(comparison.added).toStrictEqual([]);
            });
        });
    });
});
