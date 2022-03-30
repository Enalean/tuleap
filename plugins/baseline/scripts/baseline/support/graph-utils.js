/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

/**
 * @param current_found_nodes Allows to transmit positive results by the recursive algorithm
 */
const filter = (nodes, branch_attribute, predicate, current_found_nodes = []) => {
    let found_nodes = current_found_nodes;
    nodes.forEach((value) => {
        predicate(value) && found_nodes.push(value);
        found_nodes = filter(value[branch_attribute], branch_attribute, predicate, found_nodes);
    });

    return found_nodes;
};

export default { findAllNodes: filter };
