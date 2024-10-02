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
import type { UpdateFunction } from "hybrids";
import { define, html } from "hybrids";
import type { ControlToolbar } from "./ToolbarController";
import scss_styles from "./styles.scss?inline";
import "./buttons/bold";

export type ProseMirrorToolbarElement = {
    controller: ControlToolbar;
};

export type InternalProseMirrorToolbarElement = Readonly<ProseMirrorToolbarElement>;

const TOOLBAR_TAG_NAME = "tuleap-prose-mirror-toolbar";

export const renderToolbar = (
    host: InternalProseMirrorToolbarElement,
): UpdateFunction<InternalProseMirrorToolbarElement> =>
    html`
        <div class="prose-mirror-toolbar-container" data-test="toolbar-container">
            <bold-item toolbar_bus="${host.controller.getToolbarBus()}"></bold-item>
        </div>
    `.style(scss_styles);

define<InternalProseMirrorToolbarElement>({
    tag: TOOLBAR_TAG_NAME,
    controller: (host, controller) => controller,
    render: {
        value: renderToolbar,
        shadow: false,
    },
});
