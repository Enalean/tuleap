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
import { ArtifactCrossReferenceStub } from "../../../../../tests/stubs/ArtifactCrossReferenceStub";
import { ArtifactLinkSelectorAutoCompleter } from "./dropdown/ArtifactLinkSelectorAutoCompleter";
import { RetrieveMatchingArtifactStub } from "../../../../../tests/stubs/RetrieveMatchingArtifactStub";
import { LinkableArtifactStub } from "../../../../../tests/stubs/LinkableArtifactStub";
import type { LinkableArtifact } from "../../../../domain/fields/link-field/LinkableArtifact";
import { AddNewLinkStub } from "../../../../../tests/stubs/AddNewLinkStub";
import { RetrieveNewLinksStub } from "../../../../../tests/stubs/RetrieveNewLinksStub";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";
import { IS_CHILD_LINK_TYPE, UNTYPED_LINK } from "@tuleap/plugin-tracker-constants";
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
import type { ParentArtifactIdentifier } from "../../../../domain/parent/ParentArtifactIdentifier";
import { VerifyIsTrackerInAHierarchyStub } from "../../../../../tests/stubs/VerifyIsTrackerInAHierarchyStub";
import type { VerifyIsTrackerInAHierarchy } from "../../../../domain/fields/link-field/VerifyIsTrackerInAHierarchy";
import { ParentArtifactIdentifierStub } from "../../../../../tests/stubs/ParentArtifactIdentifierStub";
import { UserIdentifierStub } from "../../../../../tests/stubs/UserIdentifierStub";
import { RetrieveUserHistoryStub } from "../../../../../tests/stubs/RetrieveUserHistoryStub";
import { okAsync } from "neverthrow";
import { SearchArtifactsStub } from "../../../../../tests/stubs/SearchArtifactsStub";
import { DispatchEventsStub } from "../../../../../tests/stubs/DispatchEventsStub";
import { LinkTypesCollectionStub } from "../../../../../tests/stubs/LinkTypesCollectionStub";
import { ChangeNewLinkTypeStub } from "../../../../../tests/stubs/ChangeNewLinkTypeStub";
import { ChangeLinkTypeStub } from "../../../../../tests/stubs/ChangeLinkTypeStub";

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
        new_link_adder: AddNewLinkStub,
        new_links_retriever: RetrieveNewLinks,
        new_link_remover: DeleteNewLinkStub,
        parents_retriever: RetrievePossibleParents,
        parent_identifier: ParentArtifactIdentifier | null,
        verify_is_tracker_in_a_hierarchy: VerifyIsTrackerInAHierarchy,
        event_dispatcher: DispatchEventsStub,
        new_link_type_changer: ChangeNewLinkTypeStub,
        link_type_changer: ChangeLinkTypeStub;

    beforeEach(() => {
        setCatalog({
            getString: (msgid) => msgid,
        });
        links_retriever = RetrieveAllLinkedArtifactsStub.withoutLink();
        links_retriever_sync = RetrieveLinkedArtifactsSyncStub.withoutLink();
        deleted_link_adder = AddLinkMarkedForRemovalStub.withCount();
        deleted_link_remover = DeleteLinkMarkedForRemovalStub.withCount();
        deleted_link_verifier = VerifyLinkIsMarkedForRemovalStub.withNoLinkMarkedForRemoval();
        new_link_adder = AddNewLinkStub.withCount();
        new_links_retriever = RetrieveNewLinksStub.withoutLink();
        new_link_remover = DeleteNewLinkStub.withCount();
        parents_retriever = RetrievePossibleParentsStub.withoutParents();
        parent_identifier = null;
        verify_is_tracker_in_a_hierarchy = VerifyIsTrackerInAHierarchyStub.withNoHierarchy();
        event_dispatcher = DispatchEventsStub.withRecordOfEventTypes();
        new_link_type_changer = ChangeNewLinkTypeStub.withCount();
        link_type_changer = ChangeLinkTypeStub.withCount();
    });

    const getController = (): LinkFieldControllerType => {
        const link_verifier = VerifyIsAlreadyLinkedStub.withNoArtifactAlreadyLinked();
        const current_artifact_identifier = CurrentArtifactIdentifierStub.withId(18);
        const cross_reference = ArtifactCrossReferenceStub.withRef("story #18");
        const current_tracker_identifier = CurrentTrackerIdentifierStub.withId(70);

        return LinkFieldController(
            links_retriever,
            links_retriever_sync,
            link_type_changer,
            deleted_link_adder,
            deleted_link_remover,
            deleted_link_verifier,
            ArtifactLinkSelectorAutoCompleter(
                RetrieveMatchingArtifactStub.withMatchingArtifact(
                    okAsync(LinkableArtifactStub.withDefaults())
                ),
                parents_retriever,
                link_verifier,
                RetrieveUserHistoryStub.withoutUserHistory(),
                SearchArtifactsStub.withoutResults(),
                event_dispatcher,
                current_artifact_identifier,
                current_tracker_identifier,
                UserIdentifierStub.fromUserId(101)
            ),
            new_link_adder,
            new_link_remover,
            new_links_retriever,
            new_link_type_changer,
            ParentLinkVerifier(links_retriever_sync, new_links_retriever, parent_identifier),
            parents_retriever,
            link_verifier,
            verify_is_tracker_in_a_hierarchy,
            event_dispatcher,
            {
                field_id: FIELD_ID,
                type: "art_link",
                label: "Artifact link",
                allowed_types: [],
            },
            current_artifact_identifier,
            current_tracker_identifier,
            cross_reference,
            LinkTypesCollectionStub.withParentPair()
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

        it(`returns a presenter for the allowed link types`, () => {
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
            and it will enable the modal submit again
            and it will return an empty presenter`, async () => {
            links_retriever = RetrieveAllLinkedArtifactsStub.withFault(
                NoLinksInCreationModeFault()
            );
            const artifacts = await displayLinkedArtifacts();

            expect(artifacts.has_loaded_content).toBe(true);
            const event_types = event_dispatcher.getDispatchedEventTypes();
            expect(event_types).toHaveLength(2);
            expect(event_types).not.toContain("WillNotifyFault");
            expect(event_types).toContain("WillDisableSubmit");
            expect(event_types).toContain("WillEnableSubmit");
        });

        it(`when the modal is in edition mode and it succeeds loading,
            and it will disable the modal submit while links are loading, so that existing links are not erased by mistake
            it will return a presenter with the linked artifacts`, async () => {
            const linked_artifact = LinkedArtifactStub.withDefaults();
            links_retriever = RetrieveAllLinkedArtifactsStub.withLinkedArtifacts(linked_artifact);
            const artifacts = await displayLinkedArtifacts();

            expect(artifacts.has_loaded_content).toBe(true);
            const event_types = event_dispatcher.getDispatchedEventTypes();
            expect(event_types).toHaveLength(2);
            expect(event_types).toContain("WillDisableSubmit");
            expect(event_types).toContain("WillEnableSubmit");
        });

        it(`when the modal is in edition mode and it fails loading,
            it will notify that there has been a fault
            and it will not enable again the modal submit, so that existing links are not erased by mistake
            and it will return an empty presenter`, async () => {
            links_retriever = RetrieveAllLinkedArtifactsStub.withFault(Fault.fromMessage("Ooops"));
            const artifacts = await displayLinkedArtifacts();

            expect(artifacts.has_loaded_content).toBe(true);
            const event_types = event_dispatcher.getDispatchedEventTypes();
            expect(event_types).toHaveLength(2);
            expect(event_types).toContain("WillNotifyFault");
            expect(event_types).toContain("WillDisableSubmit");
            expect(event_types).not.toContain("WillEnableSubmit");
        });
    });

    describe(`canMarkForRemoval()`, () => {
        let link_type: LinkType;
        beforeEach(() => {
            link_type = LinkTypeStub.buildUntyped();
        });

        const canMark = (): boolean => {
            const linked_artifact = LinkedArtifactStub.withIdAndType(ARTIFACT_ID, link_type);
            return getController().canMarkForRemoval(linked_artifact);
        };

        it(`returns false when the given artifact's link type is _mirrored_milestone`, () => {
            link_type = LinkTypeStub.buildMirrors();
            expect(canMark()).toBe(false);
        });

        it(`returns true otherwise`, () => {
            expect(canMark()).toBe(true);
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

    describe(`canChangeType()`, () => {
        let link_type: LinkType;

        beforeEach(() => {
            link_type = LinkTypeStub.buildUntyped();
        });
        const canChangeType = (): boolean => {
            const linked_artifact = LinkedArtifactStub.withIdAndType(ARTIFACT_ID, link_type);
            return getController().canChangeType(linked_artifact);
        };

        it(`returns false when the given artifact's link type is _mirrored_milestone`, () => {
            link_type = LinkTypeStub.buildMirroredBy();
            expect(canChangeType()).toBe(false);
        });

        it(`returns true otherwise`, () => {
            expect(canChangeType()).toBe(true);
        });
    });

    describe(`changeLinkType()`, () => {
        const changeLinkType = (): LinkedArtifactCollectionPresenter => {
            const link = LinkedArtifactStub.withIdAndType(113, LinkTypeStub.buildUntyped());
            const type = LinkTypeStub.buildForwardCustom();
            links_retriever_sync = RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(link);
            return getController().changeLinkType(link, type);
        };

        it(`changes the type of link for the existing link and returns an updated presenter`, () => {
            const presenter = changeLinkType();
            expect(link_type_changer.getCallCount()).toBe(1);
            expect(presenter.linked_artifacts).toHaveLength(1);
        });
    });

    describe(`addNewLink`, () => {
        let link_type: LinkType;
        beforeEach(() => {
            link_type = LinkTypeStub.buildChildLinkType();
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

    describe(`changeNewLinkType()`, () => {
        const changeNewLinkType = (): NewLinkCollectionPresenter => {
            const new_link = NewLinkStub.withIdAndType(96, LinkTypeStub.buildUntyped());
            const type = LinkTypeStub.buildForwardCustom();
            new_links_retriever = RetrieveNewLinksStub.withNewLinks(new_link);
            return getController().changeNewLinkType(new_link, type);
        };

        it(`changes the type of link for the new link and returns an updated presenter`, () => {
            const links = changeNewLinkType();
            expect(new_link_type_changer.getCallCount()).toBe(1);
            expect(links).toHaveLength(1);
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

            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillNotifyFault");
            expect(group.is_loading).toBe(false);
            expect(group.items).toHaveLength(0);
        });
    });
});
