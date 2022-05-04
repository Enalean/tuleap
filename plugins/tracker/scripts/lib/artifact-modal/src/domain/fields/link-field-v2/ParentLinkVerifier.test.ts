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

import { ParentLinkVerifier } from "./ParentLinkVerifier";
import type { RetrieveLinkedArtifactsSync } from "./RetrieveLinkedArtifactsSync";
import type { VerifyLinkIsMarkedForRemoval } from "./VerifyLinkIsMarkedForRemoval";
import type { RetrieveNewLinks } from "./RetrieveNewLinks";
import { RetrieveLinkedArtifactsSyncStub } from "../../../../tests/stubs/RetrieveLinkedArtifactsSyncStub";
import { VerifyLinkIsMarkedForRemovalStub } from "../../../../tests/stubs/VerifyLinkIsMarkedForRemovalStub";
import { RetrieveNewLinksStub } from "../../../../tests/stubs/RetrieveNewLinksStub";
import { NewLinkStub } from "../../../../tests/stubs/NewLinkStub";
import { LinkTypeStub } from "../../../../tests/stubs/LinkTypeStub";
import { LinkedArtifactStub } from "../../../../tests/stubs/LinkedArtifactStub";

describe(`ParentLinkVerifier`, () => {
    let links_retriever: RetrieveLinkedArtifactsSync,
        removal_verifier: VerifyLinkIsMarkedForRemoval,
        new_links_retriever: RetrieveNewLinks;

    beforeEach(() => {
        links_retriever = RetrieveLinkedArtifactsSyncStub.withoutLink();
        new_links_retriever = RetrieveNewLinksStub.withoutLink();
        removal_verifier = VerifyLinkIsMarkedForRemovalStub.withNoLinkMarkedForRemoval();
    });

    const hasParentLink = (): boolean => {
        const verifier = ParentLinkVerifier(links_retriever, removal_verifier, new_links_retriever);
        return verifier.hasParentLink();
    };

    it(`returns false when there is no link at all`, () => {
        expect(hasParentLink()).toBe(false);
    });

    it(`returns false when there is no existing reverse _is_child link`, () => {
        links_retriever = RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(
            LinkedArtifactStub.withIdAndType(357, LinkTypeStub.buildUntyped())
        );
        expect(hasParentLink()).toBe(false);
    });

    it(`returns false when there is no new reverse _is_child link`, () => {
        new_links_retriever = RetrieveNewLinksStub.withNewLinks(
            NewLinkStub.withIdAndType(162, LinkTypeStub.buildUntyped())
        );

        expect(hasParentLink()).toBe(false);
    });

    it(`returns true when a new reverse _is_child link exists`, () => {
        new_links_retriever = RetrieveNewLinksStub.withNewLinks(
            NewLinkStub.withIdAndType(162, LinkTypeStub.buildParentLinkType())
        );
        expect(hasParentLink()).toBe(true);
    });

    it(`returns true when an existing reverse _is_child link exists and is not marked for removal`, () => {
        links_retriever = RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(
            LinkedArtifactStub.withIdAndType(357, LinkTypeStub.buildParentLinkType())
        );
        expect(hasParentLink()).toBe(true);
    });

    it(`returns false when an existing reverse _is_child link exists but is marked for removal`, () => {
        links_retriever = RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(
            LinkedArtifactStub.withIdAndType(357, LinkTypeStub.buildParentLinkType())
        );
        removal_verifier = VerifyLinkIsMarkedForRemovalStub.withAllLinksMarkedForRemoval();
        expect(hasParentLink()).toBe(false);
    });
});
