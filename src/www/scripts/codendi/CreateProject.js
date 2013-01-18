/**
 * Copyright (c) Enalean SAS, 2013. All rights reserved
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

document.observe('dom:loaded', function () {

    function toggleTemplate(input) {
        var method = 'hide';
        if (input.checked) {
            method = 'show';
        }
        input.up('label').nextSiblings().invoke(method);
    }

    function toggleAllTemplates(inputs) {
        inputs.map(toggleTemplate);
    }

    var inputs = $$('.one_step_project_choose_template > label.radio > input[type=radio]');
    toggleAllTemplates(inputs);
    inputs.invoke('observe', 'click', function () {
        toggleAllTemplates(inputs);
    });
});

