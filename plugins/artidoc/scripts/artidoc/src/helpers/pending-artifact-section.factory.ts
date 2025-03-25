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
import { parse } from "marked";

const PendingArtifactSectionFactory = {
    create: (): PendingArtifactSection => ({
        type: "artifact",
        id: uuidv4(),
        tracker: TrackerStub.withoutTitleAndDescription(),
        title: "Technologies section",
        description: "<h2>Title 1</h2><p>description 1</p>",
        attachments: {
            upload_url: "/api/v1/tracker_fields/171/files",
            attachment_ids: [],
        },
        level: 1,
        display_level: "",
        fields: [],
    }),

    override: (overrides: Partial<PendingArtifactSection>): PendingArtifactSection => ({
        ...PendingArtifactSectionFactory.create(),
        ...overrides,
    }),

    overrideFromTracker: (tracker: TrackerWithSubmittableSection): PendingArtifactSection =>
        PendingArtifactSectionFactory.override({
            tracker,
            title: tracker.title.default_value,
            description: ["commonmark", "text"].includes(tracker.description.default_value.format)
                ? parse(tracker.description.default_value.content)
                : tracker.description.default_value.content,
            attachments: tracker.file
                ? {
                      upload_url: tracker.file.upload_url,
                      attachment_ids: [],
                  }
                : null,
        }),
};

export default PendingArtifactSectionFactory;
