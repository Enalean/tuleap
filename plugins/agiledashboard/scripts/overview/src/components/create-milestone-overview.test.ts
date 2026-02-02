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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { createMilestoneOverview } from "./create-milestone-overview";
import type { GetText } from "@tuleap/gettext";
import { isMilestoneOverviewElement, TAG } from "./milestone-overview";
import * as rest_querier from "../api/rest-querier";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { ArtifactTestBuilder } from "../../tests/ArtifactTestBuilder";

describe("createMilestoneOverview", () => {
    let doc: Document;
    let mount_point: HTMLElement;

    const gettext_provider = {
        gettext: (msgid: string) => msgid,
    } as GetText;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        mount_point = doc.createElement("div");
        mount_point.setAttribute("data-milestone-id", "123");
        mount_point.setAttribute("data-submilestones-ids", "251,252");
        mount_point.setAttribute("data-solve-inconsistencies-url", "https://example.com");
        mount_point.setAttribute("data-csrf-synchronizer-token", "{}");
        doc.body.appendChild(mount_point);
    });

    it("Should display API error in danger alert", async () => {
        vi.spyOn(rest_querier, "getMilestoneBacklog").mockReturnValue(
            errAsync(Fault.fromMessage("Oh no!")),
        );
        await createMilestoneOverview(mount_point, gettext_provider);

        expect(doc.body.querySelector<HTMLElement>(".tlp-alert-danger")?.innerText).toBe(
            "An error occurred while loading the content of the milestone: Oh no!",
        );
    });

    it("Should create element and populate it", async () => {
        const getMilestoneBacklog = vi
            .spyOn(rest_querier, "getMilestoneBacklog")
            .mockReturnValue(
                okAsync([
                    new ArtifactTestBuilder(35).withLabel("Label 1").withStatus("Todo").build(),
                    new ArtifactTestBuilder(37).withLabel("Label 3").withStatus("None").build(),
                ]),
            );
        const getMilestoneItems = vi
            .spyOn(rest_querier, "getMilestoneItems")
            .mockReturnValueOnce(
                okAsync([
                    new ArtifactTestBuilder(35).withLabel("Label 1").withStatus("Todo").build(),
                    new ArtifactTestBuilder(36).withLabel("Label 2").withStatus("Done").build(),
                ]),
            )
            .mockReturnValueOnce(
                okAsync([
                    new ArtifactTestBuilder(37).withLabel("Label 3").withStatus("None").build(),
                ]),
            );
        await createMilestoneOverview(mount_point, gettext_provider);

        expect(getMilestoneBacklog).toHaveBeenCalledExactlyOnceWith(123);
        expect(getMilestoneItems).toHaveBeenCalledTimes(2);
        expect(getMilestoneItems).toHaveBeenNthCalledWith(1, 251);
        expect(getMilestoneItems).toHaveBeenNthCalledWith(2, 252);

        const element = doc.body.querySelector<HTMLElement>(TAG);
        if (element === null || !isMilestoneOverviewElement(element)) {
            throw Error("Failed to find created milestone overview element");
        }
        const rows = element.querySelectorAll("[data-test=artifact-row]");
        expect(rows).toHaveLength(3);
        expect(rows[0].querySelector("[data-test=item-label]")?.textContent).toContain("Label 1");
        expect(rows[0].querySelector("[data-test=item-status]")?.textContent).toContain("Todo");
        expect(rows[0].querySelector("[data-test=inconsistent-warning]")).toBe(null);
        expect(rows[1].querySelector("[data-test=item-label]")?.textContent).toContain("Label 3");
        expect(rows[1].querySelector("[data-test=item-status]")?.textContent).toContain("None");
        expect(rows[1].querySelector("[data-test=inconsistent-warning]")).toBe(null);
        expect(rows[2].querySelector("[data-test=item-label]")?.textContent).toContain("Label 2");
        expect(rows[2].querySelector("[data-test=item-status]")?.textContent).toContain("Done");
        expect(rows[2].querySelector("[data-test=inconsistent-warning]")).not.toBe(null);

        const inconsistent = element.querySelectorAll<HTMLInputElement>(
            "[data-test=inconsistent-input]",
        );
        expect(inconsistent).toHaveLength(1);
        expect(inconsistent[0].value).toBe("36");
    });
});
