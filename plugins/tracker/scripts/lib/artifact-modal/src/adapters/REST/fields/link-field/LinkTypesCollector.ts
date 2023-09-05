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
import { LinkTypesCollection } from "../../../../domain/fields/link-field/LinkTypesCollection";
import type { LinkTypesPair } from "../../../../domain/fields/link-field/LinkTypesPair";

export const LinkTypesCollector = {
    buildFromTypesRepresentations: (
        allowed_types: readonly AllowedLinkTypeRepresentation[],
    ): LinkTypesCollection => {
        const allowed_links_types = allowed_types.reduce<LinkTypesPair[]>((accumulator, type) => {
            accumulator.push({
                forward_type: {
                    shortname: type.shortname,
                    direction: "forward",
                    label: type.forward_label,
                },
                reverse_type: {
                    shortname: type.shortname,
                    direction: "reverse",
                    label: type.reverse_label,
                },
            });
            return accumulator;
        }, []);

        return LinkTypesCollection(allowed_links_types);
    },
};
