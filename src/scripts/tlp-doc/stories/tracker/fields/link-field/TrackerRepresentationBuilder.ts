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

import type {
    TrackerProjectRepresentation,
    TrackerResponseNoInstance,
} from "@tuleap/plugin-tracker-rest-api-types";
import type { ColorName } from "@tuleap/core-constants";
import { ProjectRepresentationBuilder } from "./ProjectRepresentationBuilder";

export type TrackerRepresentation = Pick<
    TrackerResponseNoInstance,
    "id" | "uri" | "label" | "project" | "color_name" | "item_name"
>;

export class TrackerRepresentationBuilder {
    readonly #tracker_id: number;
    #label: string = "User Stories";
    #short_name: string = "user_stories";
    color: ColorName = "inca-silver";
    #project: TrackerProjectRepresentation = ProjectRepresentationBuilder.aProject(136).build();

    private constructor(id: number) {
        this.#tracker_id = id;
    }

    static aTracker(id: number): TrackerRepresentationBuilder {
        return new TrackerRepresentationBuilder(id);
    }

    withLabel(label: string): this {
        this.#label = label;
        return this;
    }

    withShortName(short_name: string): this {
        this.#short_name = short_name;
        return this;
    }

    withColor(color: ColorName): this {
        this.color = color;
        return this;
    }

    inProject(project: TrackerProjectRepresentation): this {
        this.#project = project;
        return this;
    }

    build(): TrackerRepresentation {
        return {
            id: this.#tracker_id,
            uri: `trackers/${this.#tracker_id}`,
            label: this.#label,
            item_name: this.#short_name,
            color_name: this.color,
            project: this.#project,
        };
    }
}
