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

import type { LinkedArtifactCollectionPresenter } from "./LinkedArtifactCollectionPresenter";
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
import { NotifyFaultStub } from "../../../../../tests/stubs/NotifyFaultStub";
import { ArtifactCrossReferenceStub } from "../../../../../tests/stubs/ArtifactCrossReferenceStub";
import type { ArtifactLinkFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import type { CurrentArtifactIdentifier } from "../../../../domain/CurrentArtifactIdentifier";
import type { ArtifactCrossReference } from "../../../../domain/ArtifactCrossReference";
import type { LinkFieldPresenter } from "./LinkFieldPresenter";

const ARTIFACT_ID = 60;
const FIELD_ID = 714;

describe(`LinkFieldController`, () => {
    let current_artifact_identifier: CurrentArtifactIdentifier,
        field: ArtifactLinkFieldStructure,
        cross_reference: ArtifactCrossReference;
    beforeEach(() => {
        field = { field_id: FIELD_ID, type: "art_link", label: "Artifact link", allowed_types: [] };
        current_artifact_identifier = CurrentArtifactIdentifierStub.withId(18);
        cross_reference = ArtifactCrossReferenceStub.withRef("story #18");
    });

    describe(`displayField()`, () => {
        const displayField = (): LinkFieldPresenter => {
            const controller = LinkFieldController(
                RetrieveAllLinkedArtifactsStub.withoutLink(),
                RetrieveLinkedArtifactsSyncStub.withoutLink(),
                AddLinkMarkedForRemovalStub.withCount(),
                DeleteLinkMarkedForRemovalStub.withCount(),
                VerifyLinkIsMarkedForRemovalStub.withNoLinkMarkedForRemoval(),
                NotifyFaultStub.withCount(),
                field,
                current_artifact_identifier,
                cross_reference
            );
            return controller.displayField();
        };

        it(`returns a presenter for the field and current artifact cross reference`, () => {
            const presenter = displayField();
            expect(presenter.field_id).toBe(FIELD_ID);
        });
    });

    describe(`displayLinkedArtifacts()`, () => {
        let links_retriever: RetrieveAllLinkedArtifacts, fault_notifier: NotifyFaultStub;

        beforeEach(() => {
            const linked_artifact = LinkedArtifactStub.withDefaults();
            links_retriever = RetrieveAllLinkedArtifactsStub.withLinkedArtifacts(linked_artifact);
            fault_notifier = NotifyFaultStub.withCount();
        });

        const displayLinkedArtifacts = (): Promise<LinkedArtifactCollectionPresenter> => {
            const controller = LinkFieldController(
                links_retriever,
                RetrieveLinkedArtifactsSyncStub.withoutLink(),
                AddLinkMarkedForRemovalStub.withCount(),
                DeleteLinkMarkedForRemovalStub.withCount(),
                VerifyLinkIsMarkedForRemovalStub.withAllLinksMarkedForRemoval(),
                fault_notifier,
                field,
                current_artifact_identifier,
                cross_reference
            );
            return controller.displayLinkedArtifacts();
        };

        it(`when the modal is in creation mode,
            it won't notify that there has been a fault
            and it will return an empty presenter`, async () => {
            links_retriever = RetrieveAllLinkedArtifactsStub.withFault(
                NoLinksInCreationModeFault()
            );
            const presenter = await displayLinkedArtifacts();

            expect(presenter.has_loaded_content).toBe(true);
            expect(fault_notifier.getCallCount()).toBe(0);
        });

        it(`when the modal is in edition mode and it succeeds loading,
            it will return a presenter with the linked artifacts`, async () => {
            const presenter = await displayLinkedArtifacts();

            expect(presenter.has_loaded_content).toBe(true);
        });

        it(`when the modal is in edition mode and it fails loading,
            it will notify that there has been a fault
            and it will return an empty presenter`, async () => {
            links_retriever = RetrieveAllLinkedArtifactsStub.withFault(Fault.fromMessage("Ooops"));
            const presenter = await displayLinkedArtifacts();

            expect(presenter.has_loaded_content).toBe(true);
            expect(fault_notifier.getCallCount()).toBe(1);
        });
    });

    describe(`markForRemoval`, () => {
        let deleted_link_adder: AddLinkMarkedForRemovalStub;

        beforeEach(() => {
            deleted_link_adder = AddLinkMarkedForRemovalStub.withCount();
        });

        const markForRemoval = (): LinkedArtifactCollectionPresenter => {
            const identifier = LinkedArtifactIdentifierStub.withId(ARTIFACT_ID);
            const linked_artifact = LinkedArtifactStub.withDefaults({ identifier });
            const controller = LinkFieldController(
                RetrieveAllLinkedArtifactsStub.withoutLink(),
                RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(linked_artifact),
                deleted_link_adder,
                DeleteLinkMarkedForRemovalStub.withCount(),
                VerifyLinkIsMarkedForRemovalStub.withAllLinksMarkedForRemoval(),
                NotifyFaultStub.withCount(),
                field,
                current_artifact_identifier,
                cross_reference
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

        const unmarkForRemoval = (): LinkedArtifactCollectionPresenter => {
            const identifier = LinkedArtifactIdentifierStub.withId(ARTIFACT_ID);
            const linked_artifact = LinkedArtifactStub.withDefaults({ identifier });
            const controller = LinkFieldController(
                RetrieveAllLinkedArtifactsStub.withoutLink(),
                RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(linked_artifact),
                AddLinkMarkedForRemovalStub.withCount(),
                deleted_link_remover,
                VerifyLinkIsMarkedForRemovalStub.withNoLinkMarkedForRemoval(),
                NotifyFaultStub.withCount(),
                field,
                current_artifact_identifier,
                cross_reference
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
