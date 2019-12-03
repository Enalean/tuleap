/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

export interface TemplateData {
    title: string;
    description: string;
    name: string;
    svg: string;
}

export interface ProjectProperties {
    shortname: string;
    label: string;
    is_public: boolean;
    allow_restricted?: boolean;
    xml_template_name: string;
}

export interface ProjectNameProperties {
    slugified_name: string;
    name: string;
    is_valid: boolean;
}
