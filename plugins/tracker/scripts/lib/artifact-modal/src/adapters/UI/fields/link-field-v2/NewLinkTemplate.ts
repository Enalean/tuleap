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
import type { NewLink } from "../../../../domain/fields/link-field-v2/NewLink";
import type { LinkField } from "./LinkField";
import { getDefaultLinkTypeLabel, getRemoveLabel } from "../../../../gettext-catalog";
import { UNTYPED_LINK } from "@tuleap/plugin-tracker-constants";

type MapOfClasses = Record<string, boolean>;

export const getArtifactStatusBadgeClasses = (
    artifact: LinkedArtifactPresenter | NewLink
): MapOfClasses => ({
    "tlp-badge-outline": true,
    "tlp-badge-success": artifact.is_open,
    "tlp-badge-secondary": !artifact.is_open,
});

export const getCrossRefClasses = (artifact: LinkedArtifactPresenter | NewLink): MapOfClasses => {
    const badge_color = `tlp-swatch-${artifact.xref.color}`;
    const classes: MapOfClasses = {
        "cross-ref-badge": true,
        "link-field-xref-badge": true,
    };
    classes[badge_color] = true;
    return classes;
};

export const getArtifactLinkTypeLabel = (artifact: LinkedArtifactPresenter | NewLink): string => {
    if (artifact.link_type.shortname !== UNTYPED_LINK) {
        return artifact.link_type.label;
    }

    return getDefaultLinkTypeLabel();
};

export const getNewLinkTemplate = (link: NewLink): UpdateFunction<LinkField> => {
    const removeNewLink = (host: LinkField): void => {
        host.new_links_presenter = host.controller.removeNewLink(link);
    };

    return html`
        <tr class="link-field-table-row link-field-table-row-new" data-test="link-row">
            <td class="link-field-table-cell-type" data-test="link-type">
                ${getArtifactLinkTypeLabel(link)}
            </td>
            <td class="link-field-table-cell-xref">
                <a
                    href="${link.uri}"
                    class="link-field-artifact-link"
                    title="${link.title}"
                    data-test="link-link"
                >
                    <span class="${getCrossRefClasses(link)}" data-test="link-xref">
                        ${link.xref.ref}
                    </span>
                    <span class="link-field-artifact-title" data-test="link-title">
                        ${link.title}
                    </span>
                </a>
            </td>
            <td class="link-field-table-cell-status">
                ${link.status &&
                html`
                    <span class="${getArtifactStatusBadgeClasses(link)}" data-test="link-status">
                        ${link.status}
                    </span>
                `}
            </td>
            <td class="link-field-table-cell-action">
                <button
                    class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline"
                    type="button"
                    onclick="${removeNewLink}"
                    data-test="action-button"
                >
                    <i class="far fa-trash-alt tlp-button-icon" aria-hidden="true"></i>
                    ${getRemoveLabel()}
                </button>
            </td>
        </tr>
    `;
};
