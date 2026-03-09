/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

import { addTransitionIdToUrl, removeTransitionIdFromUrl } from "./url-synchronizer";

const transition_id = 44;
const url_with_transition_id = `/plugins/tracker/workflow/12/transitions/${transition_id}`;
const url_without_transition_id = `/plugins/tracker/workflow/12/transitions`;

describe("url-synchronizer", () => {
    it("should synchronize url with transition id when addTransitionIdToUrl is called", () => {
        window.history.replaceState({}, "", url_without_transition_id);

        addTransitionIdToUrl(transition_id);

        expect(window.location.pathname).toStrictEqual(url_with_transition_id);
    });

    it("should synchronize url whithout transition id when removeTransitionIdFromUrl is called", () => {
        window.history.replaceState({}, "", url_with_transition_id);

        removeTransitionIdFromUrl();

        expect(window.location.pathname).toStrictEqual(url_without_transition_id);
    });
});
