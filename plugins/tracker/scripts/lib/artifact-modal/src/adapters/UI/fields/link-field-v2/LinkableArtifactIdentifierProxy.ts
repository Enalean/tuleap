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

import type { LinkableArtifactIdentifier } from "../../../../domain/fields/link-field-v2/LinkableArtifactIdentifier";

export const LinkableArtifactIdentifierProxy = {
    fromQueryString: (query_string: string): LinkableArtifactIdentifier | null => {
        if (query_string === "" || isNaN(Number(query_string))) {
            return null;
        }

        const query_number = Number.parseInt(query_string, 10);
        if (Number.isNaN(query_number)) {
            return null;
        }

        return {
            _type: "LinkableArtifactIdentifier",
            id: query_number,
        };
    },
};
