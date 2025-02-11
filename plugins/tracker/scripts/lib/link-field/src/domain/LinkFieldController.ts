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

import type { Option } from "@tuleap/option";
import type { Fault } from "@tuleap/fault";
import type {
    CommonEvents,
    CurrentProjectIdentifier,
    CurrentTrackerIdentifier,
    DispatchEvents,
    ParentArtifactIdentifier,
} from "@tuleap/plugin-tracker-artifact-common";
import {
    WillClearFaultNotification,
    WillDisableSubmit,
    WillEnableSubmit,
    WillNotifyFault,
} from "@tuleap/plugin-tracker-artifact-common";
import type { RetrieveAllLinkedArtifacts } from "./links/RetrieveAllLinkedArtifacts";
import type { LinkedArtifact, LinkedArtifactIdentifier } from "./links/LinkedArtifact";
import type { AddLinkMarkedForRemoval } from "./links/AddLinkMarkedForRemoval";
import type { DeleteLinkMarkedForRemoval } from "./links/DeleteLinkMarkedForRemoval";
import type { VerifyLinkIsMarkedForRemoval } from "./links/VerifyLinkIsMarkedForRemoval";
import type { RetrieveLinkedArtifactsSync } from "./links/RetrieveLinkedArtifactsSync";
import { LinkRetrievalFault } from "./links/LinkRetrievalFault";
import type { LabeledField } from "./LabeledField";
import type { ArtifactCrossReference } from "./ArtifactCrossReference";
import type { LinkableArtifact } from "./links/LinkableArtifact";
import type { AddNewLink } from "./links/AddNewLink";
import type { RetrieveNewLinks } from "./links/RetrieveNewLinks";
import { NewLink } from "./links/NewLink";
import { LinkType } from "./links/LinkType";
import type { DeleteNewLink } from "./links/DeleteNewLink";
import type { RetrievePossibleParents } from "./RetrievePossibleParents";
import type { LinkTypesCollection } from "./links/LinkTypesCollection";
import type { ChangeNewLinkType } from "./links/ChangeNewLinkType";
import type { ChangeLinkType } from "./links/ChangeLinkType";
import type { ParentTrackerIdentifier } from "./ParentTrackerIdentifier";

export type LinkFieldController = {
    getCurrentArtifactReference(): Option<ArtifactCrossReference>;
    getLabeledField(): LabeledField;
    getLinkedArtifacts(disable_submit_message: string): PromiseLike<readonly LinkedArtifact[]>;
    getAllowedLinkTypes(): LinkTypesCollection;
    canMarkForRemoval(link: LinkedArtifact): boolean;
    isMarkedForRemoval(artifact: LinkedArtifact): boolean;
    markForRemoval(artifact_id: LinkedArtifactIdentifier): ReadonlyArray<LinkedArtifact>;
    unmarkForRemoval(artifact_id: LinkedArtifactIdentifier): ReadonlyArray<LinkedArtifact>;
    canChangeType(link: LinkedArtifact): boolean;
    changeLinkType(link: LinkedArtifact, new_link_type: LinkType): ReadonlyArray<LinkedArtifact>;
    addNewLink(artifact: LinkableArtifact, type: LinkType): ReadonlyArray<NewLink>;
    removeNewLink(link: NewLink): ReadonlyArray<NewLink>;
    changeNewLinkType(link: NewLink, new_link_type: LinkType): ReadonlyArray<NewLink>;
    getPossibleParents(): PromiseLike<ReadonlyArray<LinkableArtifact>>;
    hasParentLink(
        existing_links: ReadonlyArray<LinkedArtifact>,
        new_links: ReadonlyArray<NewLink>,
    ): boolean;
    getCurrentLinkType(
        has_possible_parents: boolean,
        existing_links: ReadonlyArray<LinkedArtifact>,
        new_links: ReadonlyArray<NewLink>,
    ): LinkType;
    clearFaultNotification(): void;
    isLinkedArtifactInCurrentProject(artifact: LinkedArtifact | NewLink): boolean;
};

const isCreationModeFault = (fault: Fault): boolean =>
    "isCreationMode" in fault && fault.isCreationMode() === true;

