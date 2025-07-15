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

import type { ReadonlyFieldLinkedArtifact } from "@/sections/readonly-fields/ReadonlyFields";

export const LinkedArtifactStub = {
    build,

    override: (overrides: Partial<ReadonlyFieldLinkedArtifact>): ReadonlyFieldLinkedArtifact => {
        return { ...build(), ...overrides };
    },
};

function build(): ReadonlyFieldLinkedArtifact {
    const artifact_id = 447;
    return {
        link_label: "is Linked to",
        tracker_shortname: "bugs",
        tracker_color: "fiesta-red",
        project: { id: 166, label: "Acetized meniscate", icon: "🛰️" },
        artifact_id,
        title: "Ottweilian bepierce",
        html_uri: `/plugins/tracker/?aid=${artifact_id}`,
        status: {
            label: "On going",
            is_open: true,
            color: "",
        },
    };
}
