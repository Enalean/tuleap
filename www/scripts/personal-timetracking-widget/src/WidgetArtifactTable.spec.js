/*
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import Vue                 from 'vue';
import WidgetArtifactTable from './WidgetArtifactTable.vue';

describe('WidgetArtifactTable', () => {
    let ArtifactTable;

    beforeEach(() => {
        ArtifactTable = Vue.extend(WidgetArtifactTable);
    });

    function instantiateComponent(data = {}) {
        return new ArtifactTable({
            propsData: { ...data }
        }).$mount();
    }

    describe('getFormattedDate', () => {
        it('When I call this method with a string date, Then it returns a formatted date.', () => {
            const vm             = instantiateComponent();
            const formatted_date = vm.getFormattedDate('2018-01-01T08:00:00Z');

            expect(formatted_date).toEqual('01 Jan 2018');
        });
    });
});
