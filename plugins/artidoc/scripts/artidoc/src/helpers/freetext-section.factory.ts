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

import type { FreetextSection } from "@/helpers/artidoc-section.type";
import { v4 as uuidv4 } from "uuid";

const FreetextSectionFactory = {
    create: (): FreetextSection => ({
        type: "freetext",
        id: uuidv4(),
        title: "",
        description: "",
        attachments: null,
        is_pending: false,
        level: 1,
        display_level: "",
    }),

    pending: (): FreetextSection => ({
        ...FreetextSectionFactory.create(),
        is_pending: true,
    }),

    override: (overrides: Partial<FreetextSection>): FreetextSection => ({
        ...FreetextSectionFactory.create(),
        ...overrides,
    }),
};

export default FreetextSectionFactory;
