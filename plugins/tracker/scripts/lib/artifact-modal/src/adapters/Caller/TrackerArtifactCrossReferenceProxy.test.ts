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

import { TrackerArtifactCrossReferenceProxy } from "./TrackerArtifactCrossReferenceProxy";
import { CurrentArtifactIdentifierStub } from "../../../tests/stubs/CurrentArtifactIdentifierStub";
import { TrackerShortnameStub } from "../../../tests/stubs/TrackerShortnameStub";

describe("TrackerArtifactCrossReferenceProxy", () => {
    it("Given a valid artifact id and a tracker shortname, Then it will return a TrackerArtifactReference", () => {
        const reference = TrackerArtifactCrossReferenceProxy.fromArtifactIdentifierAndTracker(
            CurrentArtifactIdentifierStub.withId(150),
            TrackerShortnameStub.withShortname("story")
        );

        if (reference === null) {
            throw new Error("Reference should not be null");
        }

        expect(reference.ref).toBe("story #150");
    });

    it("Given no artifact id, Then it will return an empty TrackerArtifactReference", () => {
        const reference = TrackerArtifactCrossReferenceProxy.fromArtifactIdentifierAndTracker(
            null,
            TrackerShortnameStub.withShortname("story")
        );

        expect(reference).toBeNull();
    });
});
