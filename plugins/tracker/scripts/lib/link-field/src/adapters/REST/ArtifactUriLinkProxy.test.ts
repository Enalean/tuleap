/**
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

import { describe, expect, it } from "vitest";
import type { UserHistoryEntry } from "@tuleap/core-rest-api-types";
import { ArtifactUriLinkProxy } from "./ArtifactUriLinkProxy";

describe("ArtifactUriLinkProxy", () => {
    describe("fromAPIUserHistory", () => {
        it("Keeps the entry uri when it points to artifact view", () => {
            const entry = {
                per_type_id: 322,
                html_url: "/plugins/tracker/?aid=322",
            } as UserHistoryEntry;

            const result = ArtifactUriLinkProxy.fromAPIUserHistory(entry);
            expect(result).toStrictEqual(entry.html_url);
        });

        it("Changes the uri to the one to artifact view if entry points to another place", () => {
            const entry = {
                per_type_id: 388,
                html_url:
                    "/plugins/agiledashboard/?group_id=102&planning_id=2&action=show&aid=388&pane=details",
            } as UserHistoryEntry;

            const result = ArtifactUriLinkProxy.fromAPIUserHistory(entry);
            expect(result).toStrictEqual("/plugins/tracker/?aid=388");
        });
    });
});
