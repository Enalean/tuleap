/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { describe, expect, it } from "vitest";
import type { LinkableArtifact } from "../../domain/links/LinkableArtifact";
import { ArtifactLinkListDuplicateRemover } from "./ArtifactLinkListDuplicateRemover";

describe("ArtifactLinkListDuplicateRemover", () => {
    describe("removeDuplicateArtifact", () => {
        it("Remove all duplicate artifact in a linkable artifact list", () => {
            const artifacts = [
                { id: 18, title: "hehe" } as LinkableArtifact,
                { id: 45, title: "artifact title" } as LinkableArtifact,
                { id: 18, title: "hehe" } as LinkableArtifact,
                { id: 352, title: "hehe" } as LinkableArtifact,
            ];

            const result = ArtifactLinkListDuplicateRemover.removeDuplicateArtifact(artifacts);

            const expected_result = [
                { id: 18, title: "hehe" } as LinkableArtifact,
                { id: 45, title: "artifact title" } as LinkableArtifact,
                { id: 352, title: "hehe" } as LinkableArtifact,
            ];

            expect(result).toStrictEqual(expected_result);
        });
    });
});
