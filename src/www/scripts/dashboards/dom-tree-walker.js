/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

export { findClosest, findAncestor };

function findClosest(element, classname) {
    if (hasClassNamed(element, classname)) {
        return element;
    }

    return findAncestor(element, classname);
}

function findAncestor(element, classname) {
    while ((element = element.parentElement) && ! hasClassNamed(element, classname)) {}
    return element;
}

function hasClassNamed(element, classname) {
    if (element.classList) {
        return element.classList.contains(classname);
    // IE11 SVG elements don't have classList
    } else if (element.getAttribute('class')) {
        return (element.getAttribute('class').indexOf(classname) !== -1);
    }
}
