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

export function highlightColumn($scope, artifact, kanban) {
    if (artifact.in_column === "backlog" && !kanban.backlog.is_open) {
        hightligh($scope, kanban.backlog);
    } else if (artifact.in_column === "archive" && !kanban.archive.is_open) {
        hightligh($scope, kanban.archive);
    } else {
        const artifact_column = kanban.columns.find(function (column) {
            return column.id === artifact.in_column;
        });

        if (artifact_column && !artifact_column.is_open) {
            hightligh($scope, artifact_column);
        }
    }
}

function hightligh($scope, collection) {
    collection.have_new_item = true;
    setTimeout(() => {
        collection.have_new_item = false;
        $scope.$apply();
    }, 2000);
}
