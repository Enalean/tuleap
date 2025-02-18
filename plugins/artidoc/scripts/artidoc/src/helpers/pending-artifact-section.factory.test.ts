/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
import PendingArtifactSectionFactory from "@/helpers/pending-artifact-section.factory";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";
describe("pending-artifact-section.factory", () => {
    describe("overrideFromTracker", () => {
        it("should take the html default value of the tracker", () => {
            const tracker = TrackerStub.withTitleAndDescription();

            expect(
                PendingArtifactSectionFactory.overrideFromTracker({
                    ...tracker,
                    description: {
                        ...tracker.description,
                        default_value: {
                            format: "text",
                            content: "<p>Lorem <em>ipsum</em></p>",
                        },
                    },
                }).description,
            ).toBe("<p>Lorem <em>ipsum</em></p>");
        });

        it("should convert the commonmark default value of the tracker", () => {
            const tracker = TrackerStub.withTitleAndDescription();

            expect(
                PendingArtifactSectionFactory.overrideFromTracker({
                    ...tracker,
                    description: {
                        ...tracker.description,
                        default_value: {
                            format: "commonmark",
                            content: "Lorem *ipsum*",
                        },
                    },
                }).description,
            ).toBe("<p>Lorem <em>ipsum</em></p>\n");
        });

        it("should convert the text default value of the tracker", () => {
            const tracker = TrackerStub.withTitleAndDescription();

            expect(
                PendingArtifactSectionFactory.overrideFromTracker({
                    ...tracker,
                    description: {
                        ...tracker.description,
                        default_value: {
                            format: "text",
                            content: "Lorem *ipsum*",
                        },
                    },
                }).description,
            ).toBe("<p>Lorem <em>ipsum</em></p>\n");
        });
    });
});
