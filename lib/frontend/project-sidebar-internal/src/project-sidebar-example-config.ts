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

import type { Configuration } from "./configuration";

export const example_config: Configuration = {
    internationalization: {
        tools: "Tools",
        homepage: "Homepage",
        project_administration: "Project administration",
        project_announcement: "Project announcement",
        show_project_announcement: "Show project announcement",
        close_sidebar: "Close sidebar",
        open_sidebar: "Open sidebar",
    },
    project: {
        icon: "🌷",
        name: "project1",
        href: "/projects/project1",
        administration_href: "https://myinstance.example.com/project/admin/?group_id=999",
        privacy: {
            are_restricted_users_allowed: true,
            project_is_public: true,
            project_is_public_incl_restricted: false,
            project_is_private: false,
            project_is_private_incl_restricted: false,
            explanation_text:
                "Project privacy set to public. By default, its content is available to all authenticated, but not restricted, users. Please note that more restrictive permissions might exist on some items.",
            privacy_title: "Public",
        },
        has_project_announcement: true,
        flags: [
            {
                label: "Label A",
                description: "Some label description A",
            },
            {
                label: "Label B",
                description: "Some label description B",
            },
        ],
        linked_projects: {
            label: "2 aggregated projects",
            is_in_children_projects_context: true,
            projects: [
                {
                    icon: "",
                    name: "Team A",
                    href: "https://myinstance.example.com/projects/teama",
                },
                {
                    icon: "🏡",
                    name: "Team B",
                    href: "https://myinstance.example.com/projects/teamb",
                },
            ],
        },
    },
    user: {
        is_project_administrator: true,
        is_logged_in: true,
    },
    instance_information: {
        version: {
            flavor_name: "Tuleap Community Edition",
            version_identifier: "Dev Build 13.2.99.999",
            full_descriptive_version: "Tuleap Community Edition — Dev Build 13.2.99.999",
        },
        copyright: "ACME",
        logo: {
            logo_link_href: "https://myinstance.example.com/",
            svg: null,
            legacy_png_href: null,
        },
    },
    tools: [
        {
            label: "Service A",
            href: "/service/a",
            description: "Description service A",
            icon: "fa-fw fa-solid fa-folder-open",
            open_in_new_tab: false,
            is_active: true,
            shortcut_id: "",
            promoted_items: [
                {
                    href: "/service/a/release-a",
                    label: "Release A",
                    description: "Description of release A",
                    is_active: true,
                    quick_link_add: {
                        href: "/service/a/release-a/add",
                        label: "Add",
                    },
                    items: [
                        {
                            href: "/service/a/release-a/sprint-w12",
                            label: "Sprint W12",
                            description: "Description of sprint W12",
                            is_active: true,
                        },
                        {
                            href: "/service/a/release-a/sprint-w11",
                            label: "Sprint W11",
                            description: "Description of sprint W11",
                            is_active: false,
                            quick_link_add: {
                                href: "/service/a/release-a/sprint-w11/add",
                                label: "Add",
                            },
                        },
                    ],
                },
                {
                    href: "/service/a/release-b",
                    label: "Release B",
                    description: "Description of release B",
                    is_active: false,
                    quick_link_add: {
                        href: "/service/a/release-b/add",
                        label: "Add",
                    },
                },
            ],
            info_tooltip: "Sidebar shows only the last 5 open milestones",
        },
        {
            label: "Git",
            href: "/service/fake_git",
            description: "Fake Git",
            icon: "fa-fw fa-solid fa-tlp-versioning-git",
            open_in_new_tab: false,
            is_active: true,
            shortcut_id: "plugin_git",
            promoted_items: [
                {
                    href: "/service/fake_git/fake_repo1",
                    label: "Repository 1",
                    description: "Awesome repository",
                    is_active: false,
                },
                {
                    href: "/service/fake_git/fake_repo2",
                    label: "Repository 2",
                    description: "Another awesome repository",
                    is_active: true,
                    quick_link_add: {
                        href: "/service/fake_git/fake_repo2/add",
                        label: "Add",
                    },
                },
                {
                    href: "/service/fake_git/fake_repo3",
                    label: "Repository 3",
                    description: "Yet another awesome repository",
                    is_active: false,
                    quick_link_add: {
                        href: "/service/fake_git/fake_repo3/add",
                        label: "Add",
                    },
                },
            ],
        },
        {
            label: "Custom",
            href: "https://example.com",
            description: "",
            icon: "fa-fw fa-solid fa-tlp-baseline",
            open_in_new_tab: true,
            is_active: false,
            shortcut_id: "",
        },
        {
            label: "Brand",
            href: "https://example.com",
            description: "",
            icon: "fa-fw fa-brands fa-figma",
            open_in_new_tab: false,
            is_active: false,
            shortcut_id: "",
        },
    ],
};
