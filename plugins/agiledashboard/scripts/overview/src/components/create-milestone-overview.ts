/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

import { getAttributeOrThrow } from "@tuleap/dom";
import type { GetText } from "@tuleap/gettext";
import { Result, ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { limitConcurrencyPool } from "@tuleap/concurrency-limit-pool";
import type { Artifact } from "../types";
import { isMilestoneOverviewElement, TAG } from "./milestone-overview";
import { getMilestoneBacklog, getMilestoneItems } from "../api/rest-querier";

interface ArtifactCollections {
    readonly top_items: ReadonlyArray<Artifact>;
    readonly submilestone_items: ReadonlyArray<Artifact>;
}

interface GroupedArtifacts {
    readonly all_linked_items: ReadonlyArray<Artifact>;
    readonly inconsistent_items: ReadonlyArray<Artifact>;
}

const MAX_PARALLEL_REQUESTS = 6;

export function createMilestoneOverview(
    mount_point: HTMLElement,
    gettext_provider: GetText,
): Promise<void> {
    const milestone_id = Number.parseInt(getAttributeOrThrow(mount_point, "data-milestone-id"), 10);
    const submilestones_attribute = getAttributeOrThrow(mount_point, "data-submilestones-ids");
    const submilestones_ids =
        submilestones_attribute === ""
            ? []
            : submilestones_attribute.split(",").map((id) => Number.parseInt(id, 10));
    const solve_inconsistencies_url = getAttributeOrThrow(
        mount_point,
        "data-solve-inconsistencies-url",
    );
    const csrf_token = JSON.parse(getAttributeOrThrow(mount_point, "data-csrf-synchronizer-token"));

    return getMilestoneBacklog(milestone_id)
        .andThen((top_items) => {
            return ResultAsync.fromSafePromise(
                limitConcurrencyPool(
                    MAX_PARALLEL_REQUESTS,
                    submilestones_ids,
                    getMilestoneItems,
                ).then((results: Array<Result<ReadonlyArray<Artifact>, Fault>>) =>
                    Result.combine(results).map((sub_items_arrays): ArtifactCollections => {
                        return { top_items, submilestone_items: sub_items_arrays.flat(1) };
                    }),
                ),
            ).andThen((ok_items): Result<ArtifactCollections, Fault> => ok_items);
        })
        .map(findInconsistentItems)
        .match(
            ({ all_linked_items, inconsistent_items }) => {
                const element = document.createElement(TAG);
                if (!isMilestoneOverviewElement(element)) {
                    throw Error("Failed to create milestone overview element");
                }
                element.gettext_provider = gettext_provider;
                element.solve_inconsistencies_url = solve_inconsistencies_url;
                element.csrf_token = csrf_token;
                element.all_linked_items = all_linked_items;
                element.inconsistent_items = inconsistent_items;
                mount_point.replaceWith(element);
            },
            (fault) => {
                const error_div = document.createElement("div");
                error_div.classList.add("tlp-alert-danger");
                error_div.innerText = gettext_provider
                    .gettext("An error occurred while loading the content of the milestone: %s")
                    .replace("%s", fault.toString());
                mount_point.replaceWith(error_div);
            },
        );
}

function findInconsistentItems(collections: ArtifactCollections): GroupedArtifacts {
    const inconsistent_items = collections.submilestone_items.reduce(
        (accumulator: Array<Artifact>, current) => {
            if (collections.top_items.find((item) => current.id === item.id) === undefined) {
                accumulator.push(current);
            }
            return accumulator;
        },
        [],
    );
    const all_linked_items = collections.top_items.concat(inconsistent_items);

    return { all_linked_items, inconsistent_items };
}
