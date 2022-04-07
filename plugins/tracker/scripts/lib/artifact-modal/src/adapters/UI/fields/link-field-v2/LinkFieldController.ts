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

import { LinkFieldPresenter } from "./LinkFieldPresenter";
import type { RetrieveAllLinkedArtifacts } from "../../../../domain/fields/link-field-v2/RetrieveAllLinkedArtifacts";
import type { CurrentArtifactIdentifier } from "../../../../domain/CurrentArtifactIdentifier";
import type { Fault } from "@tuleap/fault";
import { isFault } from "@tuleap/fault";
import type { LinkedArtifactIdentifier } from "../../../../domain/fields/link-field-v2/LinkedArtifact";
import { LinkedArtifactPresenter } from "./LinkedArtifactPresenter";
import type { AddLinkMarkedForRemoval } from "../../../../domain/fields/link-field-v2/AddLinkMarkedForRemoval";
import type { DeleteLinkMarkedForRemoval } from "../../../../domain/fields/link-field-v2/DeleteLinkMarkedForRemoval";
import type { VerifyLinkIsMarkedForRemoval } from "../../../../domain/fields/link-field-v2/VerifyLinkIsMarkedForRemoval";
import type { RetrieveLinkedArtifactsSync } from "../../../../domain/fields/link-field-v2/RetrieveLinkedArtifactsSync";
import type { NotifyFault } from "../../../../domain/NotifyFault";
import { LinkRetrievalFault } from "../../../../domain/fields/link-field-v2/LinkRetrievalFault";

export interface LinkFieldControllerType {
    displayLinkedArtifacts(): Promise<LinkFieldPresenter>;
    markForRemoval(artifact_id: LinkedArtifactIdentifier): LinkFieldPresenter;
    unmarkForRemoval(artifact_id: LinkedArtifactIdentifier): LinkFieldPresenter;
}

const isCreationModeFault = (fault: Fault): boolean => {
    return typeof fault.isCreationMode === "function" && fault.isCreationMode();
};

const buildPresenter = (
    links_store: RetrieveLinkedArtifactsSync,
    deleted_link_verifier: VerifyLinkIsMarkedForRemoval
): LinkFieldPresenter => {
    const presenters = links_store
        .getLinkedArtifacts()
        .map((linked_artifact) =>
            LinkedArtifactPresenter.fromLinkedArtifact(
                linked_artifact,
                deleted_link_verifier.isMarkedForRemoval(linked_artifact)
            )
        );
    return LinkFieldPresenter.fromArtifacts(presenters);
};

export const LinkFieldController = (
    links_retriever: RetrieveAllLinkedArtifacts,
    links_store: RetrieveLinkedArtifactsSync,
    deleted_link_adder: AddLinkMarkedForRemoval,
    deleted_link_remover: DeleteLinkMarkedForRemoval,
    deleted_link_verifier: VerifyLinkIsMarkedForRemoval,
    fault_notifier: NotifyFault,
    current_artifact_identifier: CurrentArtifactIdentifier | null
): LinkFieldControllerType => ({
    displayLinkedArtifacts: (): Promise<LinkFieldPresenter> => {
        return links_retriever.getLinkedArtifacts(current_artifact_identifier).then((result) => {
            if (!isFault(result)) {
                const presenters = result.map((linked_artifact) =>
                    LinkedArtifactPresenter.fromLinkedArtifact(linked_artifact, false)
                );
                return LinkFieldPresenter.fromArtifacts(presenters);
            }
            if (!isCreationModeFault(result)) {
                fault_notifier.onFault(LinkRetrievalFault(result));
            }
            return LinkFieldPresenter.forFault();
        });
    },

    markForRemoval(artifact_identifier: LinkedArtifactIdentifier): LinkFieldPresenter {
        deleted_link_adder.addLinkMarkedForRemoval(artifact_identifier);
        return buildPresenter(links_store, deleted_link_verifier);
    },

    unmarkForRemoval(artifact_identifier: LinkedArtifactIdentifier): LinkFieldPresenter {
        deleted_link_remover.deleteLinkMarkedForRemoval(artifact_identifier);
        return buildPresenter(links_store, deleted_link_verifier);
    },
});
