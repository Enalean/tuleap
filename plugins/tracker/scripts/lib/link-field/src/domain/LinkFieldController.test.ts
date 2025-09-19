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

import { beforeEach, describe, expect, it } from "vitest";
import { Fault } from "@tuleap/fault";
import { okAsync } from "neverthrow";
import { Option } from "@tuleap/option";
import {
    FORWARD_DIRECTION,
    IS_CHILD_LINK_TYPE,
    REVERSE_DIRECTION,
    DEFAULT_LINK_TYPE,
} from "@tuleap/plugin-tracker-constants";
import {
    CurrentProjectIdentifier,
    CurrentTrackerIdentifier,
    ParentArtifactIdentifier,
} from "@tuleap/plugin-tracker-artifact-common";
import { LinkFieldController } from "./LinkFieldController";
import { RetrieveAllLinkedArtifactsStub } from "../../tests/stubs/links/RetrieveAllLinkedArtifactsStub";
import type { RetrieveAllLinkedArtifacts } from "./links/RetrieveAllLinkedArtifacts";
import { NoLinksInCreationModeFault } from "./links/NoLinksInCreationModeFault";
import { RetrieveLinkedArtifactsSyncStub } from "../../tests/stubs/links/RetrieveLinkedArtifactsSyncStub";
import { AddLinkMarkedForRemovalStub } from "../../tests/stubs/links/AddLinkMarkedForRemovalStub";
import { DeleteLinkMarkedForRemovalStub } from "../../tests/stubs/links/DeleteLinkMarkedForRemovalStub";
import { VerifyLinkIsMarkedForRemovalStub } from "../../tests/stubs/links/VerifyLinkIsMarkedForRemovalStub";
import { LinkedArtifactStub } from "../../tests/stubs/links/LinkedArtifactStub";
import { LinkedArtifactIdentifierStub } from "../../tests/stubs/links/LinkedArtifactIdentifierStub";
import { ArtifactCrossReferenceStub } from "../../tests/stubs/ArtifactCrossReferenceStub";
import { LinkableArtifactStub } from "../../tests/stubs/links/LinkableArtifactStub";
import type { LinkableArtifact } from "./links/LinkableArtifact";
import { AddNewLinkStub } from "../../tests/stubs/links/AddNewLinkStub";
import { RetrieveNewLinksStub } from "../../tests/stubs/links/RetrieveNewLinksStub";
import { LinkTypeStub } from "../../tests/stubs/links/LinkTypeStub";
import type { RetrieveLinkedArtifactsSync } from "./links/RetrieveLinkedArtifactsSync";
import type { VerifyLinkIsMarkedForRemoval } from "./links/VerifyLinkIsMarkedForRemoval";
import type { RetrieveNewLinks } from "./links/RetrieveNewLinks";
import { DeleteNewLinkStub } from "../../tests/stubs/links/DeleteNewLinkStub";
import { NewLinkStub } from "../../tests/stubs/links/NewLinkStub";
import type { LinkType } from "./links/LinkType";
import { RetrievePossibleParentsStub } from "../../tests/stubs/RetrievePossibleParentsStub";
import type { RetrievePossibleParents } from "./RetrievePossibleParents";
import { DispatchEventsStub } from "../../tests/stubs/DispatchEventsStub";
import { LinkTypesCollectionStub } from "../../tests/stubs/links/LinkTypesCollectionStub";
import { ChangeNewLinkTypeStub } from "../../tests/stubs/links/ChangeNewLinkTypeStub";
import { ChangeLinkTypeStub } from "../../tests/stubs/links/ChangeLinkTypeStub";
import { LabeledFieldStub } from "../../tests/stubs/LabeledFieldStub";
import { ParentTrackerIdentifier } from "./ParentTrackerIdentifier";
import type { LinkTypesCollection } from "./links/LinkTypesCollection";
import type { LinkedArtifact } from "./links/LinkedArtifact";
import type { NewLink } from "./links/NewLink";
import { ProjectStub } from "../../tests/stubs/ProjectStub";

