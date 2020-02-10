/*
 * Copyright (c) Enalean, 2017-present. All Rights Reserved.
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

import angular from "angular";
import "angular-gettext";

import StaticOpenListFieldDirective from "./static-open-list-field-directive.js";
import UgroupsOpenListFieldDirective from "./ugroups-open-list-field-directive.js";
import UsersOpenListFieldDirective from "./users-open-list-field-directive.js";

export default angular
    .module("tuleap-artifact-modal-open-list-field", ["gettext"])
    .directive("tuleapArtifactModalStaticOpenListField", StaticOpenListFieldDirective)
    .directive("tuleapArtifactModalUgroupsOpenListField", UgroupsOpenListFieldDirective)
    .directive("tuleapArtifactModalUsersOpenListField", UsersOpenListFieldDirective).name;
