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

import type { UpdateFunction } from "hybrids";
import { html } from "hybrids";
import type { LinkedArtifactPresenter } from "./LinkedArtifactPresenter";
import {
    getOpenArtifactToUnlinkTextEnd,
    getOpenArtifactToUnlinkTextStart,
    getRestoreLabel,
    getUnlinkLabel,
} from "../../../../gettext-catalog";
import { FORWARD_DIRECTION } from "../../../../domain/fields/link-field-v2/LinkType";
import type { LinkField } from "./LinkField";
import type { LinkType } from "../../../../domain/fields/link-field-v2/LinkType";
import {
    getArtifactLinkTypeLabel,
    getArtifactStatusBadgeClasses,
    getCrossRefClasses,
} from "./NewLinkTemplate";

type MapOfClasses = Record<string, boolean>;

export const LINKED_ARTIFACT_POPOVER_CLASS = "link-field-linked-artifact-popover";

const getStatusBadgeClassesWithRemoval = (artifact: LinkedArtifactPresenter): MapOfClasses => {
    const classes = getArtifactStatusBadgeClasses(artifact);
    classes["link-field-link-to-remove"] = artifact.is_marked_for_removal;
    return classes;
};

const getArtifactTableRowClasses = (artifact: LinkedArtifactPresenter): MapOfClasses => ({
    "link-field-table-row": true,
    "link-field-table-row-muted": artifact.status !== "" && !artifact.is_open,
});

const getRemoveClass = (artifact: LinkedArtifactPresenter): string =>
    artifact.is_marked_for_removal ? "link-field-link-to-remove" : "";

const getCrossRefClassesWithRemoval = (artifact: LinkedArtifactPresenter): MapOfClasses => {
    const classes = getCrossRefClasses(artifact);
    classes["link-field-link-to-remove"] = artifact.is_marked_for_removal;
    return classes;
};

const canLinkBeDeleted = (link_type: LinkType): boolean =>
    link_type.direction === FORWARD_DIRECTION;

const getUnlinkableArtifactPopover = (
    trigger_id: string,
    artifact: LinkedArtifactPresenter
): UpdateFunction<LinkField> => {
    const content_id = `${trigger_id}-content`;
    return html`
        <section
            id="${content_id}"
            class="tlp-popover link-field-popover"
            data-test="linked-artifact-popover"
        >
            <div class="tlp-popover-arrow"></div>
            <div class="tlp-popover-body link-field-linked-artifact-popover-body">
                ${getOpenArtifactToUnlinkTextStart()}
                <span
                    class="cross-ref-badge link-field-linked-artifact-popover-badge tlp-swatch-${artifact
                        .xref.color}"
                >
                    ${artifact.xref.ref}
                </span>
                ${getOpenArtifactToUnlinkTextEnd()}
            </div>
        </section>
    `;
};

export const getActionButton = (artifact: LinkedArtifactPresenter): UpdateFunction<LinkField> => {
    const button_classes = [
        "tlp-table-cell-actions-button",
        "tlp-button-small",
        "tlp-button-danger",
        "tlp-button-outline",
    ];

    if (!canLinkBeDeleted(artifact.link_type)) {
        button_classes.push(LINKED_ARTIFACT_POPOVER_CLASS);
        const trigger_id = LINKED_ARTIFACT_POPOVER_CLASS + `-${artifact.identifier.id}`;
        return html`
            <a
                id="${trigger_id}"
                class="${button_classes}"
                type="button"
                href="${artifact.uri}"
                data-test="action-button"
                target="_blank"
                rel="noreferer"
            >
                <i class="fas fa-unlink tlp-button-icon" aria-hidden="true"></i>
                ${getUnlinkLabel()}
                <i class="fas fa-external-link-alt tlp-button-icon-right" aria-hidden="true"></i>
            </a>
            ${getUnlinkableArtifactPopover(trigger_id, artifact)}
        `;
    }

    if (!artifact.is_marked_for_removal) {
        const markForRemoval = (host: LinkField): void => {
            host.linked_artifacts_presenter = host.controller.markForRemoval(artifact.identifier);
        };
        return html`
            <button
                class="${button_classes}"
                type="button"
                onclick="${markForRemoval}"
                data-test="action-button"
            >
                <i class="fas fa-unlink tlp-button-icon" aria-hidden="true"></i>
                ${getUnlinkLabel()}
            </button>
        `;
    }

    const cancelRemoval = (host: LinkField): void => {
        host.linked_artifacts_presenter = host.controller.unmarkForRemoval(artifact.identifier);
    };
    return html`
        <button
            class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline"
            type="button"
            onclick="${cancelRemoval}"
            data-test="action-button"
        >
            <i class="fas fa-undo-alt tlp-button-icon" aria-hidden="true"></i>
            ${getRestoreLabel()}
        </button>
    `;
};

export const getLinkedArtifactTemplate = (
    artifact: LinkedArtifactPresenter
): UpdateFunction<LinkField> => html`
    <tr class="${getArtifactTableRowClasses(artifact)}" data-test="artifact-row">
        <td
            class="link-field-table-cell-type ${getRemoveClass(artifact)}"
            data-test="artifact-link-type"
        >
            ${getArtifactLinkTypeLabel(artifact)}
        </td>
        <td class="link-field-table-cell-xref ${getRemoveClass(artifact)}">
            <a href="${artifact.uri}" class="link-field-artifact-link" data-test="artifact-link">
                <span class="${getCrossRefClassesWithRemoval(artifact)}" data-test="artifact-xref">
                    ${artifact.xref.ref}
                </span>
                <span
                    class="link-field-artifact-title ${getRemoveClass(artifact)}"
                    data-test="artifact-title"
                >
                    ${artifact.title}
                </span>
            </a>
        </td>
        <td class="link-field-table-cell-status">
            ${artifact.status &&
            html`
                <span
                    class="${getStatusBadgeClassesWithRemoval(artifact)}"
                    data-test="artifact-status"
                >
                    ${artifact.status}
                </span>
            `}
        </td>
        <td class="link-field-table-cell-action">${getActionButton(artifact)}</td>
    </tr>
`;
