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

import type { AllowedLinkTypeRepresentation } from "@tuleap/plugin-tracker-rest-api-types";
import {
    FORWARD_DIRECTION,
    REVERSE_DIRECTION,
} from "../../../../domain/fields/link-field-v2/LinkType";
import type { VerifyHasParentLink } from "../../../../domain/fields/link-field-v2/VerifyHasParentLink";

export type CollectionOfAllowedLinksTypesPresenters = {
    readonly is_parent_type_disabled: boolean;
    readonly types: ReadonlyArray<AllowedLinkTypesPresenterContainer>;
};

export interface AllowedLinkTypesPresenterContainer {
    readonly forward_type_presenter: AllowedLinkTypePresenter;
    readonly reverse_type_presenter: AllowedLinkTypePresenter;
}

export interface AllowedLinkTypePresenter {
    readonly label: string;
    readonly shortname: string;
    readonly direction: "forward" | "reverse";
}

export const CollectionOfAllowedLinksTypesPresenters = {
    fromCollectionOfAllowedLinkType: (
        parent_verifier: VerifyHasParentLink,
        types: readonly AllowedLinkTypeRepresentation[]
    ): CollectionOfAllowedLinksTypesPresenters => ({
        is_parent_type_disabled: parent_verifier.hasParentLink(),
        types: types.map((allowed_type) => ({
            forward_type_presenter: {
                label: allowed_type.forward_label,
                shortname: allowed_type.shortname,
                direction: FORWARD_DIRECTION,
            },
            reverse_type_presenter: {
                label: allowed_type.reverse_label,
                shortname: allowed_type.shortname,
                direction: REVERSE_DIRECTION,
            },
        })),
    }),

    buildEmpty: (): CollectionOfAllowedLinksTypesPresenters => ({
        is_parent_type_disabled: false,
        types: [],
    }),
};
