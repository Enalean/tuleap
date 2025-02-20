/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import type { Tracker, TrackerWithSubmittableSection } from "@/stores/configuration-store";

const base: Tracker = {
    id: 101,
    label: "Bugs",
    color: "flamingo-pink",
    item_name: "bugs",
    title: null,
    description: null,
    file: null,
    project: {
        id: 101,
        uri: "/my-project",
        label: "My project",
        icon: "project-icon",
    },
};

export const TrackerStub = {
    build: (id: number, label: string): Tracker => ({
        ...base,
        id,
        label,
    }),

    withoutTitleAndDescription: (): Tracker => base,

    withTitle: (): Tracker => ({
        ...base,
        title: {
            field_id: 1001,
            label: "Summary",
            type: "string",
            default_value: "",
        },
    }),

    withProjectId: (project_id: number): Tracker => ({
        ...base,
        project: {
            ...base.project,
            id: project_id,
        },
    }),

    withDescription: (): Tracker => ({
        ...base,
        description: {
            label: "Details",
            type: "text",
            default_value: { format: "html", content: "" },
        },
    }),

    withTitleAndDescription: (): TrackerWithSubmittableSection => ({
        ...base,
        title: {
            field_id: 1001,
            label: "Summary",
            type: "string",
            default_value: "",
        },
        description: {
            label: "Details",
            type: "text",
            default_value: { format: "html", content: "" },
        },
    }),
};
