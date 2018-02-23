/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

import Vue                  from 'vue';
import LabeledItemsList     from './LabeledItemsList.vue';
import { gettext_provider } from './gettext-provider.js';

document.addEventListener('DOMContentLoaded', () => {
    const locale = document.body.dataset.userLocale;
    gettext_provider.setLocale(locale);
    const widgets       = document.getElementsByClassName("labeled-items-widget");
    const RootComponent = Vue.extend(LabeledItemsList);

    const widgets_array = [...widgets];
    for (const widget of widgets_array) {
        new RootComponent({
            propsData: { ...widget.dataset }
        }).$mount(widget);
    }
});
