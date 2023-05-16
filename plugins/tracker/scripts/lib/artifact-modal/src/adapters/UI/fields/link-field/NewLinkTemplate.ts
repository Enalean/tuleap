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
import type { NewLink } from "../../../../domain/fields/link-field/NewLink";
import type { LinkField } from "./LinkField";
import { getRemoveLabel } from "../../../../gettext-catalog";
import "./LinkTypeSelectorElement";
import type { TypeChangedEvent } from "./LinkTypeSelectorElement";

type MapOfClasses = Record<string, boolean>;

export const getArtifactStatusBadgeClasses = (
    artifact: LinkedArtifactPresenter | NewLink
): MapOfClasses => {
    const classes: MapOfClasses = {
        "tlp-badge-outline": true,
    };
    if (artifact.status && artifact.status.color) {
        classes[`tlp-badge-${artifact.status.color}`] = true;
    } else {
        classes["tlp-badge-secondary"] = true;
    }
    return classes;
};

export const getCrossRefClasses = (artifact: LinkedArtifactPresenter | NewLink): MapOfClasses => {
    const classes: MapOfClasses = {
        "cross-ref-badge": true,
    };
    classes[`tlp-swatch-${artifact.xref.color}`] = true;
    return classes;
};

export const getNewLinkTemplate = (host: LinkField, link: NewLink): UpdateFunction<LinkField> => {
    const removeNewLink = (): void => {
        host.new_links_presenter = host.controller.removeNewLink(link);
    };

    const onTypeChanged = (host: LinkField, event: CustomEvent<TypeChangedEvent>): void => {
        host.new_links_presenter = host.controller.changeNewLinkType(
            link,
            event.detail.new_link_type
        );
    };

    return html`<div class="link-field-row link-field-new-row" data-test="link-row">
        <span class="link-field-row-type"
            ><tuleap-artifact-modal-link-type-selector
                value="${link.link_type}"
                current_artifact_reference="${host.current_artifact_reference}"
                available_types="${host.allowed_link_types}"
                ontype-changed="${onTypeChanged}"
            ></tuleap-artifact-modal-link-type-selector></span
        ><span class="link-field-row-xref"
            ><a
                href="${link.uri}"
                class="link-field-artifact-link"
                title="${link.title}"
                data-test="link-link"
                ><span class="${getCrossRefClasses(link)}" data-test="link-xref"
                    >${link.xref.ref}</span
                >
                <span class="link-field-artifact-title" data-test="link-title"
                    >${link.title}</span
                ></a
            ></span
        >${link.status &&
        html`<span class="${getArtifactStatusBadgeClasses(link)}" data-test="link-status"
            >${link.status.value}</span
        >`}<button
            class="tlp-button-small tlp-button-danger tlp-button-outline"
            type="button"
            onclick="${removeNewLink}"
            data-test="action-button"
        >
            <i class="fa-regular fa-trash-alt tlp-button-icon" aria-hidden="true"></i
            >${getRemoveLabel()}
        </button>
    </div>`;
};
