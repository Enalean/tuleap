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

import { ParentArtifactIdentifierProxy } from "./ParentArtifactIdentifierProxy";

const ARTIFACT_ID = 19;

describe(`ParentArtifactIdentifierProxy`, () => {
    it(`builds an identifier from the parent artifact id given by the caller of the Modal`, () => {
        const identifier = ParentArtifactIdentifierProxy.fromCallerArgument(ARTIFACT_ID);
        if (identifier === null) {
            throw new Error("Identifier should not be null");
        }
        expect(identifier.id).toBe(ARTIFACT_ID);
    });

    it(`returns null when the Modal was called with a null parent artifact id`, () => {
        expect(ParentArtifactIdentifierProxy.fromCallerArgument(null)).toBeNull();
    });

    it(`returns null when the Modal is in edition mode and its parent artifact id is undefined`, () => {
        expect(ParentArtifactIdentifierProxy.fromCallerArgument(undefined)).toBeNull();
    });
});
