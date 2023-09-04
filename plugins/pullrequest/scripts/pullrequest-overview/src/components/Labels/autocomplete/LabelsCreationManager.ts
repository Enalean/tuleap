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

import type { ProjectLabel } from "@tuleap/plugin-pullrequest-rest-api-types";

export const LABEL_TO_CREATE_TMP_ID = 0;

export type ManageLabelsCreation = {
    canLabelBeCreated(label: string): boolean;
    getLabelsToCreate(): ReadonlyArray<string>;
    getTemporaryLabel(label: string): ProjectLabel;
    registerLabelsToCreate(selection: ReadonlyArray<ProjectLabel>): void;
};

export const LabelsCreationManager = (
    project_labels: ReadonlyArray<ProjectLabel>,
): ManageLabelsCreation => {
    let labels_to_create: string[] = [];

    return {
        canLabelBeCreated: (label: string): boolean => {
            const lowercase_label = label.toLowerCase();

            return (
                label.trim() !== "" &&
                !project_labels.find(
                    (project_label) => project_label.label.toLowerCase() === lowercase_label,
                ) &&
                !labels_to_create.find(
                    (label_to_create) => label_to_create.toLowerCase() === lowercase_label,
                )
            );
        },
        getLabelsToCreate: () => labels_to_create,
        getTemporaryLabel: (label: string): ProjectLabel => ({
            id: LABEL_TO_CREATE_TMP_ID,
            label,
            is_outline: true,
            color: "chrome-silver",
        }),
        registerLabelsToCreate(selection: ReadonlyArray<ProjectLabel>): void {
            labels_to_create = selection
                .filter(({ id }) => id === LABEL_TO_CREATE_TMP_ID)
                .map(({ label }) => label);
        },
    };
};
