/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { put } from "@tuleap/tlp-fetch";
import type { ProgramConfiguration } from "../type";

export function saveConfiguration(configuration: ProgramConfiguration): Promise<Response> {
    return put(
        "/api/v1/projects/" + encodeURIComponent(configuration.program_id) + "/program_plan",
        {
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                program_increment_tracker_id: configuration.program_increment_tracker_id,
                plannable_tracker_ids: configuration.plannable_tracker_ids,
                permissions: configuration.permissions,
                program_increment_label: configuration.program_increment_label,
                program_increment_sub_label: configuration.program_increment_sub_label,
                iteration: configuration.iteration,
            }),
        },
    );
}