export const LinkFieldController = (
    links_retriever: RetrieveAllLinkedArtifacts,
    links_store: RetrieveLinkedArtifactsSync,
    link_type_changer: ChangeLinkType,
    deleted_link_adder: AddLinkMarkedForRemoval,
    deleted_link_remover: DeleteLinkMarkedForRemoval,
    deleted_link_verifier: VerifyLinkIsMarkedForRemoval,
    new_link_adder: AddNewLink,
    new_link_remover: DeleteNewLink,
    new_links_retriever: RetrieveNewLinks,
    new_link_type_changer: ChangeNewLinkType,
    parents_retriever: RetrievePossibleParents,
    event_dispatcher: DispatchEvents<CommonEvents>,
    field: LabeledField,
    current_tracker_identifier: CurrentTrackerIdentifier,
    parent_tracker_identifier: Option<ParentTrackerIdentifier>,
    current_artifact_reference: Option<ArtifactCrossReference>,
    allowed_link_types_collection: LinkTypesCollection,
    current_project_identifier: CurrentProjectIdentifier,
    parent_artifact_identifier: Option<ParentArtifactIdentifier>,
): LinkFieldController => {
    const hasParentLink = (
        existing_links: ReadonlyArray<LinkedArtifact>,
        new_links: ReadonlyArray<NewLink>,
    ): boolean => {
        if (parent_artifact_identifier.isValue()) {
            return true;
        }
        const has_non_removed_parent = existing_links.some((link) =>
            LinkType.isReverseChild(link.link_type),
        );
        const has_new_parent = new_links.some((link) => LinkType.isReverseChild(link.link_type));

        return has_new_parent || has_non_removed_parent;
    };

    return {
        getCurrentArtifactReference: () => current_artifact_reference,

        getLabeledField: () => field,

        getCurrentLinkType: (has_possible_parents, existing_links, new_links): LinkType => {
            const reverse_child_type = allowed_link_types_collection.getReverseChildType();
            const is_tracker_in_a_hierarchy = parent_tracker_identifier.isValue();

            return reverse_child_type &&
                !hasParentLink(existing_links, new_links) &&
                (is_tracker_in_a_hierarchy || has_possible_parents)
                ? reverse_child_type
                : LinkType.buildUntyped();
        },

        getAllowedLinkTypes: () => allowed_link_types_collection,

        getLinkedArtifacts(disable_submit_message): PromiseLike<readonly LinkedArtifact[]> {
            event_dispatcher.dispatch(WillDisableSubmit(disable_submit_message));
            return links_retriever.getLinkedArtifacts().match(
                (artifacts) => {
                    event_dispatcher.dispatch(WillEnableSubmit());
                    return artifacts;
                },
                (fault) => {
                    if (isCreationModeFault(fault)) {
                        event_dispatcher.dispatch(WillEnableSubmit());
                    } else {
                        event_dispatcher.dispatch(WillNotifyFault(LinkRetrievalFault(fault)));
                    }
                    return [];
                },
            );
        },

        canMarkForRemoval(link): boolean {
            return !LinkType.isMirroredMilestone(link.link_type);
        },

        isMarkedForRemoval(link): boolean {
            return deleted_link_verifier.isMarkedForRemoval(link);
        },

        markForRemoval(artifact_identifier): ReadonlyArray<LinkedArtifact> {
            deleted_link_adder.addLinkMarkedForRemoval(artifact_identifier);
            return links_store.getLinkedArtifacts();
        },

        unmarkForRemoval(artifact_identifier): ReadonlyArray<LinkedArtifact> {
            deleted_link_remover.deleteLinkMarkedForRemoval(artifact_identifier);
            return links_store.getLinkedArtifacts();
        },

        canChangeType(link): boolean {
            return !LinkType.isMirroredMilestone(link.link_type);
        },

        changeLinkType(link, type): ReadonlyArray<LinkedArtifact> {
            link_type_changer.changeLinkType(link, type);
            return links_store.getLinkedArtifacts();
        },

        clearFaultNotification(): void {
            event_dispatcher.dispatch(WillClearFaultNotification());
        },

        addNewLink(artifact, type): ReadonlyArray<NewLink> {
            new_link_adder.addNewLink(NewLink.fromLinkableArtifactAndType(artifact, type));
            return new_links_retriever.getNewLinks();
        },

        removeNewLink(link): ReadonlyArray<NewLink> {
            new_link_remover.deleteNewLink(link);
            return new_links_retriever.getNewLinks();
        },

        changeNewLinkType(link, type): ReadonlyArray<NewLink> {
            new_link_type_changer.changeNewLinkType(link, type);
            return new_links_retriever.getNewLinks();
        },

        getPossibleParents(): PromiseLike<ReadonlyArray<LinkableArtifact>> {
            return parents_retriever.getPossibleParents(current_tracker_identifier).match(
                (possible_parents) => possible_parents,
                (fault) => {
                    event_dispatcher.dispatch(WillNotifyFault(fault));
                    return [];
                },
            );
        },

        hasParentLink,

        isLinkedArtifactInCurrentProject(artifact): boolean {
            return artifact.project.id === current_project_identifier.id;
        },
    };
};
