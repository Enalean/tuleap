/*
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

import { LinkableArtifactFilter } from "./LinkableArtifactFilter";
import { LinkableArtifactStub } from "../../../../tests/stubs/LinkableArtifactStub";

const ARTIFACT_ID = 374;
const TITLE = "Ampelidae";

describe(`LinkableArtifactFilter`, () => {
    const matches = (query: string): boolean => {
        const filter = LinkableArtifactFilter(query);
        return filter.matchesQuery(
            LinkableArtifactStub.withDefaults({ id: ARTIFACT_ID, title: TITLE }),
        );
    };

    it.each([
        ["an empty string", true, ""],
        ["a string part of the Title of the artifact", true, "elid"],
        ["a string part of the Title of the artifact with different case", true, "AMP"],
        ["a number part of the ID of the artifact", true, "74"],
        ["a string not matching anything", false, "zzz"],
        ["a number not matching anything", false, "999"],
    ])(`given the query was %s, it will return %s`, (_type_of_query, expected_return, query) => {
        expect(matches(query)).toBe(expected_return);
    });
});
