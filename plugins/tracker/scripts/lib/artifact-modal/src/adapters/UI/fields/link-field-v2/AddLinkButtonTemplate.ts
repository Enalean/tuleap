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

import { html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import type { LinkField } from "./LinkField";
import { getAddLinkButtonLabel } from "../../../../gettext-catalog";
import type { LinkType } from "../../../../domain/fields/link-field-v2/LinkType";
import { IS_CHILD_LINK_TYPE } from "@tuleap/plugin-tracker-constants";
import { LinkTypeProxy } from "./LinkTypeProxy";
import { REVERSE_DIRECTION } from "../../../../domain/fields/link-field-v2/LinkType";

const getNewCurrentLinkType = (host: LinkField): LinkType => {
    if (
        host.current_link_type.shortname === IS_CHILD_LINK_TYPE &&
        host.current_link_type.direction === REVERSE_DIRECTION &&
        host.allowed_link_types.is_parent_type_disabled
    ) {
        return LinkTypeProxy.buildUntyped();
    }
    return host.current_link_type;
};

const onClickAddNewLink = (host: LinkField): void => {
    if (!host.link_addition_presenter.artifact) {
        return;
    }
    const { links, types } = host.controller.addNewLink(
        host.link_addition_presenter.artifact,
        host.current_link_type
    );
    host.new_links_presenter = links;
    host.allowed_link_types = types;
    host.current_link_type = getNewCurrentLinkType(host);
    host.link_selector.resetSelection();
};

export const getAddLinkButtonTemplate = (host: LinkField): UpdateFunction<LinkField> => {
    return html`
        <button
            type="button"
            class="tlp-button-small tlp-button-primary"
            data-test="add-new-link-button"
            disabled="${host.link_addition_presenter.is_add_button_disabled}"
            onclick="${onClickAddNewLink}"
        >
            ${getAddLinkButtonLabel()}
        </button>
    `;
};
