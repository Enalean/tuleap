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

import { PossibleParentsCache } from "./PossibleParentsCache";
import type { LinkableArtifact } from "../../../../domain/fields/link-field/LinkableArtifact";
import { CurrentTrackerIdentifierStub } from "../../../../../tests/stubs/CurrentTrackerIdentifierStub";
import { RetrievePossibleParentsStub } from "../../../../../tests/stubs/RetrievePossibleParentsStub";
import { LinkableArtifactStub } from "../../../../../tests/stubs/LinkableArtifactStub";
import type { RetrievePossibleParents } from "../../../../domain/fields/link-field/RetrievePossibleParents";
import type { CurrentTrackerIdentifier } from "../../../../domain/CurrentTrackerIdentifier";

describe(`PossibleParentsCache`, () => {
    let first_parent: LinkableArtifact,
        second_parent: LinkableArtifact,
        tracker_identifier: CurrentTrackerIdentifier;

    beforeEach(() => {
        first_parent = LinkableArtifactStub.withDefaults({ id: 867 });
        second_parent = LinkableArtifactStub.withDefaults({ id: 628 });
        tracker_identifier = CurrentTrackerIdentifierStub.withId(125);
    });

    const getCache = (): RetrievePossibleParents => {
        const actual_retriever = RetrievePossibleParentsStub.withSuccessiveParents(
            [first_parent, second_parent],
            [],
        );
        return PossibleParentsCache(actual_retriever);
    };

    it(`delegates to another retriever the first call,
        and on later calls it returns the cached result`, async () => {
        const cache = getCache();
        const first_call_result = await cache.getPossibleParents(tracker_identifier);
        if (!first_call_result.isOk()) {
            throw new Error("Expected an Ok");
        }
        expect(first_call_result.value).toHaveLength(2);
        expect(first_call_result.value).toContain(first_parent);
        expect(first_call_result.value).toContain(second_parent);

        const second_call_result = await cache.getPossibleParents(tracker_identifier);
        if (!second_call_result.isOk()) {
            throw new Error("Expected an Ok");
        }
        expect(second_call_result.value).toStrictEqual(first_call_result.value);
    });
});
