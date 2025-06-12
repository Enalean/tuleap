/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { SelectedTrackerRef } from "@/configuration/SelectedTracker";
import { buildSelectedTracker } from "@/configuration/SelectedTracker";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";
import type { Tracker } from "@/configuration/AllowedTrackersCollection";

export const SelectedTrackerStub = {
    withNoTracker(): SelectedTrackerRef {
        return buildSelectedTracker(Option.nothing());
    },
    build(): SelectedTrackerRef {
        return buildSelectedTracker(Option.fromValue(TrackerStub.withTitleAndDescription()));
    },
    withTracker(tracker: Tracker): SelectedTrackerRef {
        return buildSelectedTracker(Option.fromValue(tracker));
    },
};
