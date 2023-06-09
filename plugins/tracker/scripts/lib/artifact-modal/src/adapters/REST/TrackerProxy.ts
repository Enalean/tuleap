/*
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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

import type { Tracker } from "../../domain/Tracker";
import type { TrackerResponseWithCannotCreateReason } from "@tuleap/plugin-tracker-rest-api-types";

export const MINIMAL_REPRESENTATION = "minimal";
export const SEMANTIC_TO_CHECK = "title";
export const TrackerProxy = {
    fromAPIProject: (tracker: TrackerResponseWithCannotCreateReason): Tracker => ({
        id: tracker.id,
        label: tracker.label,
        color_name: tracker.color_name,
        cannot_create_reason: tracker.cannot_create_reasons[0] ?? "",
    }),
};
