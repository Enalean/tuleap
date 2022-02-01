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

import type { LinkFieldPresenter } from "./LinkFieldPresenter";
import { LinkFieldController } from "./LinkFieldController";
import { RetrieveAllLinkedArtifactsStub } from "../../../../../tests/stubs/RetrieveAllLinkedArtifactsStub";
import type { RetrieveAllLinkedArtifacts } from "../../../../domain/fields/link-field-v2/RetrieveAllLinkedArtifacts";
import { CurrentArtifactIdentifierStub } from "../../../../../tests/stubs/CurrentArtifactIdentifierStub";
import { Fault } from "@tuleap/fault";
import { NoLinksInCreationModeFault } from "../../../../domain/fields/link-field-v2/NoLinksInCreationModeFault";
import { RetrieveLinkedArtifactsSyncStub } from "../../../../../tests/stubs/RetrieveLinkedArtifactsSyncStub";
import { AddLinkMarkedForRemovalStub } from "../../../../../tests/stubs/AddLinkMarkedForRemovalStub";
import { DeleteLinkMarkedForRemovalStub } from "../../../../../tests/stubs/DeleteLinkMarkedForRemovalStub";
import { VerifyLinkIsMarkedForRemovalStub } from "../../../../../tests/stubs/VerifyLinkIsMarkedForRemovalStub";
import { LinkedArtifactStub } from "../../../../../tests/stubs/LinkedArtifactStub";
import { LinkedArtifactIdentifierStub } from "../../../../../tests/stubs/LinkedArtifactIdentifierStub";

const ARTIFACT_ID = 60;

describe(`LinkFieldController`, () => {
    describe(`displayLinkedArtifacts()`, () => {
        let links_retriever: RetrieveAllLinkedArtifacts;

        beforeEach(() => {
            const linked_artifact = LinkedArtifactStub.withDefaults();
            links_retriever = RetrieveAllLinkedArtifactsStub.withLinkedArtifacts(linked_artifact);
        });

        const displayLinkedArtifacts = (): Promise<LinkFieldPresenter> => {
            const controller = LinkFieldController(
                links_retriever,
                RetrieveLinkedArtifactsSyncStub.withoutLink(),
                AddLinkMarkedForRemovalStub.withCount(),
                DeleteLinkMarkedForRemovalStub.withCount(),
                VerifyLinkIsMarkedForRemovalStub.withLinked(),
                CurrentArtifactIdentifierStub.withId(18)
            );
            return controller.displayLinkedArtifacts();
        };

        it(`when the modal is in creation mode, it will return a dedicated empty presenter`, async () => {
            links_retriever = RetrieveAllLinkedArtifactsStub.withFault(
                NoLinksInCreationModeFault()
            );
            const presenter = await displayLinkedArtifacts();

            expect(presenter.has_loaded_content).toBe(true);
            expect(presenter.error_message).toBe("");
        });

        it(`when the modal is in edition mode and it succeeds loading,
            it will return a presenter with the linked artifacts`, async () => {
            const presenter = await displayLinkedArtifacts();

            expect(presenter.has_loaded_content).toBe(true);
            expect(presenter.error_message).toBe("");
        });

        it(`when the modal is in edition mode and it fails loading,
            it will return a presenter with an error message`, async () => {
            const error_message = "Ooops";
            links_retriever = RetrieveAllLinkedArtifactsStub.withFault(
                Fault.fromMessage(error_message)
            );
            const presenter = await displayLinkedArtifacts();

            expect(presenter.has_loaded_content).toBe(true);
            expect(presenter.error_message).toBe(error_message);
        });
    });

    describe(`markForRemoval`, () => {
        let deleted_link_adder: AddLinkMarkedForRemovalStub;

        beforeEach(() => {
            deleted_link_adder = AddLinkMarkedForRemovalStub.withCount();
        });

        const markForRemoval = (): LinkFieldPresenter => {
            const identifier = LinkedArtifactIdentifierStub.withId(ARTIFACT_ID);
            const linked_artifact = LinkedArtifactStub.withDefaults({ identifier });
            const controller = LinkFieldController(
                RetrieveAllLinkedArtifactsStub.withoutLink(),
                RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(linked_artifact),
                deleted_link_adder,
                DeleteLinkMarkedForRemovalStub.withCount(),
                VerifyLinkIsMarkedForRemovalStub.withLinked(),
                CurrentArtifactIdentifierStub.withId(40)
            );
            return controller.markForRemoval(identifier);
        };

        it(`stores the given identifier as a link marked for removal and returns an updated presenter`, () => {
            const presenter = markForRemoval();

            expect(deleted_link_adder.getCallCount()).toBe(1);
            const is_marked = presenter.linked_artifacts.some(
                (linked_artifact) => linked_artifact.is_marked_for_removal
            );
            expect(is_marked).toBe(true);
        });
    });

    describe(`unmarkForRemoval`, () => {
        let deleted_link_remover: DeleteLinkMarkedForRemovalStub;

        beforeEach(() => {
            deleted_link_remover = DeleteLinkMarkedForRemovalStub.withCount();
        });

        const unmarkForRemoval = (): LinkFieldPresenter => {
            const identifier = LinkedArtifactIdentifierStub.withId(ARTIFACT_ID);
            const linked_artifact = LinkedArtifactStub.withDefaults({ identifier });
            const controller = LinkFieldController(
                RetrieveAllLinkedArtifactsStub.withoutLink(),
                RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(linked_artifact),
                AddLinkMarkedForRemovalStub.withCount(),
                deleted_link_remover,
                VerifyLinkIsMarkedForRemovalStub.withNoLink(),
                CurrentArtifactIdentifierStub.withId(80)
            );
            return controller.unmarkForRemoval(identifier);
        };

        it(`deletes the given identifier in the stored links marked for removal,
            and returns an updated presenter`, () => {
            const presenter = unmarkForRemoval();

            expect(deleted_link_remover.getCallCount()).toBe(1);
            const is_marked = presenter.linked_artifacts.some(
                (linked_artifact) => linked_artifact.is_marked_for_removal
            );
            expect(is_marked).toBe(false);
        });
    });
});
