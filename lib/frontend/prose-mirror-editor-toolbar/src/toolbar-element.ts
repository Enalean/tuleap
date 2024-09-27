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
import "./bold";

export type ProseMirrorToolbarElement = {
    controller: ControlToolbar;
};

export type InternalProseMirrorToolbarElement = Readonly<ProseMirrorToolbarElement> & {
    is_bold_activated: boolean;
};

const TOOLBAR_TAG_NAME = "tuleap-prose-mirror-toolbar";

export const connect = (host: InternalProseMirrorToolbarElement): void => {
    host.controller.getToolbarBus().setView({
        activateBold: (is_activated: boolean): void => {
            host.is_bold_activated = is_activated;
        },
    });
};

export const renderToolbar = (
    host: InternalProseMirrorToolbarElement,
): UpdateFunction<InternalProseMirrorToolbarElement> =>
    html`
        <div class="prose-mirror-toolbar-container" data-test="toolbar-container">
            <bold-item
                is_activated="${host.is_bold_activated}"
                toolbar_bus="${host.controller.getToolbarBus()}"
            ></bold-item>
        </div>
    `.style(scss_styles);

define<InternalProseMirrorToolbarElement>({
    tag: TOOLBAR_TAG_NAME,
    is_bold_activated: false,
    controller: {
        value: (host, controller) => controller,
        connect,
    },
    render: {
        value: renderToolbar,
        shadow: false,
    },
});
