/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import Vue from "vue";
import { Prop, Component } from "vue-property-decorator";

const nb_skeletons_per_column = [4, 1, 2, 3, 1];

@Component
export default class SkeletonMixin extends Vue {
    @Prop({ required: true })
    readonly column_index!: number;

    get nb_skeletons(): number {
        return nb_skeletons_per_column[this.column_index % nb_skeletons_per_column.length];
    }
}
