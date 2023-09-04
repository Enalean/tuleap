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

import { CurrentArtifactIdentifierProxy } from "./CurrentArtifactIdentifierProxy";

const ARTIFACT_ID = 17;

describe(`CurrentArtifactIdentifierProxy`, () => {
    it(`builds an identifier from the Modal's artifact id when it is in edition mode`, () => {
        const identifier =
            CurrentArtifactIdentifierProxy.fromModalArtifactId(ARTIFACT_ID).unwrapOr(null);
        if (identifier === null) {
            throw Error("Identifier should not be null");
        }
        expect(identifier.id).toBe(ARTIFACT_ID);
    });

    it(`returns nothing when Modal is in creation mode and its artifact id is undefined`, () => {
        expect(CurrentArtifactIdentifierProxy.fromModalArtifactId(undefined).isNothing()).toBe(
            true,
        );
    });
});
