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

import type { ProjectReference } from "@tuleap/core-rest-api-types";
import { ProjectProxy } from "./ProjectProxy";
import type { ArtifactWithStatus } from "./ArtifactWithStatus";

describe(`ProjectProxy`, () => {
    it.each([
        ["with an icon", { id: 169, icon: "🦁", label: "Saturday's Venom" }, "🦁 Saturday's Venom"],
        ["without an icon", { id: 195, icon: "", label: "Hidden Street" }, "Hidden Street"],
    ])(
        `builds from the project %s of an Artifact from the API`,
        (_condition, project: ProjectReference, expected_label) => {
            const result = ProjectProxy.fromAPIArtifact({
                tracker: { project },
            } as ArtifactWithStatus);

            expect(result.id).toBe(project.id);
            expect(result.label).toBe(expected_label);
        }
    );
});
