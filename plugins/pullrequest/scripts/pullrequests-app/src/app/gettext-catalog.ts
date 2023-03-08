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

interface GettextCatalog {
    getString: (source: string) => string;
    getPlural: (
        nb_items: number,
        singular_form: string,
        plural_form: string,
        params: Record<string, string>
    ) => string;
}

let gettextCatalog: GettextCatalog;

export function setCatalog(catalog: GettextCatalog): void {
    gettextCatalog = catalog;
}

export const getUserUpdatePullRequest = (): string =>
    gettextCatalog.getString("Has updated the pull request.");
export const getUserRebasePullRequest = (): string =>
    gettextCatalog.getString("Has rebased the pull request.");
export const getUserMergePullRequest = (): string =>
    gettextCatalog.getString("Has merged the pull request.");
export const getUserAbandonedPullRequest = (): string =>
    gettextCatalog.getString("Has abandoned the pull request.");
export const getUserReopenedPullRequest = (): string =>
    gettextCatalog.getString("Has reopened the pull request.");
export const getCollapsibleSectionLabel = (nb_lines: number): string =>
    gettextCatalog.getPlural(
        nb_lines,
        "... Skipped 1 common line",
        "... Skipped {{ $count }} common lines",
        {}
    );
