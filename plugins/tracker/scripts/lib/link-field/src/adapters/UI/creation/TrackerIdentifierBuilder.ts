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
import { TrackerIdentifier } from "../../../domain/TrackerIdentifier";

export interface TrackerIdentifierBuilder {
    buildFromChangeEvent(event: Event): Option<TrackerIdentifier>;
}

export const TrackerIdentifierBuilder = (): TrackerIdentifierBuilder => ({
    buildFromChangeEvent: (event: Event): Option<TrackerIdentifier> => {
        if (!(event.target instanceof HTMLSelectElement)) {
            return Option.nothing();
        }
        const tracker_id = Number.parseInt(event.target.value, 10);
        if (Number.isNaN(tracker_id)) {
            return Option.nothing();
        }
        return Option.fromValue(TrackerIdentifier.fromId(tracker_id));
    },
});
