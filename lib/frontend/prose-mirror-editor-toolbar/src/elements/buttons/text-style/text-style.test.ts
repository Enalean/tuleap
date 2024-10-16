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

import { describe, it, expect, beforeEach } from "vitest";
import type { ToolbarBus } from "@tuleap/prose-mirror-editor";
import { buildToolbarBus, NB_HEADING } from "@tuleap/prose-mirror-editor";
import type { HostElement } from "./text-style";
import { connect } from "./text-style";

describe("text-style", () => {
    let toolbar_bus: ToolbarBus;

    beforeEach(() => {
        toolbar_bus = buildToolbarBus();
    });

    it("When the component is connected, then it should set its part of the toolbar view so it will be able to update itself when the view changes.", () => {
        const host = {
            current_heading: null,
            is_plain_text_activated: false,
            is_preformatted_text_activated: false,
            style_elements: {
                headings: true,
                text: true,
                preformatted: true,
            },
            toolbar_bus,
        } as HostElement;

        connect(host);

        for (let level = 1; level < NB_HEADING; level++) {
            toolbar_bus.view.activateHeading({ level });
            expect(host.current_heading).toStrictEqual({ level });
        }

        toolbar_bus.view.activatePlainText(true);
        expect(host.is_plain_text_activated).toBe(true);

        toolbar_bus.view.activatePlainText(false);
        expect(host.is_plain_text_activated).toBe(false);

        toolbar_bus.view.activatePreformattedText(true);
        expect(host.is_preformatted_text_activated).toBe(true);

        toolbar_bus.view.activatePreformattedText(false);
        expect(host.is_preformatted_text_activated).toBe(false);
    });
});
