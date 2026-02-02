/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
    SemanticsRepresentation,
    BaseFieldStructure,
} from "@tuleap/plugin-tracker-rest-api-types";

import type { TrackerSemantics, TrackerSemantic } from "../type";

export function getTrackerSemantics(semantics: SemanticsRepresentation): TrackerSemantics {
    const TITLE = "title";
    const DESCRIPTION = "description";
    const STATUS = "status";
    const CONTRIBUTOR = "contributor";
    const INITIAL_EFFORT = "initial-effort";
    const PROGRESS_TOTAL = "progress-total";
    const PROGRESS_EFFORT = "progress-effort";
    const TIMEFRAME_START = "timeframe-start";
    const TIMEFRAME_END = "timeframe-end";
    const TIMEFRAME_DURATION = "timeframe-duration";

    type Semantic =
        | typeof TITLE
        | typeof DESCRIPTION
        | typeof STATUS
        | typeof CONTRIBUTOR
        | typeof INITIAL_EFFORT
        | typeof PROGRESS_TOTAL
        | typeof PROGRESS_EFFORT
        | typeof TIMEFRAME_START
        | typeof TIMEFRAME_END
        | typeof TIMEFRAME_DURATION;

    const by_field = new Map<number, Semantic[]>();

    if (semantics.title) {
        addSemanticForField(semantics.title.field_id, TITLE);
    }

    if (semantics.description) {
        addSemanticForField(semantics.description.field_id, DESCRIPTION);
    }

    if (semantics.status) {
        addSemanticForField(semantics.status.field_id, STATUS);
    }

    if (semantics.contributor) {
        addSemanticForField(semantics.contributor.field_id, CONTRIBUTOR);
    }

    if (semantics.initial_effort) {
        addSemanticForField(semantics.initial_effort.field_id, INITIAL_EFFORT);
    }

    if (semantics.progress) {
        if ("total_effort_field_id" in semantics.progress) {
            addSemanticForField(semantics.progress.total_effort_field_id, PROGRESS_TOTAL);
        }
        if ("remaining_effort_field_id" in semantics.progress) {
            addSemanticForField(semantics.progress.remaining_effort_field_id, PROGRESS_EFFORT);
        }
    }

    if (semantics.timeframe) {
        if ("start_date_field_id" in semantics.timeframe) {
            addSemanticForField(semantics.timeframe.start_date_field_id, TIMEFRAME_START);
        }

        if ("end_date_field_id" in semantics.timeframe) {
            addSemanticForField(semantics.timeframe.end_date_field_id, TIMEFRAME_END);
        }

        if ("duration_field_id" in semantics.timeframe) {
            addSemanticForField(semantics.timeframe.duration_field_id, TIMEFRAME_DURATION);
        }
    }

    function addSemanticForField(field_id: number, semantic: Semantic): void {
        by_field.set(field_id, [...(by_field.get(field_id) ?? []), semantic]);
    }

    return {
        getForField(
            field: BaseFieldStructure,
            $gettext: (msgid: string) => string,
        ): TrackerSemantic[] {
            return (by_field.get(field.field_id) || []).map((semantic: string) => {
                switch (semantic) {
                    case TITLE:
                        return {
                            label: $gettext("Title"),
                            description: $gettext("This field will be the title of an artifact"),
                        };
                    case DESCRIPTION:
                        return {
                            label: $gettext("Description"),
                            description: $gettext(
                                "This field will be the description of an artifact",
                            ),
                        };
                    case STATUS:
                        return {
                            label: $gettext("Status"),
                            description: $gettext("This field will be the status of an artifact"),
                        };
                    case CONTRIBUTOR:
                        return {
                            label: $gettext("Contributor"),
                            description: $gettext(
                                "User selected in this field will be the contributor of an artifact",
                            ),
                        };
                    case INITIAL_EFFORT:
                        return {
                            label: $gettext("Initial effort"),
                            description: $gettext(
                                "This field will be used to track the initial effort of an artifact",
                            ),
                        };
                    case PROGRESS_TOTAL:
                        return {
                            label: $gettext("Progress"),
                            description: $gettext(
                                "This field will be used to track the total effort to compute the progress of an artifact",
                            ),
                        };
                    case PROGRESS_EFFORT:
                        return {
                            label: $gettext("Progress"),
                            description: $gettext(
                                "This field will be used to track the remaining effort to compute the progress of an artifact",
                            ),
                        };
                    case TIMEFRAME_START:
                        return {
                            label: $gettext("Timeframe"),
                            description: $gettext(
                                "This field will be used to track the start date to compute the timeframe of an artifact",
                            ),
                        };
                    case TIMEFRAME_END:
                        return {
                            label: $gettext("Timeframe"),
                            description: $gettext(
                                "This field will be used to track the end date to compute the timeframe of an artifact",
                            ),
                        };
                    case TIMEFRAME_DURATION:
                        return {
                            label: $gettext("Timeframe"),
                            description: $gettext(
                                "This field will be used to track the duration to compute the timeframe of an artifact",
                            ),
                        };
                    default:
                        throw new Error("Unknown semantic");
                }
            });
        },
    };
}
