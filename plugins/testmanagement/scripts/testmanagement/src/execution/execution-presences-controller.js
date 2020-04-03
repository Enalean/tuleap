/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

import { sortBy } from "lodash";

export default ExecutionPresencesCtrl;

ExecutionPresencesCtrl.$inject = ["modal_model"];

function ExecutionPresencesCtrl(modal_model) {
    const self = this;
    self.$onInit = () => {
        modal_model.presences.forEach(function (presence) {
            presence.score = presence.score || 0;
            presence.scoreView = Math.max(presence.score, 0);
        });
        var ranking = sortBy(modal_model.presences, "score").reverse();

        const [first, second, third, ...rest_ranking] = ranking;
        const top_ranking = [first, second, third];

        Object.assign(self, {
            title: modal_model.title,
            topRanking: top_ranking,
            restRanking: rest_ranking,
        });
    };
}
