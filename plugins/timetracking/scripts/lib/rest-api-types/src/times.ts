/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

import type { ProjectResponse } from "@tuleap/core-rest-api-types";
import type { ArtifactResponseNoInstance } from "@tuleap/plugin-tracker-rest-api-types";
import type { ColorName } from "@tuleap/core-constants";

export type PersonalTime = {
    readonly artifact: Artifact;
    readonly project: ProjectResponse;
    readonly id: number;
    readonly date: string;
    readonly minutes: number;
    readonly step: string;
};

export type Artifact = Pick<
    ArtifactResponseNoInstance,
    "id" | "xref" | "html_url" | "title" | "uri"
> & {
    readonly badge_color: ColorName;
    readonly submission_date: string;
};
