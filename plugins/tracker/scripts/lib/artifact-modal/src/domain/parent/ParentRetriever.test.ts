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

import type { Artifact } from "../Artifact";
import { ParentRetriever } from "./ParentRetriever";
import { StubWithArtifact } from "../../../tests/stubs/RetrieveArtifactStub";
import {
    StubWithCreationMode,
    StubWithEditionMode,
} from "../../../tests/stubs/VerifyIsInCreationModeStub";
import type { VerifyIsInCreationMode } from "../VerifyIsInCreationMode";

const PARENT_ARTIFACT_ID = 5;

describe(`parent-retriever`, () => {
    let parent_artifact: Artifact;
    let mode_verifier: VerifyIsInCreationMode;

    beforeEach(() => {
        parent_artifact = { id: PARENT_ARTIFACT_ID, title: "mobship" };
        mode_verifier = StubWithCreationMode();
    });

    const retrieveFutureParent = (): Promise<Artifact | null> => {
        const retriever = ParentRetriever(StubWithArtifact(parent_artifact), mode_verifier);

        return retriever.retrieveFutureParent(PARENT_ARTIFACT_ID);
    };

    describe(`retrieveFutureParent()`, () => {
        it(`when the modal is in creation mode, it will retrieve the future parent artifact`, () => {
            return expect(retrieveFutureParent()).resolves.toBe(parent_artifact);
        });

        it(`when the modal is in edition mode, it will return null`, () => {
            mode_verifier = StubWithEditionMode();
            return expect(retrieveFutureParent()).resolves.toBeNull();
        });
    });
});
