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
    },
    user: {
        is_project_administrator: true,
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
            icon: "fa-fw fas fa-folder-open",
            open_in_new_tab: false,
            is_active: true,
        },
        {
            label: "Custom",
            href: "https://example.com",
            description: "",
            icon: "fa-fw fas fa-tlp-baseline",
            open_in_new_tab: true,
            is_active: false,
        },
    ],
};
