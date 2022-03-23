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

import {
    FORWARD_DIRECTION,
    REVERSE_DIRECTION,
} from "../../../../domain/fields/link-field-v2/LinkedArtifact";
import type { AllowedLinkType } from "../../../../domain/fields/link-field-v2/AllowedLinkType";

export type CollectionOfAllowedLinksTypesPresenters =
    ReadonlyArray<AllowedLinkTypesPresenterContainer>;

export interface AllowedLinkTypesPresenterContainer {
    forward_type_presenter: AllowedLinkTypePresenter;
    reverse_type_presenter: AllowedLinkTypePresenter;
}

export interface AllowedLinkTypePresenter {
    label: string;
    shortname: string;
    direction: string;
}

export const CollectionOfAllowedLinksTypesPresenters = {
    fromCollectionOfAllowedLinkType: (
        allowed_links_types: AllowedLinkType[]
    ): CollectionOfAllowedLinksTypesPresenters => {
        return allowed_links_types.map((allowed_type) => {
            return {
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
            };
        });
    },
};
