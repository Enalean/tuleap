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

import { describe, it, expect, vi, beforeEach } from "vitest";
import { createLocalDocument } from "./helpers/helper-for-test";
import type { InternalProseMirrorToolbarElement } from "./toolbar-element";
import { connect, renderToolbar } from "./toolbar-element";
import type { ControlToolbar } from "./ToolbarController";
import { ToolbarController } from "./ToolbarController";
import { buildToolbarBus } from "@tuleap/prose-mirror-editor";

describe("ToolbarElement", () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    it("When the component is connected, then the view should be set", () => {
        const doc = createLocalDocument();
        const toolbar_bus = buildToolbarBus();
        const controller: ControlToolbar = ToolbarController(toolbar_bus);

        const host = Object.assign(doc.createElement("span"), {
            is_bold_activated: false,
            controller,
        } as InternalProseMirrorToolbarElement);
        const target = doc.createElement("span") as unknown as ShadowRoot;

        renderToolbar(host)(host, target);

        const toolbar = target.querySelector<HTMLSpanElement>("[data-test=toolbar-container]");
        if (!toolbar) {
            throw new Error("Unable to find the toolbar element");
        }

        connect(host);

        host.controller.getToolbarBus().view?.activateBold(true);

        expect(host.is_bold_activated).toBe(true);
    });
});
