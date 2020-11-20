/*
 * Copyright (c) Enalean, 2020-present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

export interface RepositoryFineGrainedPermissions {
    name: string;
    has_fined_grained_permissions: boolean;
    fine_grained_permission: FineGrainedPermission[];
    url: string;
    repository_id: number;
    readers: PermissionGroup[];
}

export interface RepositorySimplePermissions {
    name: string;
    has_fined_grained_permissions: boolean;
    url: string;
    repository_id: number;
    readers: PermissionGroup[];
    rewinders: PermissionGroup[];
    writers: PermissionGroup[];
}

export interface FineGrainedPermission {
    id: number;
    branch: string;
    tag: string;
    writers: PermissionGroup[];
    rewinders: PermissionGroup[];
}

export interface PermissionGroup {
    is_project_admin: boolean;
    is_static: boolean;
    is_custom: boolean;
    ugroup_name: string;
}
