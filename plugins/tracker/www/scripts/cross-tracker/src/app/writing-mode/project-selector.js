/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

import { recursiveGet } from 'tlp';
import { render } from 'mustache';
import { watch } from 'wrist';
import project_option_template from './project-option.mustache';

export default class ProjectSelector {
    constructor(
        widget_content,
        reading_cross_tracker_report,
        tracker_selection,
        error_displayer,
        loader_displayer
    ) {
        this.reading_cross_tracker_report = reading_cross_tracker_report;
        this.widget_content               = widget_content;
        this.tracker_selection            = tracker_selection;
        this.error_displayer              = error_displayer;
        this.loader_displayer             = loader_displayer;
        this.form_projects                = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-form-projects');
        this.projects_input               = this.widget_content.querySelector('.dashboard-widget-content-cross-tracker-form-projects-input');
        this.projects                     = new Map();
        this.projects_loaded              = false;

        this.translated_too_many_trackers_message = this.widget_content.querySelector('.project-selector-error').textContent;

        this.listenSelectElementChange();
        this.listenChangeMode();
        this.setDisabled();
    }

    async loadProjects() {
        try {
            this.loader_displayer.show();
            const json = await recursiveGet('/api/v1/projects/', {
                params: {
                    limit: 50,
                    query: {'is_member_of': true }
                }
            });

            const sorted_projects = json.sort(
                ({ label: label_a }, { label: label_b }) => label_a.localeCompare(label_b)
            );

            for (const { id, label } of sorted_projects) {
                this.projects.set(id.toString(), { id, label });
            }
            const projects_array = [...this.projects.values()];

            this.displayProjects(projects_array);
            this.setEnabled();
            this.tracker_selection.selected_project = projects_array[0];
        } catch (error) {
            this.error_displayer.displayError(this.translated_too_many_trackers_message);
        }
        this.loader_displayer.hide();
    }

    setDisabled() {
        this.projects_input.disabled = true;
        this.form_projects.classList.add('tlp-form-element-disabled');
    }

    setEnabled() {
        this.projects_input.disabled = false;
        this.form_projects.classList.remove('tlp-form-element-disabled');
    }

    displayProjects(projects) {
        this.projects_input.insertAdjacentHTML('beforeEnd', render(project_option_template, { projects }));
    }

    listenSelectElementChange() {
        const inputChanged = (property_name, old_value, new_value) => {
            this.tracker_selection.selected_project = this.projects.get(new_value);
        };

        watch(this.projects_input, 'value', inputChanged);
    }

    listenChangeMode() {
        const updateMode = (property_name, old_value, new_value) => {
            if (! new_value) {
                if (! this.projects_loaded) {
                    this.loadProjects();
                }
                this.projects_loaded = true;
            }
        };

        watch(this.reading_cross_tracker_report, 'reading_mode', updateMode);
    }
}
