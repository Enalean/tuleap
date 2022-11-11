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

import type { LinkType } from "../../../../domain/fields/link-field/LinkType";
import type { VerifyHasParentLink } from "../../../../domain/fields/link-field/VerifyHasParentLink";
import type { CollectAllowedLinksTypes } from "../../../../domain/fields/link-field/CollectAllowedLinksTypes";
import {
    FORWARD_DIRECTION,
    REVERSE_DIRECTION,
} from "../../../../domain/fields/link-field/LinkType";

export type CollectionOfAllowedLinksTypesPresenters = {
    readonly is_parent_type_disabled: boolean;
    readonly types: ReadonlyArray<AllowedLinkTypesPresenterContainer>;
};

export interface AllowedLinkTypesPresenterContainer {
    readonly forward_type_presenter: LinkType;
    readonly reverse_type_presenter: LinkType;
}

const getTypeWithDirection = (two_ways_types: LinkType[], direction: string): LinkType => {
    const type = two_ways_types.find((type) => type.direction === direction);
    if (!type) {
        throw new Error(`Cannot find type with direction ${direction}`);
    }

    return type;
};

export const CollectionOfAllowedLinksTypesPresenters = {
    fromCollectionOfAllowedLinkType: (
        parent_verifier: VerifyHasParentLink,
        allowed_types: CollectAllowedLinksTypes
    ): CollectionOfAllowedLinksTypesPresenters => ({
        is_parent_type_disabled: parent_verifier.hasParentLink(),
        types: allowed_types.getAll().map((allowed_type) => ({
            forward_type_presenter: getTypeWithDirection(allowed_type, FORWARD_DIRECTION),
            reverse_type_presenter: getTypeWithDirection(allowed_type, REVERSE_DIRECTION),
        })),
    }),

    buildEmpty: (): CollectionOfAllowedLinksTypesPresenters => ({
        is_parent_type_disabled: false,
        types: [],
    }),
};
