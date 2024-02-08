/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, it, expect, vi } from "vitest";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { ProjectLabel } from "@tuleap/plugin-pullrequest-rest-api-types";
import { ProjectLabelStub } from "../../../../tests/stubs/ProjectLabelStub";
import * as rest_querier from "../../../api/tuleap-rest-querier";
import { LabelsLoader } from "./LabelsLoader";

const labels: ProjectLabel[] = [
    ProjectLabelStub.outlinedWithIdAndLabel(1, "Emergency"),
    ProjectLabelStub.outlinedWithIdAndLabel(2, "Easy fix"),
    ProjectLabelStub.outlinedWithIdAndLabel(3, "Doc"),
];

const project_id = 2;

describe("LabelsLoader", () => {
    it("should load the labels and return a collection of LazyboxItem", async () => {
        vi.spyOn(rest_querier, "fetchProjectLabels").mockReturnValue(okAsync(labels));

        const loadItems = LabelsLoader(() => {
            // Do nothing
        }, project_id);
        const items = await loadItems();

        expect(rest_querier.fetchProjectLabels).toHaveBeenCalledWith(project_id);
        expect(items).toHaveLength(labels.length);
        expect(items).toStrictEqual([
            { value: labels[0], is_disabled: false },
            { value: labels[1], is_disabled: false },
            { value: labels[2], is_disabled: false },
        ]);
    });

    it("When an error occurs, Then it should call the on_error_callback and return an empty array", async () => {
        const tuleap_api_error = Fault.fromMessage("Oops!");
        const on_error_callback = vi.fn();

        vi.spyOn(rest_querier, "fetchProjectLabels").mockReturnValue(errAsync(tuleap_api_error));

        const loadItems = LabelsLoader(on_error_callback, project_id);
        const items = await loadItems();

        expect(on_error_callback).toHaveBeenCalledWith(tuleap_api_error);
        expect(items).toHaveLength(0);
    });
});
