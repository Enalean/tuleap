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

export function addTransitionIdToUrl(transition_id: number): void {
    const updated_path = `${getBasePath()}/${encodeURIComponent(String(transition_id))}`;

    window.history.replaceState(
        window.history.state,
        "",
        `${updated_path}${window.location.search}${window.location.hash}`,
    );
}

export function removeTransitionIdFromUrl(): void {
    window.history.replaceState(
        window.history.state,
        "",
        `${getBasePath()}${window.location.search}${window.location.hash}`,
    );
}

export function getBasePath(): string {
    const route_match = window.location.pathname.match(
        /^(.*\/workflow\/\d+\/transitions)(?:\/\d+)?\/?$/,
    );

    if (!route_match) {
        throw Error("Could not find base path in URL");
    }

    return route_match[1];
}
