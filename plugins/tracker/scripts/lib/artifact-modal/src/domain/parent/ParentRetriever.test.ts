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

import type { Fault } from "@tuleap/fault";
import { isFault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import type { ParentArtifactIdentifier } from "./ParentArtifactIdentifier";
import { ParentArtifactIdentifierStub } from "../../../tests/stubs/ParentArtifactIdentifierStub";
import type { Artifact } from "../Artifact";
import { ParentRetriever } from "./ParentRetriever";
import { RetrieveArtifactStub } from "../../../tests/stubs/RetrieveArtifactStub";

const PARENT_ID = 66;

describe(`ParentRetriever`, () => {
    let parent_identifier: ParentArtifactIdentifier | null;
    beforeEach(() => {
        parent_identifier = ParentArtifactIdentifierStub.withId(PARENT_ID);
    });

    const getParent = (): ResultAsync<Artifact, Fault> => {
        const parent_artifact: Artifact = { id: PARENT_ID, title: "tribrachic" };
        const retriever = ParentRetriever(RetrieveArtifactStub.withArtifact(parent_artifact));
        return retriever.getParent(parent_identifier);
    };

    it(`when there is a parent, it will return it`, async () => {
        const result = await getParent();
        if (!result.isOk()) {
            throw new Error("Expected an Ok");
        }
        expect(result.value.id).toBe(PARENT_ID);
    });

    it(`when there is no parent artifact, it will return a Fault`, async () => {
        parent_identifier = null;
        const result = await getParent();
        if (!result.isErr()) {
            throw new Error("Expected an Err");
        }
        expect(isFault(result.error)).toBe(true);
    });
});
