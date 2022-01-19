/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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

const moment = require("moment");

module.exports = function (scores) {
    var self                  = this;
    var clear_scores_interval = setScoresInterval();

    self.scores = scores;

    /**
     * @access public
     *
     * Function to clear scores for
     * each room id
     */
    self.clearScoresInSeveralDays = function() {
        clearInterval(clear_scores_interval);
        clear_scores_interval = setScoresInterval();
    };

    /**
     * Function to initialize interval to
     * clear scores
     *
     * @returns {number}
     */
    function setScoresInterval() {
        return setInterval(function () {
            self.scores.removeAll();
        }, moment.duration(2, 'days').valueOf());
    }
};
