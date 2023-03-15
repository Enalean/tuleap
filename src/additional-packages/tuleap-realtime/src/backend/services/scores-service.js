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

const NB_DAYS_TO_CLEAR_SCORES = 2;

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
        const delay_to_clear_scores_in_ms = NB_DAYS_TO_CLEAR_SCORES * 24 * 3600 * 1000;
        return setInterval(function () {
            self.scores.removeAll();
        }, delay_to_clear_scores_in_ms);
    }
};
