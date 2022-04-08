/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
import { IS_CHILD_LINK_TYPE } from "@tuleap/plugin-tracker-constants";
import type { LinkField } from "./LinkField";
import type { AllowedLinkType } from "../../../../domain/fields/link-field-v2/AllowedLinkType";
import { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";
import { getDefaultLinkTypeLabel, getNewArtifactLabel } from "../../../../gettext-catalog";
import type { AllowedLinkTypesPresenterContainer } from "./CollectionOfAllowedLinksTypesPresenters";
import type { ArtifactCrossReference } from "../../../../domain/ArtifactCrossReference";

const getOptions = (
    types_container: AllowedLinkTypesPresenterContainer
): UpdateFunction<LinkField> => {
    const { forward_type_presenter } = types_container;

    const forward_link_value =
        forward_type_presenter.shortname + " " + forward_type_presenter.direction;

    return html`
        <option disabled>–</option>
        <option value="${forward_link_value}" data-test="link-type-select-option">
            ${forward_type_presenter.label}
        </option>
    `;
};

export const getTypeSelectorTemplate = (
    allowed_links_types: AllowedLinkType[],
    artifact_cross_reference: ArtifactCrossReference | null
): UpdateFunction<LinkField> => {
    const types_presenters =
        CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
            allowed_links_types.filter((type) => type.shortname === IS_CHILD_LINK_TYPE)
        );
    const current_artifact_xref =
        artifact_cross_reference === null ? getNewArtifactLabel() : artifact_cross_reference.ref;

    return html`
        <select class="tlp-select tlp-select-small" data-test="link-type-select" required>
            <optgroup label="${current_artifact_xref}" data-test="link-type-select-optgroup">
                <option value="" data-test="link-type-select-option" selected>
                    ${getDefaultLinkTypeLabel()}
                </option>
                ${types_presenters.map((presenter) => getOptions(presenter))}
            </optgroup>
        </select>
    `;
};
