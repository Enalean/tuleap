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
import type { LinkFieldControllerType } from "./LinkFieldController";
import { LinkFieldController } from "./LinkFieldController";
import { RetrieveAllLinkedArtifactsStub } from "../../../../../tests/stubs/RetrieveAllLinkedArtifactsStub";
import type { RetrieveAllLinkedArtifacts } from "../../../../domain/fields/link-field/RetrieveAllLinkedArtifacts";
import { CurrentArtifactIdentifierStub } from "../../../../../tests/stubs/CurrentArtifactIdentifierStub";
import { Fault } from "@tuleap/fault";
import { NoLinksInCreationModeFault } from "../../../../domain/fields/link-field/NoLinksInCreationModeFault";
import { RetrieveLinkedArtifactsSyncStub } from "../../../../../tests/stubs/RetrieveLinkedArtifactsSyncStub";
import { AddLinkMarkedForRemovalStub } from "../../../../../tests/stubs/AddLinkMarkedForRemovalStub";
import { DeleteLinkMarkedForRemovalStub } from "../../../../../tests/stubs/DeleteLinkMarkedForRemovalStub";
import { VerifyLinkIsMarkedForRemovalStub } from "../../../../../tests/stubs/VerifyLinkIsMarkedForRemovalStub";
import { LinkedArtifactStub } from "../../../../../tests/stubs/LinkedArtifactStub";
import { LinkedArtifactIdentifierStub } from "../../../../../tests/stubs/LinkedArtifactIdentifierStub";
import { NotifyFaultStub } from "../../../../../tests/stubs/NotifyFaultStub";
import { ArtifactCrossReferenceStub } from "../../../../../tests/stubs/ArtifactCrossReferenceStub";
import { ArtifactLinkSelectorAutoCompleter } from "./ArtifactLinkSelectorAutoCompleter";
import { RetrieveMatchingArtifactStub } from "../../../../../tests/stubs/RetrieveMatchingArtifactStub";
import { LinkableArtifactStub } from "../../../../../tests/stubs/LinkableArtifactStub";
import type { LinkableArtifact } from "../../../../domain/fields/link-field/LinkableArtifact";
import { AddNewLinkStub } from "../../../../../tests/stubs/AddNewLinkStub";
import { RetrieveNewLinksStub } from "../../../../../tests/stubs/RetrieveNewLinksStub";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";
import { IS_CHILD_LINK_TYPE, UNTYPED_LINK } from "@tuleap/plugin-tracker-constants";
import { ClearFaultNotificationStub } from "../../../../../tests/stubs/ClearFaultNotificationStub";
import type { RetrieveLinkedArtifactsSync } from "../../../../domain/fields/link-field/RetrieveLinkedArtifactsSync";
import type { VerifyLinkIsMarkedForRemoval } from "../../../../domain/fields/link-field/VerifyLinkIsMarkedForRemoval";
import type { RetrieveNewLinks } from "../../../../domain/fields/link-field/RetrieveNewLinks";
import { DeleteNewLinkStub } from "../../../../../tests/stubs/DeleteNewLinkStub";
import { NewLinkStub } from "../../../../../tests/stubs/NewLinkStub";
import { ParentLinkVerifier } from "../../../../domain/fields/link-field/ParentLinkVerifier";
import type { LinkType } from "../../../../domain/fields/link-field/LinkType";
import {
    FORWARD_DIRECTION,
    REVERSE_DIRECTION,
} from "../../../../domain/fields/link-field/LinkType";
import { RetrievePossibleParentsStub } from "../../../../../tests/stubs/RetrievePossibleParentsStub";
import { CurrentTrackerIdentifierStub } from "../../../../../tests/stubs/CurrentTrackerIdentifierStub";
import type { RetrievePossibleParents } from "../../../../domain/fields/link-field/RetrievePossibleParents";
import { setCatalog } from "../../../../gettext-catalog";
import type { GroupOfItems } from "@tuleap/link-selector";
import { VerifyIsAlreadyLinkedStub } from "../../../../../tests/stubs/VerifyIsAlreadyLinkedStub";
import type { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";
import type { NewLinkCollectionPresenter } from "./NewLinkCollectionPresenter";
import { ControlLinkedArtifactsPopoversStub } from "../../../../../tests/stubs/ControlLinkedArtifactsPopoversStub";
import type { AllowedLinkTypeRepresentation } from "@tuleap/plugin-tracker-rest-api-types";
import type { ParentArtifactIdentifier } from "../../../../domain/parent/ParentArtifactIdentifier";
import { AllowedLinksTypesCollection } from "./AllowedLinksTypesCollection";
import { VerifyIsTrackerInAHierarchyStub } from "../../../../../tests/stubs/VerifyIsTrackerInAHierarchyStub";
import type { VerifyIsTrackerInAHierarchy } from "../../../../domain/fields/link-field/VerifyIsTrackerInAHierarchy";
import { ParentArtifactIdentifierStub } from "../../../../../tests/stubs/ParentArtifactIdentifierStub";
import { UserIdentifierStub } from "../../../../../tests/stubs/UserIdentifierStub";
import { RetrieveUserHistoryStub } from "../../../../../tests/stubs/RetrieveUserHistoryStub";
import { okAsync } from "neverthrow";
import { SearchArtifactsStub } from "../../../../../tests/stubs/SearchArtifactsStub";

const ARTIFACT_ID = 60;
const FIELD_ID = 714;
const FIRST_PARENT_ID = 527;
const SECOND_PARENT_ID = 548;

describe(`LinkFieldController`, () => {
    let links_retriever: RetrieveAllLinkedArtifacts,
        links_retriever_sync: RetrieveLinkedArtifactsSync,
        deleted_link_adder: AddLinkMarkedForRemovalStub,
        deleted_link_remover: DeleteLinkMarkedForRemovalStub,
        deleted_link_verifier: VerifyLinkIsMarkedForRemoval,
        fault_notifier: NotifyFaultStub,
        new_link_adder: AddNewLinkStub,
        new_links_retriever: RetrieveNewLinks,
        new_link_remover: DeleteNewLinkStub,
        notification_clearer: ClearFaultNotificationStub,
        parents_retriever: RetrievePossibleParents,
        allowed_link_types: AllowedLinkTypeRepresentation[],
        parent_identifier: ParentArtifactIdentifier | null,
        verify_is_tracker_in_a_hierarchy: VerifyIsTrackerInAHierarchy;

    beforeEach(() => {
        setCatalog({
            getString: (msgid) => msgid,
        });
        links_retriever = RetrieveAllLinkedArtifactsStub.withoutLink();
        links_retriever_sync = RetrieveLinkedArtifactsSyncStub.withoutLink();
        deleted_link_adder = AddLinkMarkedForRemovalStub.withCount();
        deleted_link_remover = DeleteLinkMarkedForRemovalStub.withCount();
        deleted_link_verifier = VerifyLinkIsMarkedForRemovalStub.withNoLinkMarkedForRemoval();
        fault_notifier = NotifyFaultStub.withCount();
        new_link_adder = AddNewLinkStub.withCount();
        new_links_retriever = RetrieveNewLinksStub.withoutLink();
        new_link_remover = DeleteNewLinkStub.withCount();
        notification_clearer = ClearFaultNotificationStub.withCount();
        parents_retriever = RetrievePossibleParentsStub.withoutParents();
        parent_identifier = null;
        verify_is_tracker_in_a_hierarchy = VerifyIsTrackerInAHierarchyStub.withNoHierarchy();

        allowed_link_types = [
            { shortname: IS_CHILD_LINK_TYPE, forward_label: "Child", reverse_label: "Parent" },
            {
                shortname: "custom",
                forward_label: "Custom Forward",
                reverse_label: "Custom Reverse",
            },
        ];
    });

    const getController = (): LinkFieldControllerType => {
        const link_verifier = VerifyIsAlreadyLinkedStub.withNoArtifactAlreadyLinked();
        const current_artifact_identifier = CurrentArtifactIdentifierStub.withId(18);
        const cross_reference = ArtifactCrossReferenceStub.withRef("story #18");
        const current_tracker_identifier = CurrentTrackerIdentifierStub.withId(70);
        return LinkFieldController(
            links_retriever,
            links_retriever_sync,
            deleted_link_adder,
            deleted_link_remover,
            deleted_link_verifier,
            fault_notifier,
            notification_clearer,
            ArtifactLinkSelectorAutoCompleter(
                RetrieveMatchingArtifactStub.withMatchingArtifact(
                    okAsync(LinkableArtifactStub.withDefaults())
                ),
                fault_notifier,
                parents_retriever,
                link_verifier,
                RetrieveUserHistoryStub.withoutUserHistory(),
                SearchArtifactsStub.withoutResults(),
                current_artifact_identifier,
                current_tracker_identifier,
                UserIdentifierStub.fromUserId(101)
            ),
            new_link_adder,
            new_link_remover,
            new_links_retriever,
            ParentLinkVerifier(links_retriever_sync, new_links_retriever, parent_identifier),
            parents_retriever,
            link_verifier,
            {
                field_id: FIELD_ID,
                type: "art_link",
                label: "Artifact link",
                allowed_types: allowed_link_types,
            },
            current_artifact_identifier,
            current_tracker_identifier,
            cross_reference,
            ControlLinkedArtifactsPopoversStub.build(),
            AllowedLinksTypesCollection.buildFromTypesRepresentations(allowed_link_types),
            verify_is_tracker_in_a_hierarchy
        );
    };

    describe(`displayField()`, () => {
        it(`returns a presenter for the field and current artifact cross reference`, () => {
            const field = getController().displayField();
            expect(field.field_id).toBe(FIELD_ID);
        });
    });

    describe("getCurrentLinkType()", () => {
        it(`When the tracker has a parent, Then it will return a presenter for the reverse child type`, () => {
            verify_is_tracker_in_a_hierarchy = VerifyIsTrackerInAHierarchyStub.withHierarchy();

            const selected_link_type = getController().getCurrentLinkType(false);
            expect(selected_link_type.shortname).toBe(IS_CHILD_LINK_TYPE);
            expect(selected_link_type.direction).toBe(REVERSE_DIRECTION);
        });

        it(`When the tracker has no parent,
            And the current artifact has no possible parents
            Then it will return a presenter for the untyped link type`, () => {
            verify_is_tracker_in_a_hierarchy = VerifyIsTrackerInAHierarchyStub.withNoHierarchy();

            const selected_link_type = getController().getCurrentLinkType(false);
            expect(selected_link_type.shortname).toBe(UNTYPED_LINK);
            expect(selected_link_type.direction).toBe(FORWARD_DIRECTION);
        });

        it(`When the tracker has no parent,
            And the current artifact has possible parents
            Then it will return a presenter for the reverse child type`, () => {
            verify_is_tracker_in_a_hierarchy = VerifyIsTrackerInAHierarchyStub.withNoHierarchy();

            const selected_link_type = getController().getCurrentLinkType(true);

            expect(selected_link_type.shortname).toBe(IS_CHILD_LINK_TYPE);
            expect(selected_link_type.direction).toBe(REVERSE_DIRECTION);
        });

        it(`When the artifact has already a parent, then it should return a presenter for the untyped link type`, () => {
            parent_identifier = ParentArtifactIdentifierStub.withId(123);
            verify_is_tracker_in_a_hierarchy = VerifyIsTrackerInAHierarchyStub.withHierarchy();

            const selected_link_type = getController().getCurrentLinkType(true);

            expect(selected_link_type.shortname).toBe(UNTYPED_LINK);
            expect(selected_link_type.direction).toBe(FORWARD_DIRECTION);
        });
    });

    describe(`displayAllowedTypes()`, () => {
        const display = (): CollectionOfAllowedLinksTypesPresenters =>
            getController().displayAllowedTypes();

        it(`returns a presenter for the allowed link types and keeps only _is_child type`, () => {
            const types = display();
            expect(types.types).toHaveLength(1);
            expect(types.types[0].forward_type_presenter.shortname).toBe(IS_CHILD_LINK_TYPE);
            expect(types.is_parent_type_disabled).toBe(false);
        });
    });

    describe(`displayLinkedArtifacts()`, () => {
        const displayLinkedArtifacts = (): PromiseLike<LinkedArtifactCollectionPresenter> =>
            getController().displayLinkedArtifacts();

        it(`when the modal is in creation mode,
            it won't notify that there has been a fault
            and it will return an empty presenter`, async () => {
            links_retriever = RetrieveAllLinkedArtifactsStub.withFault(
                NoLinksInCreationModeFault()
            );
            const artifacts = await displayLinkedArtifacts();

            expect(artifacts.has_loaded_content).toBe(true);
            expect(fault_notifier.getCallCount()).toBe(0);
        });

        it(`when the modal is in edition mode and it succeeds loading,
            it will return a presenter with the linked artifacts`, async () => {
            const linked_artifact = LinkedArtifactStub.withDefaults();
            links_retriever = RetrieveAllLinkedArtifactsStub.withLinkedArtifacts(linked_artifact);
            const artifacts = await displayLinkedArtifacts();

            expect(artifacts.has_loaded_content).toBe(true);
        });

        it(`when the modal is in edition mode and it fails loading,
            it will notify that there has been a fault
            and it will return an empty presenter`, async () => {
            links_retriever = RetrieveAllLinkedArtifactsStub.withFault(Fault.fromMessage("Ooops"));
            const artifacts = await displayLinkedArtifacts();

            expect(artifacts.has_loaded_content).toBe(true);
            expect(fault_notifier.getCallCount()).toBe(1);
        });
    });

    describe(`markForRemoval`, () => {
        const markForRemoval = (): LinkedArtifactCollectionPresenter => {
            const identifier = LinkedArtifactIdentifierStub.withId(ARTIFACT_ID);
            const linked_artifact = LinkedArtifactStub.withDefaults({ identifier });
            links_retriever_sync =
                RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(linked_artifact);
            deleted_link_verifier = VerifyLinkIsMarkedForRemovalStub.withAllLinksMarkedForRemoval();
            return getController().markForRemoval(identifier);
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
        const unmarkForRemoval = (): LinkedArtifactCollectionPresenter => {
            const identifier = LinkedArtifactIdentifierStub.withId(ARTIFACT_ID);
            const linked_artifact = LinkedArtifactStub.withDefaults({ identifier });
            links_retriever_sync =
                RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(linked_artifact);
            return getController().unmarkForRemoval(identifier);
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

    describe(`addNewLink`, () => {
        let link_type: LinkType;
        beforeEach(() => {
            link_type = LinkTypeStub.buildParentLinkType();
        });

        const addNewLink = (): NewLinkCollectionPresenter => {
            const linkable_artifact = LinkableArtifactStub.withDefaults({
                id: ARTIFACT_ID,
            });
            new_links_retriever = RetrieveNewLinksStub.withNewLinks(
                NewLinkStub.withIdAndType(ARTIFACT_ID, link_type)
            );
            return getController().addNewLink(linkable_artifact, link_type);
        };

        it(`adds a new link to the stored new links and returns an updated presenter`, () => {
            const links = addNewLink();

            expect(new_link_adder.getCallCount()).toBe(1);
            expect(links).toHaveLength(1);
            expect(links[0].identifier.id).toBe(ARTIFACT_ID);
            expect(links[0].link_type.shortname).toBe(IS_CHILD_LINK_TYPE);
        });
    });

    describe(`removeNewLink`, () => {
        const removeNewLink = (): NewLinkCollectionPresenter => {
            const new_link = NewLinkStub.withDefaults();
            new_links_retriever = RetrieveNewLinksStub.withoutLink();
            return getController().removeNewLink(new_link);
        };

        it(`deletes a new link and returns an updated presenter`, () => {
            const links = removeNewLink();

            expect(new_link_remover.getCallCount()).toBe(1);
            expect(links).toHaveLength(0);
        });
    });

    describe(`retrievePossibleParentsGroups`, () => {
        beforeEach(() => {
            const first_parent = LinkableArtifactStub.withDefaults({ id: FIRST_PARENT_ID });
            const second_parent = LinkableArtifactStub.withDefaults({ id: SECOND_PARENT_ID });
            parents_retriever = RetrievePossibleParentsStub.withParents(
                okAsync([first_parent, second_parent])
            );
        });

        const retrieveParents = (): PromiseLike<GroupOfItems> => {
            return getController().retrievePossibleParentsGroups();
        };

        it(`will return the group of possible parents for this tracker`, async () => {
            const group = await retrieveParents();

            expect(notification_clearer.getCallCount()).toBe(1);
            expect(group.is_loading).toBe(false);
            const parent_ids = group.items.map((item) => {
                const linkable_artifact = item.value as LinkableArtifact;
                return linkable_artifact.id;
            });
            expect(parent_ids).toHaveLength(2);
            expect(parent_ids).toContain(FIRST_PARENT_ID);
            expect(parent_ids).toContain(SECOND_PARENT_ID);
        });

        it(`when there is an error during retrieval of the possible parents,
            it will notify that there has been a fault
            and will return an empty group`, async () => {
            parents_retriever = RetrievePossibleParentsStub.withFault(Fault.fromMessage("Ooops"));

            const group = await retrieveParents();

            expect(fault_notifier.getCallCount()).toBe(1);
            expect(group.is_loading).toBe(false);
            expect(group.items).toHaveLength(0);
        });
    });
});
