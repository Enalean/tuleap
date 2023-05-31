/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { CurrentArtifactIdentifier } from "../../../../domain/CurrentArtifactIdentifier";
import type { SearchResultEntry, UserHistoryEntry } from "@tuleap/core-rest-api-types";
import { ARTIFACT_TYPE } from "@tuleap/plugin-tracker-constants";
import type { Option } from "@tuleap/option";

export const LinkableArtifactRESTFilter = {
    filterArtifact: (
        entry: SearchResultEntry | UserHistoryEntry,
        current_artifact_option: Option<CurrentArtifactIdentifier>
    ): boolean => {
        const is_artifact_entry = entry.type === ARTIFACT_TYPE;
        return current_artifact_option.mapOr(
            (current_artifact_identifier) =>
                is_artifact_entry && entry.per_type_id !== current_artifact_identifier.id,
            is_artifact_entry
        );
    },
};
