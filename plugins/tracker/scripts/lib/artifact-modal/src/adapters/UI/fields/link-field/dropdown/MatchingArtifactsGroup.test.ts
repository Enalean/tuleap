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

import { MatchingArtifactsGroup } from "./MatchingArtifactsGroup";
import { LinkableArtifactStub } from "../../../../../../tests/stubs/LinkableArtifactStub";
import { setCatalog } from "../../../../../gettext-catalog";
import { VerifyIsAlreadyLinkedStub } from "../../../../../../tests/stubs/VerifyIsAlreadyLinkedStub";

describe(`MatchingArtifactsGroup`, () => {
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
    });

    it(`builds from a Linkable Artifact`, () => {
        const artifact = LinkableArtifactStub.withDefaults({ id: 123 });
        const group = MatchingArtifactsGroup.fromMatchingArtifact(
            VerifyIsAlreadyLinkedStub.withNoArtifactAlreadyLinked(),
            artifact,
        );

        expect(group.is_loading).toBe(false);
        expect(group.items).toHaveLength(1);
        expect(group.empty_message).not.toBe("");
        expect(group.items[0].value).toBe(artifact);
    });

    it(`builds an empty group so that Link-selector will show an empty state message`, () => {
        const group = MatchingArtifactsGroup.buildEmpty();
        expect(group.items).toHaveLength(0);
        expect(group.is_loading).toBe(false);
        expect(group.empty_message).not.toBe("");
    });

    it(`builds an empty loading group so that Link-selector will show a spinner`, () => {
        const group = MatchingArtifactsGroup.buildLoadingState();
        expect(group.items).toHaveLength(0);
        expect(group.is_loading).toBe(true);
        expect(group.empty_message).toBe("");
    });
});
