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
import type {
    LinkFieldControllerType,
    LinkFieldPresenterAndAllowedLinkTypes,
} from "./LinkFieldController";
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
import { ArtifactLinkSelectorAutoCompleter } from "./ArtifactLinkSelectorAutoCompleter";
import { RetrieveMatchingArtifactStub } from "../../../../../tests/stubs/RetrieveMatchingArtifactStub";
import { LinkableArtifactStub } from "../../../../../tests/stubs/LinkableArtifactStub";
import type { LinkAdditionPresenter } from "./LinkAdditionPresenter";
import type { LinkableArtifact } from "../../../../domain/fields/link-field-v2/LinkableArtifact";
import { AddNewLinkStub } from "../../../../../tests/stubs/AddNewLinkStub";
import { RetrieveNewLinksStub } from "../../../../../tests/stubs/RetrieveNewLinksStub";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";
import { IS_CHILD_LINK_TYPE, UNTYPED_LINK } from "@tuleap/plugin-tracker-constants";
import { ClearFaultNotificationStub } from "../../../../../tests/stubs/ClearFaultNotificationStub";
import type { RetrieveLinkedArtifactsSync } from "../../../../domain/fields/link-field-v2/RetrieveLinkedArtifactsSync";
import type { VerifyLinkIsMarkedForRemoval } from "../../../../domain/fields/link-field-v2/VerifyLinkIsMarkedForRemoval";
import type { RetrieveNewLinks } from "../../../../domain/fields/link-field-v2/RetrieveNewLinks";
import { DeleteNewLinkStub } from "../../../../../tests/stubs/DeleteNewLinkStub";
import { NewLinkStub } from "../../../../../tests/stubs/NewLinkStub";
import { ParentLinkVerifier } from "../../../../domain/fields/link-field-v2/ParentLinkVerifier";
import type { RetrieveSelectedLinkType } from "../../../../domain/fields/link-field-v2/RetrieveSelectedLinkType";
import { SetSelectedLinkTypeStub } from "../../../../../tests/stubs/SetSelectedLinkTypeStub";
import { RetrieveSelectedLinkTypeStub } from "../../../../../tests/stubs/RetrieveSelectedLinkTypeStub";
import type { SetSelectedLinkType } from "../../../../domain/fields/link-field-v2/SetSelectedLinkType";
import type { LinkType } from "../../../../domain/fields/link-field-v2/LinkType";
import { FORWARD_DIRECTION } from "../../../../domain/fields/link-field-v2/LinkType";
import { RetrievePossibleParentsStub } from "../../../../../tests/stubs/RetrievePossibleParentsStub";
import { CurrentTrackerIdentifierStub } from "../../../../../tests/stubs/CurrentTrackerIdentifierStub";
import type { RetrievePossibleParents } from "../../../../domain/fields/link-field-v2/RetrievePossibleParents";
import { LinkSelectorStub } from "../../../../../tests/stubs/LinkSelectorStub";
import { setCatalog } from "../../../../gettext-catalog";
import type { GroupCollection } from "@tuleap/link-selector";
import { VerifyIsAlreadyLinkedStub } from "../../../../../tests/stubs/VerifyIsAlreadyLinkedStub";
import type { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";
import type { NewLinkCollectionPresenter } from "./NewLinkCollectionPresenter";

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
        type_retriever: RetrieveSelectedLinkType,
        type_setter: SetSelectedLinkType,
        notification_clearer: ClearFaultNotificationStub,
        parents_retriever: RetrievePossibleParents;

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
        type_retriever = RetrieveSelectedLinkTypeStub.withType(LinkTypeStub.buildUntyped());
        type_setter = SetSelectedLinkTypeStub.buildPassThrough();
        notification_clearer = ClearFaultNotificationStub.withCount();
        parents_retriever = RetrievePossibleParentsStub.withoutParents();
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
                    LinkableArtifactStub.withDefaults()
                ),
                fault_notifier,
                notification_clearer,
                type_retriever,
                parents_retriever,
                link_verifier,
                current_artifact_identifier,
                current_tracker_identifier
            ),
            new_link_adder,
            new_link_remover,
            new_links_retriever,
            ParentLinkVerifier(links_retriever_sync, new_links_retriever, null),
            type_retriever,
            type_setter,
            parents_retriever,
            link_verifier,
            {
                field_id: FIELD_ID,
                type: "art_link",
                label: "Artifact link",
                allowed_types: [
                    {
                        shortname: IS_CHILD_LINK_TYPE,
                        forward_label: "Child",
                        reverse_label: "Parent",
                    },
                    {
                        shortname: "custom",
                        forward_label: "Custom Forward",
                        reverse_label: "Custom Reverse",
                    },
                ],
            },
            current_artifact_identifier,
            current_tracker_identifier,
            cross_reference
        );
    };

    describe(`displayField()`, () => {
        const displayField = (): LinkFieldPresenterAndAllowedLinkTypes =>
            getController().displayField();

        it(`returns a presenter for the field and current artifact cross reference`, () => {
            const { field } = displayField();
            expect(field.field_id).toBe(FIELD_ID);
        });

        it(`returns a presenter containing the selected link type`, () => {
            const { selected_link_type } = displayField();
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
        const displayLinkedArtifacts = (): Promise<LinkedArtifactCollectionPresenter> =>
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

    describe(`onLinkableArtifactSelection`, () => {
        const onSelection = (artifact: LinkableArtifact | null): LinkAdditionPresenter =>
            getController().onLinkableArtifactSelection(artifact);

        it(`when selection is null, it will return a presenter with disabled button`, () => {
            const presenter = onSelection(null);
            expect(presenter.is_add_button_disabled).toBe(true);
        });

        it(`when an artifact is selected, it will return a presenter with enabled button`, () => {
            const presenter = onSelection(LinkableArtifactStub.withDefaults());
            expect(presenter.is_add_button_disabled).toBe(false);
        });
    });

    describe(`addNewLink`, () => {
        let first_type: LinkType, second_type: LinkType;
        beforeEach(() => {
            first_type = LinkTypeStub.buildParentLinkType();
            second_type = LinkTypeStub.buildUntyped();
        });

        const addNewLink = (): NewLinkCollectionPresenter => {
            type_retriever = RetrieveSelectedLinkTypeStub.withSuccessiveTypes(
                first_type,
                second_type
            );
            const linkable_artifact = LinkableArtifactStub.withDefaults({
                id: ARTIFACT_ID,
            });
            new_links_retriever = RetrieveNewLinksStub.withNewLinks(
                NewLinkStub.withIdAndType(ARTIFACT_ID, first_type)
            );
            return getController().addNewLink(linkable_artifact);
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

    describe(`setSelectedLinkType`, () => {
        let link_selector: LinkSelectorStub;

        beforeEach(() => {
            link_selector = LinkSelectorStub.withDropdownContentRecord();
        });

        const getGroupCollection = (): GroupCollection => {
            const groups = link_selector.getGroupCollection();
            if (groups === undefined) {
                throw new Error("Expected a group collection to be set");
            }
            return groups;
        };

        const setSelectedLinkType = (type: LinkType): LinkType => {
            return getController().setSelectedLinkType(link_selector, type);
        };

        it(`when the new type is NOT reverse _is_child
            it stores the new selected link type,
            and returns the new selected link type`, () => {
            const new_type = LinkTypeStub.buildReverseCustom();
            const result = setSelectedLinkType(new_type);

            expect(result).toBe(new_type);
        });

        describe(`when the new type is reverse _is_child (Parent)`, () => {
            let parent_type: LinkType;
            beforeEach(() => {
                parent_type = LinkTypeStub.buildParentLinkType();
                const first_parent = LinkableArtifactStub.withDefaults({ id: FIRST_PARENT_ID });
                const second_parent = LinkableArtifactStub.withDefaults({ id: SECOND_PARENT_ID });
                parents_retriever = RetrievePossibleParentsStub.withParents(
                    first_parent,
                    second_parent
                );
            });

            it(`will load the possible parents for this tracker
                and will set the dropdown content with a group of possible parents
                and returns the new selected link type`, async () => {
                const result = setSelectedLinkType(parent_type);

                expect(notification_clearer.getCallCount()).toBe(1);
                const loading_groups = getGroupCollection();
                expect(loading_groups).toHaveLength(1);
                expect(loading_groups[0].is_loading).toBe(true);

                await result; // wait for the ResultAsync of parents_retriever

                expect(result).toBe(parent_type);
                const groups = getGroupCollection();
                expect(groups).toHaveLength(1);
                expect(groups[0].is_loading).toBe(false);
                const parent_ids = groups[0].items.map((item) => {
                    const linkable_artifact = item.value as LinkableArtifact;
                    return linkable_artifact.id;
                });
                expect(parent_ids).toHaveLength(2);
                expect(parent_ids).toContain(FIRST_PARENT_ID);
                expect(parent_ids).toContain(SECOND_PARENT_ID);
            });

            it(`and there is an error during retrieval of the possible parents,
                it will notify that there has been a fault
                and will set the dropdown content with an empty group of possible parents`, async () => {
                parents_retriever = RetrievePossibleParentsStub.withFault(
                    Fault.fromMessage("Ooops")
                );

                const result = await setSelectedLinkType(parent_type);

                expect(result).toBe(parent_type);
                expect(fault_notifier.getCallCount()).toBe(1);
                const groups = getGroupCollection();
                expect(groups).toHaveLength(1);
                expect(groups[0].is_loading).toBe(false);
                expect(groups[0].items).toHaveLength(0);
            });
        });
    });
});
