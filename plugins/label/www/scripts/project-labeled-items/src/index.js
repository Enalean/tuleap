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

import Vue from 'vue';
import LabeledItemsList from './LabeledItemsList.vue';
import { create } from './labels-box';

const widgets = document.getElementsByClassName("labeled-items-widget");

for (const widget of widgets) {
    new Vue({
        el: widget,
        components: {LabeledItemsList}
    }).$mount();
}

document.addEventListener('DOMContentLoaded', function () {
    for (const element of document.querySelectorAll('.dashboard-widget-content-projectlabeleditems')) {
        const widget_container = element.parentNode,
            edit_button      = widget_container.querySelector('.edit-widget-button'),
            modal_edit       = widget_container.querySelector(`#${edit_button.dataset.targetModalId}`);

        modal_edit.addEventListener('edit-widget-modal-content-loaded', () => {
            initEditModalContent(widget_container);
        });
    }
});

function initEditModalContent(widget_container) {
    const container     = widget_container.querySelector('.project-labels');
    let selected_labels = [];

    for (const option of container.options) {
        selected_labels.push({
            id: option.value, text: option.dataset.name, is_outline: option.dataset.isOutline, color: option.dataset.color
        });
    }
    create(container, container.dataset.labelsEndpoint, selected_labels);
}