const ARTIFACT_ID = 60;
const FIELD_ID = 714;
const FIRST_PARENT_ID = 527;
const SECOND_PARENT_ID = 548;
const CURRENT_PROJECT = 10;

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
        parent_artifact_identifier: Option<ParentArtifactIdentifier>,
        event_dispatcher: DispatchEventsStub,
        new_link_type_changer: ChangeNewLinkTypeStub,
        link_type_changer: ChangeLinkTypeStub,
        parent_tracker_identifier: Option<ParentTrackerIdentifier>;

    beforeEach(() => {
        links_retriever = RetrieveAllLinkedArtifactsStub.withoutLink();
        links_retriever_sync = RetrieveLinkedArtifactsSyncStub.withoutLink();
        deleted_link_adder = AddLinkMarkedForRemovalStub.withCount();
        deleted_link_remover = DeleteLinkMarkedForRemovalStub.withCount();
        deleted_link_verifier = VerifyLinkIsMarkedForRemovalStub.withNoLinkMarkedForRemoval();
        new_link_adder = AddNewLinkStub.withCount();
        new_links_retriever = RetrieveNewLinksStub.withoutLink();
        new_link_remover = DeleteNewLinkStub.withCount();
        parents_retriever = RetrievePossibleParentsStub.withoutParents();
        parent_artifact_identifier = Option.nothing();
        event_dispatcher = DispatchEventsStub.withRecordOfEventTypes();
        new_link_type_changer = ChangeNewLinkTypeStub.withCount();
        link_type_changer = ChangeLinkTypeStub.withCount();
        parent_tracker_identifier = Option.nothing();
    });

    const getController = (): LinkFieldController => {
        const cross_reference = Option.fromValue(ArtifactCrossReferenceStub.withRef("story #18"));
        const current_tracker_identifier = CurrentTrackerIdentifier.fromId(70);

        return LinkFieldController(
            links_retriever,
            links_retriever_sync,
            link_type_changer,
            deleted_link_adder,
            deleted_link_remover,
            deleted_link_verifier,
            new_link_adder,
            new_link_remover,
            new_links_retriever,
            new_link_type_changer,
            parents_retriever,
            event_dispatcher,
            LabeledFieldStub.withDefaults({ field_id: FIELD_ID }),
            current_tracker_identifier,
            parent_tracker_identifier,
            cross_reference,
            LinkTypesCollectionStub.withParentPair(),
            CurrentProjectIdentifier.fromId(CURRENT_PROJECT),
            parent_artifact_identifier,
        );
    };

    describe(`getLabeledField()`, () => {
        it(`returns the artifact link field's field ID and label`, () => {
            const field = getController().getLabeledField();
            expect(field.field_id).toBe(FIELD_ID);
        });
    });

    describe(`hasParentLink()`, () => {
        it(`returns true when the artifact under creation was given a parent by the caller of the modal`, () => {
            parent_artifact_identifier = Option.fromValue(ParentArtifactIdentifier.fromId(318));
            expect(getController().hasParentLink([], [])).toBe(true);
        });

        it(`returns false when there is no link at all`, () => {
            expect(getController().hasParentLink([], [])).toBe(false);
        });

        it(`returns false when there is no existing reverse _is_child link`, () => {
            expect(
                getController().hasParentLink(
                    [LinkedArtifactStub.withIdAndType(357, LinkTypeStub.buildDefaultLinkType())],
                    [],
                ),
            ).toBe(false);
        });

        it(`returns false when there is no new reverse _is_child link`, () => {
            expect(
                getController().hasParentLink(
                    [],
                    [NewLinkStub.withIdAndType(162, LinkTypeStub.buildDefaultLinkType())],
                ),
            ).toBe(false);
        });

        it(`returns true when a new reverse _is_child link exists`, () => {
            expect(
                getController().hasParentLink(
                    [],
                    [NewLinkStub.withIdAndType(162, LinkTypeStub.buildChildLinkType())],
                ),
            ).toBe(true);
        });

        it(`returns true when an existing reverse _is_child link exists`, () => {
            expect(
                getController().hasParentLink(
                    [LinkedArtifactStub.withIdAndType(357, LinkTypeStub.buildChildLinkType())],
                    [],
                ),
            ).toBe(true);
        });
    });

    describe("getCurrentLinkType()", () => {
        it(`When the tracker has a parent, Then it will return the reverse child type`, () => {
            parent_tracker_identifier = Option.fromValue(ParentTrackerIdentifier.fromId(217));

            const selected_link_type = getController().getCurrentLinkType(false, [], []);
            expect(selected_link_type.shortname).toBe(IS_CHILD_LINK_TYPE);
            expect(selected_link_type.direction).toBe(REVERSE_DIRECTION);
        });

        it(`When the tracker has no parent,
            And the current artifact has no possible parents
            Then it will return the default link type`, () => {
            parent_tracker_identifier = Option.nothing();

            const selected_link_type = getController().getCurrentLinkType(false, [], []);
            expect(selected_link_type.shortname).toBe(DEFAULT_LINK_TYPE);
            expect(selected_link_type.direction).toBe(FORWARD_DIRECTION);
        });

        it(`When the tracker has no parent,
            And the current artifact has possible parents
            Then it will return the reverse child type`, () => {
            parent_tracker_identifier = Option.nothing();

            const selected_link_type = getController().getCurrentLinkType(true, [], []);

            expect(selected_link_type.shortname).toBe(IS_CHILD_LINK_TYPE);
            expect(selected_link_type.direction).toBe(REVERSE_DIRECTION);
        });

        it(`When the artifact under creation was given a parent by the caller of the modal,
            then it will return the default link type`, () => {
            parent_artifact_identifier = Option.fromValue(ParentArtifactIdentifier.fromId(123));
            parent_tracker_identifier = Option.fromValue(ParentTrackerIdentifier.fromId(88));

            const selected_link_type = getController().getCurrentLinkType(true, [], []);

            expect(selected_link_type.shortname).toBe(DEFAULT_LINK_TYPE);
            expect(selected_link_type.direction).toBe(FORWARD_DIRECTION);
        });

        it(`When the artifact has an existing parent link, then it should return the default link type`, () => {
            const selected_link_type = getController().getCurrentLinkType(
                true,
                [LinkedArtifactStub.withIdAndType(534, LinkTypeStub.buildChildLinkType())],
                [],
            );

            expect(selected_link_type.shortname).toBe(DEFAULT_LINK_TYPE);
            expect(selected_link_type.direction).toBe(FORWARD_DIRECTION);
        });

        it(`When the artifact has a new parent link, then it should return the default link type`, () => {
            const selected_link_type = getController().getCurrentLinkType(
                true,
                [],
                [NewLinkStub.withIdAndType(92, LinkTypeStub.buildChildLinkType())],
            );

            expect(selected_link_type.shortname).toBe(DEFAULT_LINK_TYPE);
            expect(selected_link_type.direction).toBe(FORWARD_DIRECTION);
        });
    });

    describe(`getAllowedLinkTypes()`, () => {
        const getTypes = (): LinkTypesCollection => getController().getAllowedLinkTypes();

        it(`returns the collection of allowed link types`, () => {
            const types = getTypes().getAll();
            expect(types).toHaveLength(1);
            expect(types[0].forward_type.shortname).toBe(IS_CHILD_LINK_TYPE);
        });
    });

    describe(`getLinkedArtifacts()`, () => {
        const displayLinkedArtifacts = (): PromiseLike<ReadonlyArray<LinkedArtifact>> =>
            getController().getLinkedArtifacts("Links are loading");

        it(`when the modal is in creation mode,
            it won't notify that there has been a fault
            and it will enable the modal submit again
            and it will return an empty array`, async () => {
            links_retriever = RetrieveAllLinkedArtifactsStub.withFault(
                NoLinksInCreationModeFault(),
            );
            const artifacts = await displayLinkedArtifacts();

            expect(artifacts).toHaveLength(0);
            const event_types = event_dispatcher.getDispatchedEventTypes();
            expect(event_types).toHaveLength(2);
            expect(event_types).not.toContain("WillNotifyFault");
            expect(event_types).toContain("WillDisableSubmit");
            expect(event_types).toContain("WillEnableSubmit");
        });

        it(`when the modal is in edition mode and it succeeds loading,
            and it will disable the modal submit while links are loading, so that existing links are not erased by mistake
            it will return the linked artifacts`, async () => {
            const linked_artifact = LinkedArtifactStub.withDefaults();
            links_retriever = RetrieveAllLinkedArtifactsStub.withLinkedArtifacts(linked_artifact);
            const artifacts = await displayLinkedArtifacts();

            expect(artifacts).toHaveLength(1);
            const event_types = event_dispatcher.getDispatchedEventTypes();
            expect(event_types).toHaveLength(3);
            expect(event_types).toContain("WillDisableSubmit");
            expect(event_types).toContain("WillEnableSubmit");
            expect(event_types).toContain("DidChangeLinkFieldValue");
        });

        it(`when the modal is in edition mode and it fails loading,
            it will notify that there has been a fault
            and it will not enable again the modal submit, so that existing links are not erased by mistake
            and it will return an empty array`, async () => {
            links_retriever = RetrieveAllLinkedArtifactsStub.withFault(Fault.fromMessage("Ooops"));
            const artifacts = await displayLinkedArtifacts();

            expect(artifacts).toHaveLength(0);
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
            link_type = LinkTypeStub.buildDefaultLinkType();
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

    describe(`isMarkedForRemoval`, () => {
        const isMarked = (): boolean => {
            const linked_artifact = LinkedArtifactStub.withDefaults();
            return getController().isMarkedForRemoval(linked_artifact);
        };

        it(`returns true when a given artifact is marked for removal`, () => {
            deleted_link_verifier = VerifyLinkIsMarkedForRemovalStub.withAllLinksMarkedForRemoval();
            expect(isMarked()).toBe(true);
        });

        it(`returns false otherwise`, () => {
            expect(isMarked()).toBe(false);
        });
    });

    describe(`markForRemoval`, () => {
        const markForRemoval = (): ReadonlyArray<LinkedArtifact> => {
            const identifier = LinkedArtifactIdentifierStub.withId(ARTIFACT_ID);
            const linked_artifact = LinkedArtifactStub.withDefaults({ identifier });
            links_retriever_sync =
                RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(linked_artifact);
            deleted_link_verifier = VerifyLinkIsMarkedForRemovalStub.withAllLinksMarkedForRemoval();
            return getController().markForRemoval(identifier);
        };

        it(`stores the given identifier as a link marked for removal
            and returns the list of linked artifacts`, () => {
            const artifacts = markForRemoval();

            expect(deleted_link_adder.getCallCount()).toBe(1);
            expect(artifacts).toHaveLength(1);
            expect(event_dispatcher.getDispatchedEventTypes()[0]).toBe("DidChangeLinkFieldValue");
        });
    });

    describe(`unmarkForRemoval`, () => {
        const unmarkForRemoval = (): ReadonlyArray<LinkedArtifact> => {
            const identifier = LinkedArtifactIdentifierStub.withId(ARTIFACT_ID);
            const linked_artifact = LinkedArtifactStub.withDefaults({ identifier });
            links_retriever_sync =
                RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(linked_artifact);
            return getController().unmarkForRemoval(identifier);
        };

        it(`deletes the given identifier in the stored links marked for removal,
            and returns the list of linked artifacts`, () => {
            const artifacts = unmarkForRemoval();

            expect(deleted_link_remover.getCallCount()).toBe(1);
            expect(artifacts).toHaveLength(1);
            expect(event_dispatcher.getDispatchedEventTypes()[0]).toBe("DidChangeLinkFieldValue");
        });
    });

    describe(`canChangeType()`, () => {
        let link_type: LinkType;

        beforeEach(() => {
            link_type = LinkTypeStub.buildDefaultLinkType();
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
        const changeLinkType = (): ReadonlyArray<LinkedArtifact> => {
            const link = LinkedArtifactStub.withIdAndType(113, LinkTypeStub.buildDefaultLinkType());
            const type = LinkTypeStub.buildForwardCustom();
            links_retriever_sync = RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(link);
            return getController().changeLinkType(link, type);
        };

        it(`changes the type of link for the existing link and returns the list of linked artifacts`, () => {
            const artifacts = changeLinkType();
            expect(link_type_changer.getCallCount()).toBe(1);
            expect(artifacts).toHaveLength(1);
            expect(event_dispatcher.getDispatchedEventTypes()[0]).toBe("DidChangeLinkFieldValue");
        });
    });

    describe(`addNewLink`, () => {
        let link_type: LinkType;
        beforeEach(() => {
            link_type = LinkTypeStub.buildChildLinkType();
        });

        const addNewLink = (): ReadonlyArray<NewLink> => {
            const linkable_artifact = LinkableArtifactStub.withDefaults({
                id: ARTIFACT_ID,
            });
            new_links_retriever = RetrieveNewLinksStub.withNewLinks(
                NewLinkStub.withIdAndType(ARTIFACT_ID, link_type),
            );
            return getController().addNewLink(linkable_artifact, link_type);
        };

        it(`adds a new link to the stored new links and returns the list of new links`, () => {
            const links = addNewLink();

            expect(new_link_adder.getCallCount()).toBe(1);
            expect(links).toHaveLength(1);
            expect(links[0].identifier.id).toBe(ARTIFACT_ID);
            expect(links[0].link_type.shortname).toBe(IS_CHILD_LINK_TYPE);
            expect(event_dispatcher.getDispatchedEventTypes()[0]).toBe("DidChangeLinkFieldValue");
        });
    });

    describe(`removeNewLink`, () => {
        const removeNewLink = (): ReadonlyArray<NewLink> => {
            const new_link = NewLinkStub.withDefaults();
            new_links_retriever = RetrieveNewLinksStub.withoutLink();
            return getController().removeNewLink(new_link);
        };

        it(`deletes a new link and returns the list of new links`, () => {
            const links = removeNewLink();

            expect(new_link_remover.getCallCount()).toBe(1);
            expect(links).toHaveLength(0);
            expect(event_dispatcher.getDispatchedEventTypes()[0]).toBe("DidChangeLinkFieldValue");
        });
    });

    describe(`changeNewLinkType()`, () => {
        const changeNewLinkType = (): ReadonlyArray<NewLink> => {
            const new_link = NewLinkStub.withIdAndType(96, LinkTypeStub.buildDefaultLinkType());
            const type = LinkTypeStub.buildForwardCustom();
            new_links_retriever = RetrieveNewLinksStub.withNewLinks(new_link);
            return getController().changeNewLinkType(new_link, type);
        };

        it(`changes the type of link for the new link and returns the list of new links`, () => {
            const links = changeNewLinkType();
            expect(new_link_type_changer.getCallCount()).toBe(1);
            expect(links).toHaveLength(1);
            expect(event_dispatcher.getDispatchedEventTypes()[0]).toBe("DidChangeLinkFieldValue");
        });
    });

    describe(`getPossibleParents`, () => {
        beforeEach(() => {
            const first_parent = LinkableArtifactStub.withDefaults({ id: FIRST_PARENT_ID });
            const second_parent = LinkableArtifactStub.withDefaults({ id: SECOND_PARENT_ID });
            parents_retriever = RetrievePossibleParentsStub.withParents(
                okAsync([first_parent, second_parent]),
            );
        });

        const getParents = (): PromiseLike<ReadonlyArray<LinkableArtifact>> => {
            return getController().getPossibleParents();
        };

        it(`will return the possible parents for this tracker`, async () => {
            const parents = await getParents();

            const parent_ids = parents.map((parent) => parent.id);
            expect(parent_ids).toHaveLength(2);
            expect(parent_ids).toContain(FIRST_PARENT_ID);
            expect(parent_ids).toContain(SECOND_PARENT_ID);
        });

        it(`when there is an error during retrieval of the possible parents,
            it will notify that there has been a fault
            and will return an empty array`, async () => {
            parents_retriever = RetrievePossibleParentsStub.withFault(Fault.fromMessage("Ooops"));

            const parents = await getParents();

            expect(event_dispatcher.getDispatchedEventTypes()).toContain("WillNotifyFault");
            expect(parents).toHaveLength(0);
        });
    });
    describe("isLinkedArtifactIsInCurrentProject", () => {
        it.each([
            [true, "the same", CURRENT_PROJECT],
            [false, "NOT the same", 15],
        ])(
            "return %s if the artifact's project is %s as the current project",
            (result: boolean, expected_result: string, project_id: number) => {
                const current_artifact = LinkedArtifactStub.withProject(
                    ProjectStub.withDefaults({ id: project_id }),
                );
                expect(getController().isLinkedArtifactInCurrentProject(current_artifact)).toBe(
                    result,
                );
            },
        );
    });
});
