/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
import type { ParentTrackerIdentifier } from "../../../../domain/fields/link-field/ParentTrackerIdentifier";

type ParentTrackerRepresentation = {
    readonly id: number;
};

export const ParentTrackerIdentifierProxy = {
    fromTrackerModel: (
        tracker: ParentTrackerRepresentation | null,
    ): Option<ParentTrackerIdentifier> => {
        if (tracker === null) {
            return Option.nothing();
        }
        const identifier: ParentTrackerIdentifier = {
            _type: "ParentTrackerIdentifier",
            id: tracker.id,
        };
        return Option.fromValue(identifier);
    },
};
