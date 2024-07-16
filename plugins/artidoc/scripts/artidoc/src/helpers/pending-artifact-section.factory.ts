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

import type { PendingArtifactSection } from "@/helpers/artidoc-section.type";
import { v4 as uuidv4 } from "uuid";
import type { TrackerWithSubmittableSection } from "@/stores/configuration-store";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";

const PendingArtifactSectionFactory = {
    create: (): PendingArtifactSection => ({
        id: uuidv4(),
        tracker: TrackerStub.withoutTitleAndDescription(),
        display_title: "Technologies section",
        title: {
            field_id: 110,
            type: "string",
            label: "Summary",
            value: "Technologies section",
        },
        description: {
            field_id: 111,
            type: "text",
            label: "Original Submission",
            value: "<h2>Title 1</h2><p>description 1</p>",
            format: "html",
            post_processed_value: "<h2>Title 1</h2><p>description 1</p>",
        },
        attachments: {
            field_id: 171,
            label: "attachment",
            type: "file",
            file_descriptions: [],
        },
    }),

    override: (overrides: Partial<PendingArtifactSection>): PendingArtifactSection => ({
        ...PendingArtifactSectionFactory.create(),
        ...overrides,
    }),

    overrideFromTracker: (tracker: TrackerWithSubmittableSection): PendingArtifactSection =>
        PendingArtifactSectionFactory.override({
            tracker,
            title: {
                ...tracker.title,
                value: tracker.title.default_value,
                ...(tracker.title.type === "string"
                    ? { type: "string" }
                    : { type: "text", post_processed_value: "", format: "html" }),
            },
            display_title: tracker.title.default_value,
            description: {
                ...tracker.description,
                value: tracker.description.default_value.content,
                post_processed_value: "",
                format: tracker.description.default_value.format,
                ...(tracker.description.default_value.format === "commonmark"
                    ? { commonmark: tracker.description.default_value.content }
                    : {}),
            },
            attachments: tracker.file ? { ...tracker.file, file_descriptions: [] } : null,
        }),
};

export default PendingArtifactSectionFactory;
