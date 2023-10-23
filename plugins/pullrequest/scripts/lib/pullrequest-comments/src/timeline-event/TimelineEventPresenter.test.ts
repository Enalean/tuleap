/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
import { GettextProviderStub } from "../../tests/stubs/GettextProviderStub";
import { ActionEventStub } from "../../tests/stubs/ActionEventStub";
import { TimelineEventPresenter } from "./TimelineEventPresenter";

describe("TimelineEventPresenter", () => {
    it.each([
        ["udpate", ActionEventStub.buildActionUpdate()],
        ["rebase", ActionEventStub.buildActionRebase()],
        ["merge", ActionEventStub.buildActionMerge()],
        ["abandon", ActionEventStub.buildActionAbandon()],
        ["reopen", ActionEventStub.buildActionReopen()],
    ])("should build a presenter for a pull-request %s event", (event_name, action_event) => {
        const presenter = TimelineEventPresenter.fromActionOnPullRequestEvent(
            action_event,
            GettextProviderStub,
        );

        expect(presenter.user).toBe(action_event.user);
        expect(presenter.post_date).toBe(action_event.post_date);
        expect(presenter.message).not.toBe("");
    });
});
