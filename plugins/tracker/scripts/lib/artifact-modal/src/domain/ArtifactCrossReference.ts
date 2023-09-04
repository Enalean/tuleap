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

import type { ColorName } from "@tuleap/plugin-tracker-constants";
import type { Option } from "@tuleap/option";
import type { CurrentArtifactIdentifier } from "./CurrentArtifactIdentifier";
import type { TrackerShortname } from "./TrackerShortname";

export type ArtifactCrossReference = {
    readonly ref: string;
    readonly color: ColorName;
};

export const ArtifactCrossReference = {
    fromCurrentArtifact: (
        current_artifact_option: Option<CurrentArtifactIdentifier>,
        tracker_shortname: TrackerShortname,
        tracker_color_name: ColorName,
    ): Option<ArtifactCrossReference> =>
        current_artifact_option.map((current_artifact_identifier) => {
            return {
                ref: `${tracker_shortname.shortname} #${current_artifact_identifier.id}`,
                color: tracker_color_name,
            };
        }),
};
