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

import type { AllowedLinkTypeRepresentation } from "@tuleap/plugin-tracker-rest-api-types/src";
import { IS_CHILD_LINK_TYPE } from "@tuleap/plugin-tracker-constants";
import { LinkType } from "../../../../domain/fields/link-field/LinkType";
import type { CollectAllowedLinksTypes } from "../../../../domain/fields/link-field/CollectAllowedLinksTypes";

export const AllowedLinksTypesCollection = {
    buildFromTypesRepresentations: (
        allowed_types: readonly AllowedLinkTypeRepresentation[]
    ): CollectAllowedLinksTypes => {
        const only_is_child_type = allowed_types.filter(
            (type) => type.shortname === IS_CHILD_LINK_TYPE
        );

        const allowed_links_types = only_is_child_type.reduce<LinkType[][]>((accumulator, type) => {
            accumulator.push([
                { shortname: type.shortname, direction: "forward", label: type.forward_label },
                { shortname: type.shortname, direction: "reverse", label: type.reverse_label },
            ]);
            return accumulator;
        }, []);

        return {
            getAll: () => allowed_links_types,
            getReverseChildType: () =>
                allowed_links_types.flat().find((type) => LinkType.isReverseChild(type)),
        };
    },
};
