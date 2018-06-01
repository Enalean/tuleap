/**
 * Copyright (c) 2018, Enalean. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

import Vue                 from 'vue';
import GetTextPlugin       from 'vue-gettext';
import french_translations from './po/fr.po';
import StepDefinitionField from './StepDefinitionField.vue';

const StepDefinitionFieldComponent = Vue.extend(StepDefinitionField);

document.addEventListener('DOMContentLoaded', () => {
    Vue.use(GetTextPlugin, {
        translations: {
            fr: french_translations.messages
        },
        silent: true
    });

    Vue.config.language = document.body.dataset.userLocale;

    for (const mount_point of document.querySelectorAll('.ttm-definition-step-mount-point')) {
        new StepDefinitionFieldComponent({
            propsData: {
                steps: JSON.parse(mount_point.dataset.steps),
                fieldId: JSON.parse(mount_point.dataset.fieldId),
                emptyStep: JSON.parse(mount_point.dataset.emptyStep)
            }
        }).$mount(mount_point);
    }
});
