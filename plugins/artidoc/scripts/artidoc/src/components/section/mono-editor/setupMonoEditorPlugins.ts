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

import type { Plugin } from "prosemirror-state";
import { JumpToSectionNodePlugin } from "@/components/section/mono-editor/jump-to-section-node";
import { EnableOrDisableToolbarPlugin } from "@/components/section/mono-editor/enable-or-disable-toolbar";
import type { ToolbarBus } from "@tuleap/prose-mirror-editor";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import type { HeadingsButtonState } from "@/toolbar/HeadingsButtonState";

export const setupMonoEditorPlugins = (
    toolbar_bus: ToolbarBus,
    headings_button_state: HeadingsButtonState,
    section: ReactiveStoredArtidocSection,
): Plugin[] => [
    JumpToSectionNodePlugin(),
    EnableOrDisableToolbarPlugin(toolbar_bus, headings_button_state, section),
];
