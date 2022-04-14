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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { LinkableArtifactIdentifierProxy } from "./LinkableArtifactIdentifierProxy";
import { CurrentArtifactIdentifierStub } from "../../../../../tests/stubs/CurrentArtifactIdentifierStub";

describe("LinkableArtifactIdentifierProxy", () => {
    it.each(["abcd", "10+", "105d", "10^5"])(
        "should return null when there is no valid id in the query string",
        (query: string) => {
            expect(LinkableArtifactIdentifierProxy.fromQueryString(query, null)).toBeNull();
        }
    );

    it("should return null when the user has entered the current artifact_id", () => {
        expect(
            LinkableArtifactIdentifierProxy.fromQueryString(
                "105",
                CurrentArtifactIdentifierStub.withId(105)
            )
        ).toBeNull();
    });

    it("should return a LinkableArtifactIdentifier", () => {
        expect(LinkableArtifactIdentifierProxy.fromQueryString("105", null)).toStrictEqual({
            _type: "LinkableArtifactIdentifier",
            id: 105,
        });
    });
});
