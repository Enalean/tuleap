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

const find = (values, predicate) => values.filter(predicate)[0];

const mapAttribute = (values, attribute) =>
    values.map((value) => value[attribute] || null).filter(Boolean);

const unique = (values) => [...new Set(values)];

const uniqueByAttribute = (values, attribute) =>
    unique(mapAttribute(values, attribute)).map((attribute_value) =>
        find(values, (value) => value[attribute] === attribute_value)
    );

const clone = (values) =>
    values.map((value) => {
        return { ...value };
    });

export default { find, unique, uniqueByAttribute, clone, mapAttribute };
