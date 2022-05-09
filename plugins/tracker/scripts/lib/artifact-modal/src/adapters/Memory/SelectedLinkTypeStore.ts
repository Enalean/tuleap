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

import type { SetSelectedLinkType } from "../../domain/fields/link-field-v2/SetSelectedLinkType";
import type { RetrieveSelectedLinkType } from "../../domain/fields/link-field-v2/RetrieveSelectedLinkType";
import { LinkType } from "../../domain/fields/link-field-v2/LinkType";

export type SelectedLinkTypeStoreType = SetSelectedLinkType & RetrieveSelectedLinkType;

export const SelectedLinkTypeStore = (): SelectedLinkTypeStoreType => {
    let selected_link_type = LinkType.buildUntyped();
    return {
        getSelectedLinkType: () => selected_link_type,

        setSelectedLinkType: (type) => (selected_link_type = type),
    };
};
