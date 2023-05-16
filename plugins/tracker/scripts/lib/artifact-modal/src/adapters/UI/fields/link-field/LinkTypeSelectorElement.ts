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
import { define, dispatch, html } from "hybrids";
import { UNTYPED_LINK } from "@tuleap/plugin-tracker-constants";
import type { Option } from "@tuleap/option";
import { LinkType } from "../../../../domain/fields/link-field/LinkType";
import type {
    AllowedLinkTypesPresenterContainer,
    CollectionOfAllowedLinksTypesPresenters,
} from "./CollectionOfAllowedLinksTypesPresenters";
import { getDefaultLinkTypeLabel, getNewArtifactLabel } from "../../../../gettext-catalog";
import type { ArtifactCrossReference } from "../../../../domain/ArtifactCrossReference";
import { LinkTypeProxy } from "./LinkTypeProxy";

export type LinkTypeSelectorElement = {
    readonly value: LinkType;
    readonly disabled: boolean;
    readonly current_artifact_reference: Option<ArtifactCrossReference>;
    readonly available_types: CollectionOfAllowedLinksTypesPresenters;
};
type InternalLinkTypeSelector = LinkTypeSelectorElement & {
    content(): HTMLElement;
};
export type HostElement = InternalLinkTypeSelector & HTMLElement;

export type TypeChangedEvent = { readonly new_link_type: LinkType };

const getOption = (
    host: LinkTypeSelectorElement,
    link_type: LinkType
): UpdateFunction<LinkTypeSelectorElement> => {
    const value = link_type.shortname + " " + link_type.direction;
    const is_selected =
        host.value.shortname === link_type.shortname &&
        host.value.direction === link_type.direction;
    const is_disabled =
        LinkType.isReverseChild(link_type) && host.available_types.is_parent_type_disabled;
    return html`<option value="${value}" selected="${is_selected}" disabled="${is_disabled}">
        ${link_type.label}
    </option>`;
};

const getOptions = (
    host: LinkTypeSelectorElement,
    types_container: AllowedLinkTypesPresenterContainer
): UpdateFunction<LinkTypeSelectorElement> => {
    const { forward_type_presenter, reverse_type_presenter } = types_container;
    return html`<option disabled>–</option>
        ${getOption(host, forward_type_presenter)} ${getOption(host, reverse_type_presenter)}`;
};

const onChange = (host: HostElement, event: Event): void => {
    const new_link_type = LinkTypeProxy.fromChangeEvent(event);
    if (!new_link_type) {
        return;
    }
    dispatch(host, "type-changed", { bubbles: true, detail: { new_link_type } });
};

export const LinkTypeSelectorElement = define<InternalLinkTypeSelector>({
    tag: "tuleap-artifact-modal-link-type-selector",
    value: undefined,
    disabled: false,
    current_artifact_reference: undefined,
    available_types: undefined,
    content: (host) => {
        const current_artifact_xref = host.current_artifact_reference.mapOr(
            (reference) => reference.ref,
            getNewArtifactLabel()
        );

        return html`<select
            class="tlp-select tlp-select-small"
            data-test="link-type-select"
            required
            onchange="${onChange}"
            disabled="${host.disabled}"
        >
            <optgroup label="${current_artifact_xref}" data-test="link-type-select-optgroup">
                <option value=" forward" selected="${host.value.shortname === UNTYPED_LINK}">
                    ${getDefaultLinkTypeLabel()}
                </option>
                ${host.available_types.types.map((presenter) => getOptions(host, presenter))}
            </optgroup>
        </select>`;
    },
});
