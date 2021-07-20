/**
 * Copyright (c) 2021-present, Enalean. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

import { actionsOnHelpMenuOpened } from "./actions-help-menu-opened";

describe("actions-help-menu-opened", () => {
    it("does actions when the help menu is opened", async (): Promise<void> => {
        const tlp_post = jest.fn();

        const help_button = document.createElement("div");
        const release_note_available_class = "new-release-note-available";
        help_button.classList.add(release_note_available_class);

        await actionsOnHelpMenuOpened(help_button, tlp_post);

        expect(help_button.classList.contains(release_note_available_class)).toBe(false);
        expect(tlp_post).toHaveBeenCalled();
    });
});
