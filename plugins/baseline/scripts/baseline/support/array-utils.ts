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
 *
 */

const mapAttribute = <T>(values: Record<string, T>[], attribute: string): T[] => {
    return values.reduce((accumulator: T[], value) => {
        if (attribute in value) {
            accumulator.push(value[attribute]);
        }
        return accumulator;
    }, []);
};

const unique = <T>(values: T[]): T[] => [...new Set(values)];

const uniqueByAttribute = <T>(
    values: Record<string, T>[],
    attribute: string,
): Record<string, T>[] => {
    const attributes: T[] = unique(mapAttribute(values, attribute));
    return attributes.reduce((accumulator: Record<string, T>[], attribute_value) => {
        const value = values.find((value) => value[attribute] === attribute_value);
        if (value !== undefined) {
            accumulator.push(value);
        }
        return accumulator;
    }, []);
};

export default { unique, uniqueByAttribute, mapAttribute };
