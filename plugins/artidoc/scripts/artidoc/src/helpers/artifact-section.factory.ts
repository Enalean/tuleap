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

import type { ArtifactSection } from "@/helpers/artidoc-section.type";
import { v4 as uuidv4 } from "uuid";

const ArtifactSectionFactory = {
    create: (): ArtifactSection => ({
        id: uuidv4(),
        artifact: {
            id: 0,
            uri: "artifacts/1",
            tracker: {
                id: 0,
                uri: "trackers/1",
                label: "Bugs",
                project: {
                    id: 101,
                    uri: "projects/101",
                    label: "project_1",
                    icon: "",
                },
            },
        },
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
        can_user_edit_section: true,
        attachments: {
            field_id: 171,
            label: "attachment",
            type: "file",
            file_descriptions: [],
        },
    }),

    override: (overrides: Partial<ArtifactSection>): ArtifactSection => ({
        ...ArtifactSectionFactory.create(),
        ...overrides,
    }),
};

export default ArtifactSectionFactory;
