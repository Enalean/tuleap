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

import { Option } from "@tuleap/option";
import type { LinkableNumber } from "../../../../../domain/fields/link-field/LinkableNumber";
import type { CurrentArtifactIdentifier } from "../../../../../domain/CurrentArtifactIdentifier";

export const LinkableNumberProxy = {
    fromQueryString: (
        query_string: string,
        current_artifact_option: Option<CurrentArtifactIdentifier>,
    ): Option<LinkableNumber> => {
        if (query_string === "") {
            return Option.nothing();
        }
        const query_number = Number.parseInt(query_string, 10);
        const is_invalid_number =
            Number.isNaN(query_number) ||
            Number(query_string) !== query_number ||
            Math.abs(query_number) !== query_number;
        const is_invalid = current_artifact_option.mapOr(
            (current_artifact_identifier) =>
                is_invalid_number || query_number === current_artifact_identifier.id,
            is_invalid_number,
        );

        if (is_invalid) {
            return Option.nothing();
        }

        const number: LinkableNumber = { _type: "LinkableNumber", id: query_number };
        return Option.fromValue(number);
    },
};
