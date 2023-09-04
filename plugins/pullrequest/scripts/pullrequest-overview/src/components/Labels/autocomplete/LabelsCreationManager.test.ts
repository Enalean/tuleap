/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import type { ProjectLabel } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { ManageLabelsCreation } from "./LabelsCreationManager";
import { LabelsCreationManager } from "./LabelsCreationManager";

const emergency_label: ProjectLabel = {
    id: 1,
    label: "Emergency",
    is_outline: false,
    color: "fiesta-red",
};

describe("LabelsCreationManager", () => {
    let manager: ManageLabelsCreation;

    const buildTemporaryProjectLabels = (labels: string[]): ProjectLabel[] =>
        labels.map(manager.getTemporaryLabel);

    beforeEach(() => {
        manager = LabelsCreationManager([emergency_label]);
    });

    it("getLabelsToCreate() should return the labels to create", () => {
        const labels_to_create = ["gluten-free", "organic", "expensive"];

        manager.registerLabelsToCreate(buildTemporaryProjectLabels(labels_to_create));

        expect(manager.getLabelsToCreate()).toStrictEqual(labels_to_create);
    });

    it(`registerLabelsToCreate() should extract the labels to create from lazybox's selection`, () => {
        const new_label = "Help required";
        const selection: ProjectLabel[] = [emergency_label, manager.getTemporaryLabel(new_label)];

        manager.registerLabelsToCreate(selection);

        expect(manager.getLabelsToCreate()).toStrictEqual([new_label]);
    });

    it("getTemporaryLabel() should create a basic ProjectLabel with 0 as id, and the provided label as label", () => {
        expect(manager.getTemporaryLabel("Gluten free")).toStrictEqual({
            id: 0,
            label: "Gluten free",
            is_outline: true,
            color: "chrome-silver",
        });
    });

    it.each([
        [false, "the provided label does not contain any character", "  ", []],
        [
            false,
            "the provided label is already registered for creation",
            "gluten-free",
            ["gluten-free"],
        ],
        [
            false,
            "the provided label already exists",
            emergency_label.label,
            [emergency_label.label],
        ],
        [
            false,
            "there is already a registered label but written with a different case",
            emergency_label.label.toUpperCase(),
            [emergency_label.label],
        ],
        [
            false,
            "there is already an existing label but written with a different case",
            emergency_label.label.toUpperCase(),
            [],
        ],
        [
            true,
            "the provided label contains characters and has not been registered for creation yet",
            "gluten-free",
            [],
        ],
    ])(
        "canLabelBeCreated() should return %s when %s",
        (can_be_created, when, label, registered_labels) => {
            manager.registerLabelsToCreate(buildTemporaryProjectLabels(registered_labels));

            expect(manager.canLabelBeCreated(label)).toBe(can_be_created);
        },
    );
});
