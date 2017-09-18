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
import { modal, filterInlineTable } from 'tlp';
import { sprintf } from 'sprintf-js';
import { sanitize } from 'dompurify';

document.addEventListener('DOMContentLoaded', () => {
    const labels_table = document.getElementById('project-labels-table');
    if (! labels_table) {
        return;
    }

    const filter = document.getElementById('project-labels-table-filter');
    if (filter) {
        filterInlineTable(filter);
    }

    const buttons = document.querySelectorAll('.project-labels-table-delete-button, .project-labels-table-edit-button');
    for (const button of buttons) {
        const modal_element = document.getElementById(button.dataset.modalId);

        if (modal_element) {
            const la_modal = modal(modal_element);

            button.addEventListener('click', () => {
                const edit_name_input = modal_element.querySelector('.project-label-edit-name');
                if (edit_name_input) {
                    hideWarning(edit_name_input);
                    edit_name_input.value = edit_name_input.dataset.originalValue;
                }

                la_modal.toggle();
            });
        }
    }

    const existing_labels = JSON.parse(labels_table.dataset.existingLabelsNames);
    for (const input of document.querySelectorAll('.project-label-edit-name')) {
        input.addEventListener('input', onLabelChange);
    }

    let timer;
    function onLabelChange() {
        if (timer) {
            clearTimeout(timer);
        }

        timer = setTimeout(() => {
            if (existing_labels.indexOf(this.value) === -1) {
                hideWarning(this);
            } else if (this.value !== this.dataset.originalValue) {
                showWarning(this);
            }
        }, 150);
    }

    function hideWarning(input) {
        document.getElementById(input.dataset.targetCancelId).classList.remove('tlp-button-warning');
        document.getElementById(input.dataset.targetSaveId).classList.remove('tlp-button-warning');
        document.getElementById(input.dataset.targetWarningId).classList.remove('shown');
    }

    function showWarning(input) {
        document.getElementById(input.dataset.targetCancelId).classList.add('tlp-button-warning');
        document.getElementById(input.dataset.targetSaveId).classList.add('tlp-button-warning');

        const warning = document.getElementById(input.dataset.targetWarningId);
        warning.classList.add('shown');
        warning.innerHTML = sanitize(sprintf(input.dataset.warningMessage, input.value));
    }
});
