/*
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

import { highlightColumn } from "./kanban-column-highlighter";

describe("highlightColumn() -", () => {
    it(`Given item is added in backlog column and given column is closed
            Then it highlight backlog column`, () => {
        const artifact = {
            in_column: "backlog",
        };

        const kanban = {
            backlog: {
                is_open: false,
            },
            columns: [],
        };

        const $scope = {};

        highlightColumn($scope, artifact, kanban);

        expect(kanban.backlog.have_new_item).toBe(true);
    });

    it(`Given item is added in backlog column and given column is opened
            Then nothing is highlighted`, () => {
        const artifact = {
            in_column: "backlog",
        };

        const kanban = {
            backlog: {
                is_open: true,
            },
            columns: [],
        };

        const $scope = {};

        highlightColumn($scope, artifact, kanban);

        expect(kanban.backlog.have_new_item).toBeUndefined();
    });

    it(`Given item is added in archive column and given column is closed
            Then it highlight backlog column`, () => {
        const artifact = {
            in_column: "archive",
        };

        const kanban = {
            archive: {
                is_open: false,
            },
            columns: [],
        };

        const $scope = {};

        highlightColumn($scope, artifact, kanban);

        expect(kanban.archive.have_new_item).toBe(true);
    });

    it(`Given item is added in archive column and given column is opened
            Then nothing is highlighted`, () => {
        const artifact = {
            in_column: "archive",
        };

        const kanban = {
            archive: {
                is_open: true,
            },
            columns: [],
        };

        const $scope = {};

        highlightColumn($scope, artifact, kanban);

        expect(kanban.archive.have_new_item).toBeUndefined();
    });

    it(`Given item is added in a column and given column is closed
            Then it highlight the column`, () => {
        const artifact = {
            in_column: "1233",
        };

        const kanban = {
            is_open: false,
            columns: [{ id: "1233", label: "Review", is_open: false }],
        };

        const $scope = {};

        highlightColumn($scope, artifact, kanban);

        expect(kanban.columns[0].have_new_item).toBe(true);
    });

    it(`Given item is added in a column and given column is opened
            Then nothing is highlighted`, () => {
        const artifact = {
            in_column: "1233",
        };

        const kanban = {
            is_open: false,
            columns: [{ id: "1233", label: "Review", is_open: true }],
        };

        const $scope = {};

        highlightColumn($scope, artifact, kanban);

        expect(kanban.columns[0].have_new_item).toBeUndefined();
    });
});
