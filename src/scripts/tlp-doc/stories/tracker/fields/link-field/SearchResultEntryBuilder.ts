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

import type { Badge, ProjectResponse, SearchResultEntry } from "@tuleap/core-rest-api-types";
import type { ColorName } from "@tuleap/core-constants";
import { ARTIFACT_TYPE } from "@tuleap/core-constants";
import { ProjectResponseBuilder } from "./ProjectResponseBuilder";

export class SearchResultEntryBuilder {
    readonly #artifact_id: number;
    #reference_key: string = "story";
    #title: string = "Romeo Mike";
    #color: ColorName = "lake-placid-blue";
    #project: ProjectResponse = ProjectResponseBuilder.aProject(113).build();
    #badges: Badge[] = [];

    private constructor(id: number) {
        this.#artifact_id = id;
    }

    static anArtifact(id: number): SearchResultEntryBuilder {
        return new SearchResultEntryBuilder(id);
    }

    withReferenceKey(reference_key: string): this {
        this.#reference_key = reference_key;
        return this;
    }

    withTitle(title: string): this {
        this.#title = title;
        return this;
    }

    withColor(color: ColorName): this {
        this.#color = color;
        return this;
    }

    withBadges(badge: Badge, ...other_badges: Badge[]): this {
        this.#badges = [badge, ...other_badges];
        return this;
    }

    inProject(project: ProjectResponse): this {
        this.#project = project;
        return this;
    }

    build(): SearchResultEntry {
        return {
            xref: `${this.#reference_key} ${this.#artifact_id}`,
            html_url: `/plugins/tracker/?aid=${this.#artifact_id}`,
            title: this.#title,
            color_name: this.#color,
            type: ARTIFACT_TYPE,
            per_type_id: this.#artifact_id,
            icon_name: "fa-solid fa-tlp-tracker",
            project: this.#project,
            cropped_content: "",
            quick_links: [],
            badges: this.#badges,
        };
    }
}
