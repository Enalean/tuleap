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

import { describe, expect, it } from "vitest";
import { getTrackerSemantics } from "./get-tracker-semantics";
import type { BaseFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";

describe("getTrackerSemantics", () => {
    describe("getForField", () => {
        const gettext = (msgid: string): string => msgid;

        const field: BaseFieldStructure = {
            field_id: 123,
            label: "Summary",
            name: "summary",
            required: false,
            has_notifications: false,
        };

        it("returns empty array if field have no semantic", () => {
            expect(getTrackerSemantics({}).getForField(field, gettext)).toStrictEqual([]);
        });

        it("returns [Title] if field have title semantic", () => {
            expect(
                getTrackerSemantics({
                    title: {
                        field_id: field.field_id,
                    },
                })
                    .getForField(field, gettext)
                    .map((semantic) => semantic.label),
            ).toStrictEqual(["Title"]);
        });

        it("returns [Description] if field have description semantic", () => {
            expect(
                getTrackerSemantics({
                    description: {
                        field_id: field.field_id,
                    },
                })
                    .getForField(field, gettext)
                    .map((semantic) => semantic.label),
            ).toStrictEqual(["Description"]);
        });

        it("returns [Title, Description] if field have title and description semantic", () => {
            expect(
                getTrackerSemantics({
                    title: {
                        field_id: field.field_id,
                    },
                    description: {
                        field_id: field.field_id,
                    },
                })
                    .getForField(field, gettext)
                    .map((semantic) => semantic.label),
            ).toStrictEqual(["Title", "Description"]);
        });

        it("returns [Status] if field have status semantic", () => {
            expect(
                getTrackerSemantics({
                    status: {
                        field_id: field.field_id,
                    },
                })
                    .getForField(field, gettext)
                    .map((semantic) => semantic.label),
            ).toStrictEqual(["Status"]);
        });

        it("returns [Contributor] if field have contributor semantic", () => {
            expect(
                getTrackerSemantics({
                    contributor: {
                        field_id: field.field_id,
                    },
                })
                    .getForField(field, gettext)
                    .map((semantic) => semantic.label),
            ).toStrictEqual(["Contributor"]);
        });

        it("returns [Initial effort] if field have initial effort semantic", () => {
            expect(
                getTrackerSemantics({
                    initial_effort: {
                        field_id: field.field_id,
                    },
                })
                    .getForField(field, gettext)
                    .map((semantic) => semantic.label),
            ).toStrictEqual(["Initial effort"]);
        });

        it("returns [Progress] if field is the total effort of progress semantic", () => {
            expect(
                getTrackerSemantics({
                    progress: {
                        total_effort_field_id: field.field_id,
                        remaining_effort_field_id: 0,
                    },
                })
                    .getForField(field, gettext)
                    .map((semantic) => semantic.label),
            ).toStrictEqual(["Progress"]);
        });

        it("returns [Progress] if field is the remaining effort of progress semantic", () => {
            expect(
                getTrackerSemantics({
                    progress: {
                        total_effort_field_id: 0,
                        remaining_effort_field_id: field.field_id,
                    },
                })
                    .getForField(field, gettext)
                    .map((semantic) => semantic.label),
            ).toStrictEqual(["Progress"]);
        });

        it("returns [Timeframe] if field is the start of timeframe semantic", () => {
            expect(
                getTrackerSemantics({
                    timeframe: {
                        start_date_field_id: field.field_id,
                        end_date_field_id: 0,
                    },
                })
                    .getForField(field, gettext)
                    .map((semantic) => semantic.label),
            ).toStrictEqual(["Timeframe"]);
        });

        it("returns [Timeframe] if field is the end of timeframe semantic", () => {
            expect(
                getTrackerSemantics({
                    timeframe: {
                        start_date_field_id: 0,
                        end_date_field_id: field.field_id,
                    },
                })
                    .getForField(field, gettext)
                    .map((semantic) => semantic.label),
            ).toStrictEqual(["Timeframe"]);
        });

        it("returns [Timeframe] if field is the duration of timeframe semantic", () => {
            expect(
                getTrackerSemantics({
                    timeframe: {
                        start_date_field_id: 0,
                        duration_field_id: field.field_id,
                    },
                })
                    .getForField(field, gettext)
                    .map((semantic) => semantic.label),
            ).toStrictEqual(["Timeframe"]);
        });
    });
});
