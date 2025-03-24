/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import type {
    ArtifactRepresentation,
    Selectable,
    SelectableQueryContentRepresentation,
} from "../../src/api/cross-tracker-rest-api-types";
import { ARTIFACT_SELECTABLE_TYPE } from "../../src/api/cross-tracker-rest-api-types";
import { ARTIFACT_COLUMN_NAME } from "../../src/domain/ColumnName";

export const SelectableQueryContentRepresentationStub = {
    build: (
        selected: ReadonlyArray<Selectable>,
        artifacts: ReadonlyArray<ArtifactRepresentation>,
    ): SelectableQueryContentRepresentation => ({
        selected: [{ type: ARTIFACT_SELECTABLE_TYPE, name: ARTIFACT_COLUMN_NAME }, ...selected],
        artifacts,
    }),
};
