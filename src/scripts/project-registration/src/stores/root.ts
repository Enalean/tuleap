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

import type { RootState } from "./type";
import { defineStore } from "pinia";
import type { TemplateData, ProjectProperties, AdvancedOptions } from "../type";
import { getProjectUserIsAdminOf, postProject } from "../api/rest-querier";
import { FetchWrapperError } from "@tuleap/tlp-fetch";
import { uploadFile } from "../helpers/upload-file";
import type { ProjectArchiveReference, ProjectReference } from "@tuleap/core-rest-api-types";
import type VueRouter from "vue-router";

export const useStore = defineStore("root", {
    state: (): RootState => ({
        tuleap_templates: [],
        external_templates: [],
        are_restricted_users_allowed: false,
        project_default_visibility: "",
        is_project_approval_required: true,
        trove_categories: [],
        is_description_required: false,
        project_fields: [],
        company_templates: [],
        company_name: "",
        can_user_choose_project_visibility: true,
        selected_tuleap_template: null,
        selected_company_template: null,
        selected_template_category: null,
        projects_user_is_admin_of: [],
        error: null,
        is_creating_project: false,
        selected_advanced_option: null,
        can_create_from_project_file: false,
    }),
    getters: {
        is_template_selected: (state: RootState): boolean =>
            state.selected_tuleap_template !== null || state.selected_company_template !== null,

        is_currently_selected_template:
            (state: RootState) =>
            (template: TemplateData): boolean => {
                if (state.selected_company_template !== null) {
                    return state.selected_company_template.id === template.id;
                }

                if (state.selected_tuleap_template !== null) {
                    return state.selected_tuleap_template.id === template.id;
                }

                return false;
            },

        has_error: (state: RootState): boolean => state.error !== null,

        is_advanced_option_selected:
            (state: RootState) =>
            (option: AdvancedOptions | null): boolean => {
                return state.selected_advanced_option === option;
            },
    },

    actions: {
        setSelectedTemplate(selected_template: TemplateData): void {
            if (selected_template.is_built_in) {
                this.selected_tuleap_template = selected_template;
                this.selected_company_template = null;
            } else {
                this.selected_tuleap_template = null;
                this.selected_company_template = selected_template;
            }
        },

        async createProjectFromArchive(
            project_properties: ProjectProperties,
            router: VueRouter,
        ): Promise<ProjectReference | ProjectArchiveReference> {
            let response;
            try {
                this.setIsCreatingProject(true);
                response = await postProject(project_properties);
                if (
                    this.selected_company_template !== null &&
                    "upload_href" in response &&
                    "archive" in this.selected_company_template
                ) {
                    uploadFile(
                        this.selected_company_template.archive,
                        response.upload_href,
                        router,
                        this.setIsCreatingProject,
                    );
                }
            } catch (error) {
                if (error instanceof FetchWrapperError) {
                    await this.handleError(error);
                }
                throw error;
            }
            return response;
        },

        async createProject(
            project_properties: ProjectProperties,
        ): Promise<ProjectReference | ProjectArchiveReference> {
            let response;

            try {
                this.setIsCreatingProject(true);
                response = await postProject(project_properties);
            } catch (error) {
                if (error instanceof FetchWrapperError) {
                    await this.handleError(error);
                }
                throw error;
            } finally {
                this.setIsCreatingProject(false);
            }

            return response;
        },

        async loadUserProjects(): Promise<void> {
            const projects_user_is_admin_of = await getProjectUserIsAdminOf();
            this.setAvailableProjectsUserIsAdminOf(projects_user_is_admin_of);
        },

        resetSelectedTemplate(): void {
            this.selected_tuleap_template = null;
            this.selected_company_template = null;
            this.selected_advanced_option = null;
        },

        resetProjectCreationError(): void {
            this.error = null;
        },

        setIsCreatingProject(is_creating_project: boolean): void {
            this.error = null;
            this.is_creating_project = is_creating_project;
        },

        async handleError(rest_error: FetchWrapperError): Promise<void> {
            try {
                const { error } = await rest_error.response.json();
                this.error = error.message;
            } catch (e) {
                this.error = "Internal server error";
                throw e;
            }
        },

        resetError(): void {
            this.error = null;
        },

        setSelectedTemplateCategory(selected_template_category: string): void {
            this.selected_template_category = selected_template_category;
        },

        setAvailableProjectsUserIsAdminOf(projects_user_is_admin_of: TemplateData[]): void {
            this.projects_user_is_admin_of = projects_user_is_admin_of;
        },

        setAdvancedActiveOption(option: AdvancedOptions | null): void {
            this.selected_advanced_option = option;
        },
    },
});
