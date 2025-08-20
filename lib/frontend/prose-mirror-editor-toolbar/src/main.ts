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
 *
 */

import "./elements/toolbar-element";

export type {
    ToolbarBus,
    LinkProperties,
    ImageProperties,
    EmojiProperties,
} from "@tuleap/prose-mirror-editor";
export { buildToolbarBus } from "@tuleap/prose-mirror-editor";

export { buildToolbarController } from "./elements/ToolbarController";
export { createProseMirrorEditorToolbar } from "./create-prose-mirror-editor-toolbar";

export type {
    LinkElements,
    ListElements,
    ScriptElements,
    StyleElements,
    TextElements,
    AdditionalElementPosition,
    ItemGroupName,
} from "./elements/toolbar-element";

export {
    BASIC_TEXT_ITEMS_GROUP,
    TEXT_STYLES_ITEMS_GROUP,
    LIST_ITEMS_GROUP,
    LINK_ITEMS_GROUP,
    SCRIPTS_ITEMS_GROUP,
} from "./elements/toolbar-element";
