/*
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

export function calculatePosition(
    svg: SVGSVGElement,
    element: SVGGraphicsElement,
    x: number,
    y: number,
    width: number,
    height: number,
): { x: number; y: number } {
    let new_x = x;
    let new_y = y;
    let is_x_changed = false;
    let is_y_changed = false;

    const relative_point = getRelativeXY(svg, element, x, y);

    if (relative_point.x < 10) {
        new_x = 10;
        is_x_changed = true;
    } else if (relative_point.x > width - 10) {
        new_x = width - 10;
        is_x_changed = true;
    }

    if (relative_point.y < 10) {
        new_y = 10;
        is_y_changed = true;
    } else if (relative_point.y > height - 10) {
        new_y = height - 10;
        is_y_changed = true;
    }

    if (is_x_changed) {
        new_x = getTransformedX(svg, element, new_x).x;
    }

    if (is_y_changed) {
        new_y = getTransformedY(svg, element, new_y).y;
    }

    return { x: new_x, y: new_y };
}

function getRelativeXY(
    svg: SVGSVGElement,
    element: SVGGraphicsElement,
    x: number,
    y: number,
): DOMPoint {
    const point = svg.createSVGPoint();
    point.x = x;
    point.y = y;

    const element_coordinate_system = element.getCTM();
    if (!element_coordinate_system) {
        return point;
    }

    return point.matrixTransform(element_coordinate_system);
}

function getTransformedX(svg: SVGSVGElement, element: SVGGraphicsElement, x: number): DOMPoint {
    const point = svg.createSVGPoint();
    point.x = x;

    const element_coordinate_system = element.getCTM();
    if (!element_coordinate_system) {
        return point;
    }
    const element_coordinate_system_inverse = element_coordinate_system.inverse();

    return point.matrixTransform(element_coordinate_system_inverse);
}

function getTransformedY(svg: SVGSVGElement, element: SVGGraphicsElement, y: number): DOMPoint {
    const point = svg.createSVGPoint();
    point.y = y;

    const element_coordinate_system = element.getCTM();
    if (!element_coordinate_system) {
        return point;
    }
    const element_coordinate_system_inverse = element_coordinate_system.inverse();

    return point.matrixTransform(element_coordinate_system_inverse);
}
