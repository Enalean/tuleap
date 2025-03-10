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

import type { ArtifactResponseNoInstance, Status } from "@tuleap/plugin-tracker-rest-api-types";
import type { TrackerRepresentation } from "./TrackerRepresentationBuilder";
import { TrackerRepresentationBuilder } from "./TrackerRepresentationBuilder";

export type ArtifactResponse = Pick<
    ArtifactResponseNoInstance,
    "id" | "title" | "xref" | "tracker" | "html_url" | "full_status" | "is_open"
>;

export class ArtifactRespresentationBuilder {
    readonly #artifact_id: number;
    #title: string = "Random Tungsten";
    #tracker: TrackerRepresentation = TrackerRepresentationBuilder.aTracker(228).build();
    #is_open: boolean = true;
    #status: Status = { value: "Ongoing", color: "neon-green" };

    private constructor(id: number) {
        this.#artifact_id = id;
    }

    static anArtifact(id: number): ArtifactRespresentationBuilder {
        return new ArtifactRespresentationBuilder(id);
    }

    withTitle(title: string): this {
        this.#title = title;
        return this;
    }

    withStatus(status: Status, is_open: boolean): this {
        this.#status = status;
        this.#is_open = is_open;
        return this;
    }

    ofTracker(tracker: TrackerRepresentation): this {
        this.#tracker = tracker;
        return this;
    }

    build(): ArtifactResponse {
        const reference = `${this.#tracker.item_name} #${this.#artifact_id}`;

        // eslint-disable-next-line @typescript-eslint/no-unused-vars -- we are specifically omitting item_name that does not exist in the real REST payload
        const { item_name, ...tracker_representation } = this.#tracker;
        return {
            id: this.#artifact_id,
            title: this.#title,
            xref: reference,
            tracker: tracker_representation,
            html_url: `/plugins/tracker/?aid=${this.#artifact_id}`,
            is_open: this.#is_open,
            full_status: this.#status,
        };
    }
}
