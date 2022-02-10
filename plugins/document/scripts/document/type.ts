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
import type { PermissionsState } from "./store/permissions/permissions-default-state";
import type {
    FolderMetadata,
    FolderStatus,
    Metadata,
    MetadataState,
} from "./store/metadata/module";

export interface State {
    is_loading_folder: boolean;
    current_folder: Folder;
    folder_content: Array<Item | FakeItem>;
    current_folder_ascendant_hierarchy: Array<Folder>;
    root_title: string;
    currently_previewed_item: Item | null;
}

export interface RootState extends State {
    readonly configuration: ConfigurationState;
    error: ErrorState;
    permissions: PermissionsState;
    metadata: MetadataState;
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

export interface SearchResult {
    readonly from: number;
    readonly to: number;
    readonly total: number;
    readonly items: ReadonlyArray<ItemSearchResult>;
}

export interface ItemSearchResult {
    readonly id: number;
    readonly type: string;
    readonly title: string;
    readonly post_processed_description: string;
    readonly owner: User;
    readonly last_update_date: string;
    readonly parents: ReadonlyArray<{
        readonly id: number;
        readonly title: string;
    }>;
    readonly file_properties: FileProperties | null;
}

export const SEARCH_LIMIT = 50;

export interface Item {
    id: number;
    title: string;
    description: string;
    post_processed_description: string;
    owner: User;
    last_update_date: Date | string | number;
    creation_date: string;
    user_can_write: boolean;
    can_user_manage: boolean;
    lock_info: LockInfo | null;
    metadata: Array<Metadata> | Array<FolderMetadata>;
    parent_id: number | null;
    type: string;
    status: string | FolderStatus;
    created?: boolean;
    obsolescence_date: null | number;
}

export interface FakeItem extends Item {
    progress: number | null;
    level: number;
    upload_error: string | null;
    is_uploading?: boolean;
    is_uploading_new_version?: boolean;
}

export interface Folder extends Item {
    is_expanded: boolean;
    permissions_for_groups: Permissions;
    folder_properties: FolderProperties;
    type: "folder";
    metadata: Array<FolderMetadata>;
    status: FolderStatus;
}

export interface ApprovableDocument extends Item {
    has_approval_table: boolean;
    is_approval_table_enabled: boolean;
    approval_table: ApprovalTable | null;
}

export interface ItemFile extends Item, ApprovableDocument {
    parent_id: number;
    file_properties: FileProperties | null;
    type: "file";
    is_uploading_in_collapsed_folder: boolean;
    is_uploading: boolean;
    is_uploading_new_version: boolean;
    name?: string;
    size?: number;
    uploader?: FileUploader;
    level?: number;
    status: string;
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
    status: string;
}

export interface Embedded extends Item, ApprovableDocument {
    parent_id: number;
    embedded_file_properties: EmbeddedProperties;
    type: "embedded";
    status: string;
}

export interface Wiki extends Item, ApprovableDocument {
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
    apply_permissions_on_children: boolean;
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
    upload_href?: string;
    level?: number;
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
    label: string;
}

export const AllowedSearchType = [
    "",
    "folder",
    "file",
    "embedded",
    "wiki",
    "link",
    "empty",
] as const;
export type AllowedSearchType = typeof AllowedSearchType[number];

export interface AdvancedSearchParams {
    readonly query: string;
    readonly type: AllowedSearchType;
    readonly title: string;
    readonly description: string;
}
