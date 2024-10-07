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

import { describe, it, expect } from "vitest";
import { buildToolbarBus } from "@tuleap/prose-mirror-editor";
import type { HostElement } from "./image";
import { connect } from "./image";

describe("image", () => {
    describe("connect", () => {
        it("When the component is connected, then it should set its part of the toolbar view so it will be able to update itself when the view changes", () => {
            const toolbar_bus = buildToolbarBus();
            const host = { toolbar_bus } as unknown as HostElement;

            connect(host);

            const image_state = {
                is_activated: true,
                is_disabled: false,
                image_src: "https://example.com",
                image_title: "See example",
            };

            toolbar_bus.view.activateImage(image_state);

            expect(host.is_activated).toBe(image_state.is_activated);
            expect(host.is_disabled).toBe(image_state.is_disabled);
            expect(host.image_src).toBe(image_state.image_src);
            expect(host.image_title).toBe(image_state.image_title);
        });
    });
});
