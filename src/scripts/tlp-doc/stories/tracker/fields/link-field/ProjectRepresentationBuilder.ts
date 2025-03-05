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

import type { TrackerProjectRepresentation } from "@tuleap/plugin-tracker-rest-api-types";

export class ProjectRepresentationBuilder {
    readonly #project_id: number;
    #label: string = "Hollow Moon";
    #icon: string = "";

    private constructor(id: number) {
        this.#project_id = id;
    }

    static aProject(id: number): ProjectRepresentationBuilder {
        return new ProjectRepresentationBuilder(id);
    }

    withLabel(label: string): this {
        this.#label = label;
        return this;
    }

    withIcon(icon: string): this {
        this.#icon = icon;
        return this;
    }

    build(): TrackerProjectRepresentation {
        return {
            id: this.#project_id,
            label: this.#label,
            icon: this.#icon,
            uri: `projects/${this.#project_id}`,
        };
    }
}
