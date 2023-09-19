/**
 * Copyright (c) 2021-Present Enalean
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

export interface PromotedItem {
    readonly href: string;
    readonly label: string;
    readonly description: string;
}

export interface Tool {
    href: string;
    label: string;
    description: string;
    icon: string;
    open_in_new_tab: boolean;
    is_active: boolean;
    shortcut_id: string;
    readonly promoted_items?: ReadonlyArray<PromotedItem>;
}

interface InstanceVersionInformation {
    flavor_name: string;
    version_identifier: string;
    full_descriptive_version: string;
}

interface LogoInformation {
    logo_link_href: string;
    svg: {
        normal: string;
        small: string;
    } | null;
    legacy_png_href: {
        normal: string;
        small: string;
    } | null;
}

interface Internationalization {
    tools: string;
    homepage: string;
    project_administration: string;
    project_announcement: string;
    show_project_announcement: string;
    close_sidebar: string;
    open_sidebar: string;
}

interface ProjectPrivacy {
    are_restricted_users_allowed: boolean;
    project_is_public_incl_restricted: boolean;
    project_is_private: boolean;
    project_is_public: boolean;
    project_is_private_incl_restricted: boolean;
    explanation_text: string;
    privacy_title: string;
}

export interface Configuration {
    internationalization: Internationalization;
    project: {
        icon: string;
        name: string;
        href: string;
        administration_href: string;
        privacy: ProjectPrivacy;
        has_project_announcement: boolean;
        flags: { label: string; description: string }[];
        linked_projects: {
            label: string;
            is_in_children_projects_context: boolean;
            projects: {
                icon: string;
                name: string;
                href: string;
            }[];
        } | null;
    };
    user: {
        is_project_administrator: boolean;
        is_logged_in: boolean;
    };
    instance_information: {
        version: InstanceVersionInformation;
        copyright: string | null;
        logo: LogoInformation;
    };
    tools: Tool[];
    readonly is_collapsible?: boolean;
}

export function unserializeConfiguration(
    serialized_config: string | undefined,
): Configuration | undefined {
    if (serialized_config === undefined) {
        return undefined;
    }

    try {
        return JSON.parse(serialized_config);
    } catch (e) {
        return undefined;
    }
}
