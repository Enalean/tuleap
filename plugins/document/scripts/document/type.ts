/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import type { ConfigurationState } from "./store/configuration";
import type { ErrorState } from "./store/error/module";

export interface State {
    is_loading_folder: boolean;
}

export interface RootState extends State {
    readonly configuration: ConfigurationState;
    error: ErrorState;
}

export type Direction = "BOTTOM" | "TOP" | "NEXT" | "PREVIOUS";
export const BOTTOM: Direction = "BOTTOM";
export const TOP: Direction = "TOP";
export const NEXT: Direction = "NEXT";
export const PREVIOUS: Direction = "PREVIOUS";

export interface GettextProvider {
    $gettext: (msgid: string) => string;
    $pgettext: (context: string, msgid: string) => string;
}

export interface Metadata {
    short_name: string;
    name: string;
    description: string | null;
    type: string;
    is_required: boolean;
    is_multiple_value_allowed: boolean;
    is_used: boolean;
    list_value: Array<number> | Array<ListValue> | null | [];
    value: number | string | null;
}

export interface FolderMetadata extends Metadata {
    recursion: string | null;
}

/**
 * Note of metadata usage:
 *
 * For single and multiple list when data comes from rest route, list_value has Array<ListValue>
 * For single metadata, after transformation, list_value is null, value is a number (chosen option)
 * For multiple value metadata, after transformation, value is null, list value is and Array<number>
 *
 * Please also note that value is used for dates/string
 */
export interface ListValue {
    id: number;
    value: string | number;
}

export interface Item {
    id: number;
    title: string;
    description: string;
    post_processed_description: string;
    owner: User;
    last_update_date: string;
    creation_date: string;
    user_can_write: boolean;
    can_user_manage: boolean;
    lock_info: LockInfo;
    metadata: Array<Metadata> | Array<FolderMetadata>;
    parent_id: number | null;
    type: string;
    status: string | FolderStatus;
}

export interface Folder extends Item {
    is_expanded: boolean;
    permissions_for_groups: Permissions;
    folder_properties: FolderProperties;
    type: "folder";
    metadata: Array<FolderMetadata>;
    status: FolderStatus;
}

export interface FolderStatus {
    value: string;
    recursion: string;
}

export interface ApprovableDocument extends Item {
    has_approval_table: boolean;
    is_approval_table_enabled: boolean;
    approval_table: ApprovalTable | null;
}

export interface ItemFile extends Item, ApprovableDocument {
    parent_id: number;
    file_properties: FileProperties;
    type: "file";
    is_uploading_in_collapsed_folder: boolean;
    is_uploading: boolean;
    is_uploading_new_version: boolean;
    name?: string;
    size?: number;
    uploader?: FileUploader;
}

export interface ItemFileUploader extends ItemFile {
    uploader: FileUploader;
}

export interface FileUploader {
    url: string;
}

export interface Link extends Item, ApprovableDocument {
    parent_id: number;
    link_properties: LinkProperties;
    type: "link";
}

export interface Embedded extends Item, ApprovableDocument {
    parent_id: number;
    embedded_file_properties: EmbeddedProperties;
    type: "embedded";
}

export interface Wiki extends Item {
    parent_id: number;
    wiki_properties: WikiProperties;
    type: "wiki";
    status: string;
}

export interface Empty extends Item {
    parent_id: number;
    approval_table: ApprovalTable | null;
    type: "empty";
    status: string;
}

export interface LockInfo {
    lock_date: string;
    lock_by: User;
}

export interface User {
    id: number;
    display_name: string;
    has_avatar: boolean;
    avatar_url: string;
}

export interface Permissions {
    can_read: Array<Permission>;
    can_write: Array<Permission>;
    can_manage: Array<Permission>;
}

export interface AdminPermissions {
    can_admin: Array<Permission>;
}

export interface Permission {
    id: string;
    key: string;
    label: string;
    short_name: string;
    uri: string;
    users_uri: string;
}

export interface ApprovalTable {
    id: number;
    table_owner: User;
    approval_state: string;
    approval_request_date: string;
    has_been_approved: boolean;
}

export interface FolderProperties {
    total_size: number;
    nb_files: number;
}

export interface FileProperties {
    file_type: string;
    download_href: string;
    file_size: number;
}

export interface LinkProperties {
    link_url: string;
}

export interface EmbeddedProperties {
    file_type: string;
}

export interface WikiProperties {
    page_name: string;
    page_id: number | null;
}

export interface UserGroup {
    id: string;
}
