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

import { Option } from "@tuleap/option";
import { ArtifactCrossReference } from "./ArtifactCrossReference";
import { CurrentArtifactIdentifierStub } from "../../tests/stubs/CurrentArtifactIdentifierStub";
import { TrackerShortnameStub } from "../../tests/stubs/TrackerShortnameStub";

describe("ArtifactCrossReference", () => {
    it("builds from an artifact id, a tracker shortname and a tracker color name", () => {
        const option = ArtifactCrossReference.fromCurrentArtifact(
            Option.fromValue(CurrentArtifactIdentifierStub.withId(150)),
            TrackerShortnameStub.withShortname("story"),
            "acid-green",
        );

        const reference = option.unwrapOr(null);
        if (reference === null) {
            throw Error("Reference should not be null");
        }

        expect(reference.ref).toBe("story #150");
        expect(reference.color).toBe("acid-green");
    });
});
