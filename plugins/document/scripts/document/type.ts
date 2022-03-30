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
import type { PropertiesState } from "./store/properties/module";
import type { Upload } from "tus-js-client";

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
    properties: PropertiesState;
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

export interface CustomPropertySearchResultDate {
    readonly type: "date";
    readonly value: string | null;
}

export interface CustomPropertySearchResultString {
    readonly type: "string";
    readonly value: string;
}

export interface CustomPropertySearchResultList {
    readonly type: "list";
    readonly values: ReadonlyArray<string>;
}

export type CustomPropertySearchResult =
    | CustomPropertySearchResultDate
    | CustomPropertySearchResultString
    | CustomPropertySearchResultList;

export interface ItemSearchResult {
    readonly id: number;
    readonly type: string;
    readonly title: string;
    readonly status: string | null;
    readonly post_processed_description: string;
    readonly owner: User;
    readonly last_update_date: string;
    readonly creation_date: string | null;
    readonly obsolescence_date: string | null;
    readonly parents: ReadonlyArray<{
        readonly id: number;
        readonly title: string;
    }>;
    readonly file_properties: FileProperties | null;
    readonly custom_properties: {
        readonly [key: AdditionalFieldNumber]: CustomPropertySearchResult;
    };
}

export const SEARCH_LIMIT = 50;

interface MinimalItem {
    readonly id: number;
    readonly title: string;
    readonly parent_id: number | null;
    readonly type: string;
    level?: number;
}

export interface Item extends MinimalItem {
    description: string;
    post_processed_description: string;
    owner: User;
    last_update_date: Date | string | number;
    creation_date: string;
    user_can_write: boolean;
    can_user_manage: boolean;
    lock_info: LockInfo | null;
    type: string;
    status: string | FolderStatus;
    created?: boolean;
    obsolescence_date: null | number;
    properties: Array<Property> | Array<FolderProperty>;
}

/**
 * Note of properties usage:
 *
 * For single and multiple list when data comes from rest route, list_value has Array<ListValue>
 * For single property, after transformation, list_value is null, value is a number (chosen option)
 * For multiple value property, after transformation, value is null, list value is and Array<number>
 *
 * Please also note that value is used for dates/string
 */
export interface Property {
    short_name: string;
    name: string;
    description: string | null;
    type: string;
    is_required: boolean;
    is_multiple_value_allowed: boolean;
    is_used: boolean;
    list_value: Array<number> | Array<ListValue> | null | [];
    value: number | string | null;
    allowed_list_values: Array<ListValue> | null;
}

export interface FolderProperty extends Property {
    recursion: string | null;
}

export interface ListValue {
    id: number;
    value: string | number;
}

export interface FolderStatus {
    value: string;
    recursion: string;
}

export interface DefaultFileProperties {
    file: File | Record<string, never>;
}

export interface DefaultFileItem {
    title: string;
    description: string;
    type: "file";
    file_properties: DefaultFileProperties;
}

export interface FakeItem extends MinimalItem {
    last_update_date?: Date;
    progress: number | null;
    upload_error: string | null;
    is_uploading?: boolean;
    uploader?: Upload;
    file_type?: string;
    is_uploading_new_version?: boolean;
}

export interface Folder extends Item {
    is_expanded: boolean;
    permissions_for_groups: Permissions;
    folder_properties: FolderProperties;
    type: "folder";
    properties: Array<FolderProperty>;
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
    level?: number;
}

export interface CreatedItem {
    readonly id: number;
    readonly uri: string;
    readonly file_properties: CreatedItemFileProperties | null;
}

export interface CreatedItemFileProperties {
    readonly upload_href: string;
}

export interface LinkProperties {
    link_url: string;
}

export interface EmbeddedProperties {
    readonly content?: string;
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

export const AllowedSearchDateOperator = [">", "=", "<"];
export type AllowedSearchDateOperator = typeof AllowedSearchDateOperator[number];

export interface SearchDate {
    readonly operator: AllowedSearchDateOperator;
    readonly date: string;
}

export type AdditionalFieldNumber = `field_${number}`;

export interface AdvancedSearchParams {
    readonly global_search: string;
    readonly id: string;
    readonly type: AllowedSearchType;
    readonly filename: string;
    readonly title: string;
    readonly description: string;
    readonly owner: string;
    readonly create_date: SearchDate | null;
    readonly update_date: SearchDate | null;
    readonly obsolescence_date: SearchDate | null;
    readonly status: string;
    readonly [key: AdditionalFieldNumber]: string | SearchDate | undefined;
}

interface BaseSearchCriterion {
    readonly name: string;
    readonly label: string;
}

export interface SearchCriterionDate extends BaseSearchCriterion {
    readonly type: "date";
}

export interface SearchCriterionOwner extends BaseSearchCriterion {
    readonly type: "owner";
}

export interface SearchCriterionText extends BaseSearchCriterion {
    readonly type: "text";
}

export interface SearchListOption {
    readonly value: string;
    readonly label: string;
}

export interface SearchCriterionList extends BaseSearchCriterion {
    readonly type: "list";
    readonly options: ReadonlyArray<SearchListOption>;
}

export type SearchCriterion = SearchCriterionDate | SearchCriterionText | SearchCriterionList;

export type SearchCriteria = ReadonlyArray<SearchCriterion>;

export const HardcodedPropertyName = [
    "id",
    "type",
    "filename",
    "title",
    "description",
    "owner",
    "create_date",
    "update_date",
    "obsolescence_date",
    "status",
] as const;
export type AllowedSearchBodyPropertyName =
    | typeof HardcodedPropertyName[number]
    | AdditionalFieldNumber;

interface SearchBodyProperty {
    readonly name: AllowedSearchBodyPropertyName;
}

export interface SearchBodyPropertySimple extends SearchBodyProperty {
    readonly value: string;
}

export interface SearchBodyPropertyDate extends SearchBodyProperty {
    readonly value_date: SearchDate;
}

export type ListOfSearchBodyProperties = ReadonlyArray<
    SearchBodyPropertySimple | SearchBodyPropertyDate
>;

export interface SearchBodyRest {
    readonly global_search?: string;
    readonly properties?: ListOfSearchBodyProperties;
}

export interface SearchResultColumnDefinition {
    readonly name: string;
    readonly label: string;
}

export type ListOfSearchResultColumnDefinition = ReadonlyArray<SearchResultColumnDefinition>;
