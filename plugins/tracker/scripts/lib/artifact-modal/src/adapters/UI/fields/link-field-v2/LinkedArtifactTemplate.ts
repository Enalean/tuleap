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
import { getUndoRemovalLabel, getMarkForRemovalLabel } from "../../../../gettext-catalog";
import type { LinkField } from "./LinkField";

type MapOfClasses = Record<string, boolean>;

const getArtifactsStatusBadgeClasses = (artifact: LinkedArtifactPresenter): MapOfClasses => ({
    "tlp-badge-outline": true,
    "tlp-badge-success": artifact.is_open,
    "tlp-badge-secondary": !artifact.is_open,
    "link-field-link-to-remove": artifact.is_marked_for_removal,
});

const getArtifactTableRowClasses = (artifact: LinkedArtifactPresenter): MapOfClasses => ({
    "link-field-table-row": true,
    "link-field-table-row-muted": artifact.status !== "" && !artifact.is_open,
});

const getRemoveClass = (artifact: LinkedArtifactPresenter): string =>
    artifact.is_marked_for_removal ? "link-field-link-to-remove" : "";

const getCrossRefClasses = (artifact: LinkedArtifactPresenter): MapOfClasses => {
    const badge_color = `cross-ref-badge-${artifact.tracker.color_name}`;
    const classes: MapOfClasses = {
        "cross-ref-badge": true,
        "link-field-xref-badge": true,
        "link-field-link-to-remove": artifact.is_marked_for_removal,
    };
    classes[badge_color] = true;
    return classes;
};

export const getActionButton = (artifact: LinkedArtifactPresenter): UpdateFunction<LinkField> => {
    if (!artifact.is_marked_for_removal) {
        const markForRemoval = (host: LinkField): void => {
            host.presenter = host.controller.markForRemoval(artifact.identifier);
        };
        return html`
            <button
                class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline"
                type="button"
                onclick="${markForRemoval}"
                data-test="action-button"
            >
                <i class="far fa-trash-alt tlp-button-icon" aria-hidden="true"></i>
                ${getMarkForRemovalLabel()}
            </button>
        `;
    }

    const cancelRemoval = (host: LinkField): void => {
        host.presenter = host.controller.unmarkForRemoval(artifact.identifier);
    };
    return html`
        <button
            class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline"
            type="button"
            onclick="${cancelRemoval}"
            data-test="action-button"
        >
            <i class="fas fa-undo-alt tlp-button-icon" aria-hidden="true"></i>
            ${getUndoRemovalLabel()}
        </button>
    `;
};

export const getLinkedArtifactTemplate = (
    artifact: LinkedArtifactPresenter
): UpdateFunction<LinkField> => html`
    <tr class="${getArtifactTableRowClasses(artifact)}" data-test="artifact-row">
        <td class="link-field-table-cell-type ${getRemoveClass(artifact)}">
            ${artifact.link_type.label}
        </td>
        <td class="link-field-table-cell-xref ${getRemoveClass(artifact)}">
            <a href="${artifact.uri}" class="link-field-artifact-link" data-test="artifact-link">
                <span class="${getCrossRefClasses(artifact)}" data-test="artifact-xref">
                    ${artifact.xref}
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
                    class="${getArtifactsStatusBadgeClasses(artifact)}"
                    data-test="artifact-status"
                >
                    ${artifact.status}
                </span>
            `}
        </td>
        <td class="link-field-table-cell-action">${getActionButton(artifact)}</td>
    </tr>
`;
