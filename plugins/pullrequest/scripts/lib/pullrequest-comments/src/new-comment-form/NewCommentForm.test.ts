/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { describe, it, expect, vi } from "vitest";
import type { HostElement } from "./NewCommentForm";
import { form_height_descriptor } from "./NewCommentForm";

describe("NewCommentForm", () => {
    it("should execute the post_rendering_callback each time the component height changes", () => {
        vi.useFakeTimers();
        const host = { post_rendering_callback: vi.fn() } as unknown as HostElement;

        form_height_descriptor.observe(host);
        vi.advanceTimersToNextTimer();

        expect(host.post_rendering_callback).toHaveBeenCalledTimes(1);
    });
});
