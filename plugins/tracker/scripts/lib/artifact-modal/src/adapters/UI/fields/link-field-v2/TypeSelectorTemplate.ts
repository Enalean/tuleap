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
import { UNTYPED_LINK, IS_CHILD_LINK_TYPE } from "@tuleap/plugin-tracker-constants";
import { LinkTypeProxy } from "./LinkTypeProxy";
import type { AllowedLinkTypePresenter } from "./CollectionOfAllowedLinksTypesPresenters";
import { REVERSE_DIRECTION } from "../../../../domain/fields/link-field-v2/LinkType";

const onChange = (host: LinkField, event: Event): void => {
    const new_link_type = LinkTypeProxy.fromChangeEvent(event);
    if (!new_link_type) {
        return;
    }
    host.current_link_type = new_link_type;
};

const getOption = (
    host: LinkField,
    presenter: AllowedLinkTypePresenter
): UpdateFunction<LinkField> => {
    const value = presenter.shortname + " " + presenter.direction;
    const is_selected =
        host.current_link_type.shortname === presenter.shortname &&
        host.current_link_type.direction === presenter.direction;
    const is_disabled =
        presenter.shortname === IS_CHILD_LINK_TYPE &&
        presenter.direction === REVERSE_DIRECTION &&
        host.allowed_link_types.is_parent_type_disabled;
    return html`
        <option value="${value}" selected="${is_selected}" disabled="${is_disabled}">
            ${presenter.label}
        </option>
    `;
};

const getOptions = (
    host: LinkField,
    types_container: AllowedLinkTypesPresenterContainer
): UpdateFunction<LinkField> => {
    const { forward_type_presenter, reverse_type_presenter } = types_container;
    return html`
        <option disabled>–</option>
        ${getOption(host, forward_type_presenter)} ${getOption(host, reverse_type_presenter)}
    `;
};

export const getTypeSelectorTemplate = (host: LinkField): UpdateFunction<LinkField> => {
    const current_artifact_xref =
        host.field_presenter.current_artifact_reference === null
            ? getNewArtifactLabel()
            : host.field_presenter.current_artifact_reference.ref;

    return html`
        <select
            class="tlp-select tlp-select-small"
            data-test="link-type-select"
            required
            onchange="${onChange}"
        >
            <optgroup label="${current_artifact_xref}" data-test="link-type-select-optgroup">
                <option
                    value=" forward"
                    selected="${host.current_link_type.shortname === UNTYPED_LINK}"
                >
                    ${getDefaultLinkTypeLabel()}
                </option>
                ${host.allowed_link_types.types.map((presenter) => getOptions(host, presenter))}
            </optgroup>
        </select>
    `;
};
