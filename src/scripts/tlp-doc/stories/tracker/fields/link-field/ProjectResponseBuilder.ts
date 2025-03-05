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

import type { ProjectResponse } from "@tuleap/core-rest-api-types";

export class ProjectResponseBuilder {
    readonly #project_id: number;
    #label: string = "Sleepy Dog";

    private constructor(id: number) {
        this.#project_id = id;
    }

    static aProject(id: number): ProjectResponseBuilder {
        return new ProjectResponseBuilder(id);
    }

    withLabel(label: string): this {
        this.#label = label;
        return this;
    }

    build(): ProjectResponse {
        return {
            id: this.#project_id,
            label: this.#label,
            shortname: "sleepy-dog",
            uri: `projects/${this.#project_id}`,
        };
    }
}
