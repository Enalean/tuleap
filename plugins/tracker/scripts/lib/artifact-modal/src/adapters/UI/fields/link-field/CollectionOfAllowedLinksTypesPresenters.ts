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

import { LinkType } from "../../../../domain/fields/link-field/LinkType";
import type { LinkTypesCollection } from "../../../../domain/fields/link-field/LinkTypesCollection";
import { getChildTypeLabel, getParentTypeLabel } from "../../../../gettext-catalog";

export type CollectionOfAllowedLinksTypesPresenters = {
    readonly is_parent_type_disabled: boolean;
    readonly types: ReadonlyArray<AllowedLinkTypesPresenterContainer>;
};

export interface AllowedLinkTypesPresenterContainer {
    readonly forward_type_presenter: LinkType;
    readonly reverse_type_presenter: LinkType;
}

export const CollectionOfAllowedLinksTypesPresenters = {
    fromCollectionOfAllowedLinkType: (
        has_parent_link: boolean,
        allowed_types: LinkTypesCollection,
    ): CollectionOfAllowedLinksTypesPresenters => ({
        is_parent_type_disabled: has_parent_link,
        types: allowed_types.getAll().map((pair) => {
            if (LinkType.isReverseChild(pair.reverse_type)) {
                return {
                    forward_type_presenter: { ...pair.forward_type, label: getParentTypeLabel() },
                    reverse_type_presenter: { ...pair.reverse_type, label: getChildTypeLabel() },
                };
            }
            return {
                forward_type_presenter: pair.forward_type,
                reverse_type_presenter: pair.reverse_type,
            };
        }),
    }),

    buildEmpty: (): CollectionOfAllowedLinksTypesPresenters => ({
        is_parent_type_disabled: false,
        types: [],
    }),
};
