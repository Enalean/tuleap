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
import type { LinkField } from "./LinkField";
import { getDefaultLinkTypeLabel, getNewArtifactLabel } from "../../../../gettext-catalog";
import type { AllowedLinkTypesPresenterContainer } from "./CollectionOfAllowedLinksTypesPresenters";
import type { LinkFieldPresenter } from "./LinkFieldPresenter";

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
    presenter: LinkFieldPresenter
): UpdateFunction<LinkField> => {
    const current_artifact_xref =
        presenter.current_artifact_reference === null
            ? getNewArtifactLabel()
            : presenter.current_artifact_reference.ref;

    return html`
        <select class="tlp-select tlp-select-small" data-test="link-type-select" required>
            <optgroup label="${current_artifact_xref}" data-test="link-type-select-optgroup">
                <option value="" data-test="link-type-select-option" selected>
                    ${getDefaultLinkTypeLabel()}
                </option>
                ${presenter.allowed_types.map((presenter) => getOptions(presenter))}
            </optgroup>
        </select>
    `;
};
