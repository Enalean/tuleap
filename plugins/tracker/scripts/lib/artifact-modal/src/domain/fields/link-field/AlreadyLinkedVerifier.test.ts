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

import type { RetrieveLinkedArtifactsSync } from "./RetrieveLinkedArtifactsSync";
import type { RetrieveNewLinks } from "./RetrieveNewLinks";
import { RetrieveLinkedArtifactsSyncStub } from "../../../../tests/stubs/RetrieveLinkedArtifactsSyncStub";
import { RetrieveNewLinksStub } from "../../../../tests/stubs/RetrieveNewLinksStub";
import { AlreadyLinkedVerifier } from "./AlreadyLinkedVerifier";
import { LinkableArtifactStub } from "../../../../tests/stubs/LinkableArtifactStub";
import { LinkedArtifactStub } from "../../../../tests/stubs/LinkedArtifactStub";
import { LinkTypeStub } from "../../../../tests/stubs/LinkTypeStub";
import { NewLinkStub } from "../../../../tests/stubs/NewLinkStub";

const ARTIFACT_ID = 877;

describe(`AlreadyLinkedVerifier`, () => {
    let links_retriever: RetrieveLinkedArtifactsSync, new_links_retriever: RetrieveNewLinks;

    beforeEach(() => {
        links_retriever = RetrieveLinkedArtifactsSyncStub.withoutLink();
        new_links_retriever = RetrieveNewLinksStub.withoutLink();
    });

    const isAlreadyLinked = (): boolean => {
        const verifier = AlreadyLinkedVerifier(links_retriever, new_links_retriever);
        return verifier.isAlreadyLinked(LinkableArtifactStub.withDefaults({ id: ARTIFACT_ID }));
    };

    it(`returns false when there is no link at all`, () => {
        expect(isAlreadyLinked()).toBe(false);
    });

    it(`returns false when the given artifact was never linked`, () => {
        links_retriever = RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(
            LinkedArtifactStub.withIdAndType(602, LinkTypeStub.buildUntyped()),
        );
        new_links_retriever = RetrieveNewLinksStub.withNewLinks(
            NewLinkStub.withIdAndType(211, LinkTypeStub.buildUntyped()),
        );
        expect(isAlreadyLinked()).toBe(false);
    });

    it(`returns true when there is a new link for the same artifact id`, () => {
        new_links_retriever = RetrieveNewLinksStub.withNewLinks(
            NewLinkStub.withIdAndType(ARTIFACT_ID, LinkTypeStub.buildUntyped()),
        );
        expect(isAlreadyLinked()).toBe(true);
    });

    it(`returns true when there is an existing link for the same artifact id`, () => {
        links_retriever = RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(
            LinkedArtifactStub.withIdAndType(ARTIFACT_ID, LinkTypeStub.buildUntyped()),
        );
        expect(isAlreadyLinked()).toBe(true);
    });
});
