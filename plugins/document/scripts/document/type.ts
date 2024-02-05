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
import type { RestUser } from "./api/rest-querier";
import {
    TYPE_EMBEDDED,
    TYPE_EMPTY,
    TYPE_FILE,
    TYPE_FOLDER,
    TYPE_LINK,
    TYPE_WIKI,
} from "./constants";

export type FolderContentItem = Item | FakeItem;

export interface State {
    is_loading_folder: boolean;
    current_folder: Folder;
    folder_content: Array<FolderContentItem>;
    current_folder_ascendant_hierarchy: Array<Folder>;
    is_loading_ascendant_hierarchy: boolean;
    is_loading_currently_previewed_item: boolean;
    root_title: string;
    currently_previewed_item: Item | null;
    files_uploads_list: Array<ItemFile | FakeItem>;
    show_post_deletion_notification: boolean;
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
    readonly type: string; // Should use ItemType
    level?: number;
}

export const ItemType = [
    TYPE_FOLDER,
    TYPE_FILE,
    TYPE_EMBEDDED,
    TYPE_WIKI,
    TYPE_LINK,
    TYPE_EMPTY,
] as const;
export type ItemType = (typeof ItemType)[number];

export interface Item extends MinimalItem {
    description: string;
    post_processed_description: string;
    owner: User;
    last_update_date: Date | string | number;
    creation_date: string;
    user_can_write: boolean;
    user_can_delete: boolean;
    can_user_manage: boolean;
    lock_info: LockInfo | null;
    type: string;
    status: string | FolderStatus;
    created?: boolean;
    obsolescence_date: null | number;
    updated?: boolean;
    properties: Array<Property>;
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
    post_processed_value?: string | null;
    recursion?: string | null;
}

export interface ListValue {
    id: number;
    name: string | number;
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
    status: string;
}

export interface DefaultFileNewVersionItem extends DefaultFileItem {
    id: number;
}

export interface NewVersion {
    title: string;
    changelog: string;
    is_file_locked?: boolean;
}

export interface Uploadable {
    progress: number | null;
    upload_error: string | null;
    is_uploading: boolean;
    uploader?: Upload;
    is_uploading_new_version: boolean;
    is_uploading_in_collapsed_folder: boolean;
}

export interface FakeItem extends Uploadable {
    id: number;
    title: string;
    parent_id: number | null;
    type: string; // Should use ItemType
    last_update_date?: Date;
    file_type?: string;
    has_approval_table: boolean;
    is_approval_table_enabled: boolean;
    approval_table: ApprovalTable | null;
    upload_error: string | null;
    is_uploading_new_version: boolean;
}

export interface Folder extends Item, Uploadable {
    is_expanded: boolean;
    permissions_for_groups: Permissions;
    folder_properties: FolderProperties;
    type: "folder";
    properties: Array<Property>;
    status: FolderStatus;
}

export interface ApprovableDocument extends Item {
    has_approval_table: boolean;
    is_approval_table_enabled: boolean;
    approval_table: ApprovalTable | null;
}

export interface ItemFile extends Item, ApprovableDocument, Uploadable {
    parent_id: number;
    file_properties: FileProperties | null;
    type: "file";
    name?: string;
    size?: number;
    status: string;
    properties: Array<Property>;
}

export interface Link extends Item, ApprovableDocument {
    parent_id: number;
    link_properties: LinkProperties;
    type: "link";
    status: string;
    properties: Array<Property>;
}

export interface Embedded extends Item, ApprovableDocument {
    parent_id: number;
    embedded_file_properties: EmbeddedProperties | null;
    type: "embedded";
    status: string;
    properties: Array<Property>;
}

export interface Wiki extends Item, ApprovableDocument {
    parent_id: number;
    wiki_properties: WikiProperties;
    type: "wiki";
    status: string;
    properties: Array<Property>;
}

export interface Empty extends Item, Uploadable {
    parent_id: number;
    approval_table: ApprovalTable | null;
    type: "empty";
    status: string;
    properties: Array<Property>;
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
    user_url: string;
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
    file_name: string;
    file_size: number;
    download_href: string;
    open_href: string | null;
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
    readonly version_number: number;
    readonly content?: string;
    file_type: string;
}

export interface WikiProperties {
    page_name: string;
    page_id: number | null;
}

export interface ItemReferencingWikiPageRepresentation {
    readonly item_id: number;
    readonly item_name: string;
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
export type AllowedSearchType = (typeof AllowedSearchType)[number];

export const AllowedSearchDateOperator = [">", "=", "<"];
export type AllowedSearchDateOperator = (typeof AllowedSearchDateOperator)[number];

export interface SearchDate {
    readonly operator: AllowedSearchDateOperator;
    readonly date: string;
}

export type AdditionalFieldNumber = `field_${number}`;

export interface AdvancedSearchParams {
    global_search: string;
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

    readonly [key: AdditionalFieldNumber]: string | SearchDate | null | undefined;

    readonly sort: SortParams | null;
}

export interface SortParams {
    readonly name: string;
    readonly order: string;
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
    | (typeof HardcodedPropertyName)[number]
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
    readonly sort?: Array<SortParams>;
}

export interface SearchResultColumnDefinition {
    readonly name: string;
    readonly label: string;
    readonly is_multiple_value_allowed: boolean;
}

export type ListOfSearchResultColumnDefinition = ReadonlyArray<SearchResultColumnDefinition>;

export interface FileHistory {
    readonly id: number;
    readonly number: number;
    readonly name: string;
    readonly changelog: string;
    readonly filename: string;
    readonly download_href: string;
    readonly approval_href: string | null;
    readonly date: string;
    readonly author: RestUser;
    readonly coauthors: RestUser[];
    readonly authoring_tool: string;
}

export interface LinkVersion {
    readonly id: number;
    readonly number: number;
    readonly name: string;
    readonly changelog: string;
    readonly date: string;
    readonly author: RestUser;
    readonly link_href: string;
}

export interface EmbeddedFileVersion {
    readonly id: number;
    readonly number: number;
    readonly name: string;
    readonly changelog: string;
    readonly open_href: string;
    readonly approval_href: string | null;
    readonly date: string;
    readonly author: RestUser;
}

export interface EmbeddedFileSpecificVersionContent {
    readonly version_number: number;
    readonly content: string;
}

export interface DocumentJsonError {
    error: JsonError;
}

export interface JsonError {
    message: string;
    i18n_error_message: string;
}

export interface Reason {
    nb_dropped_files?: number;
    lock_owner?: User;
    filename?: string;
}

export interface FeedbackHandler {
    success: (feedback: string | null) => void;
}

export type NewItemAlternativeArray = readonly NewItemAlternativeSection[];

export interface NewItemAlternativeSection {
    readonly title: string;
    readonly alternatives: readonly NewItemAlternative[];
}

export interface NewItemAlternative {
    readonly mime_type: string;
    readonly title: string;
}
