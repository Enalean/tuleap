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

const _ = require("lodash");

module.exports = function () {
    var self = this;

    self.user_scores_collection = {};

    var score_map_previous_status_to_status_for_current_user = {
        passed: {
            passed: 0, failed: 1, blocked: 0, notrun: 0
        },
        failed: {
            passed: 1, failed: 0, blocked: 0, notrun: 0
        },
        blocked: {
            passed: 1, failed: 1, blocked: 0,  notrun: 0
        },
        notrun: {
            passed: 1, failed: 1, blocked: 0,  notrun: 0
        }
    };

    var score_map_previous_status_to_status_for_previous_user = {
        passed: {
            passed: 0, failed: -1, blocked: -1, notrun: -1
        },
        failed: {
            passed: 0, failed: 0, blocked: -1, notrun: -1
        },
        blocked: {
            passed: 0, failed: 0, blocked: 0,  notrun: 0
        },
        notrun: {
            passed: 0, failed: 0, blocked: 0,  notrun: 0
        }
    };

    /**
     * @access public
     *
     * Function to add score
     * by user id and room id
     *
     * @param user     (Object)
     * @param room_id  (string)
     */
    self.addScoreByUserIdAndRoomId = function(user, room_id) {
        try {
            if (! _.has(self.user_scores_collection, user.id)) {
                self.user_scores_collection[user.id] = [];
            }

            var user_score_room = getByUserIdAndRoomId(user.id, room_id);

            if (! user_score_room) {
                _.extend(user, {
                    score: 0
                });
                user_score_room = {
                    room_id: room_id,
                    user   : user
                };
                self.user_scores_collection[user.id].push(user_score_room);
            }
            return true;
        } catch (e) {
            return false;
        }
    };

    /**
     * @access public
     *
     * Function to get score
     * by user id and room id
     *
     * @param user_id  (int)
     * @param room_id  (string)
     */
    self.getScoreByUserIdAndRoomId = function(user_id, room_id) {
        var score = 0;
        var user_score = getByUserIdAndRoomId(user_id, room_id);

        if (user_score) {
            score = user_score.user.score;
        }

        return score;
    };

    /**
     * @access public
     *
     * Function to get score
     * for all users by room id
     *
     * @param room_id  (string)
     */
    self.getAllUsersByRoomId = function(room_id) {
        var users = [];
        _.forEach(self.user_scores_collection, function (score_by_user) {
            _.forEach(score_by_user, function(score_by_room) {
                if (score_by_room.room_id === room_id) {
                    users.push(score_by_room.user);
                }
            });
        });

        return users;
    };

    /**
     * @access public
     *
     * Function to verify if the test
     * is finished before adding new score
     *
     * @param user     (Object)
     * @param room_id  (string)
     * @param data     (Object)
     */
    self.update = function(user, room_id, data) {
        var status          = data.status;
        var previous_status = data.previous_status;
        var previous_user   = data.previous_user;
        var delta_for_current_user  = score_map_previous_status_to_status_for_current_user[previous_status][status];
        var delta_for_previous_user = score_map_previous_status_to_status_for_previous_user[previous_status][status];

        if (user) {
            updateByUser(delta_for_current_user, user, room_id);
        }

        if (previous_user) {
            updateByUser(delta_for_previous_user, previous_user, room_id);
        }
    };

    /**
     * @access public
     *
     * Function to remove by
     * user id
     *
     * @param user_id (int)
     */
    self.remove = function(user_id) {
        delete self.user_scores_collection[user_id];
    };

    /**
     * @access public
     *
     * Function to clear the collection
     * of user scores
     */
    self.removeAll = function() {
        delete self.user_scores_collection;
        self.user_scores_collection = {};
    };

    /**
     * @access private
     *
     * Function to update score
     * by user id and room id
     *
     * @param delta   (int)
     * @param user    (object)
     * @param room_id (string)
     */
    function updateByUser(delta, user, room_id) {
        self.addScoreByUserIdAndRoomId(user, room_id);

        var user_score_room        = getByUserIdAndRoomId(user.id, room_id);
        var score                  = user_score_room.user.score;
        user_score_room.user       = user;
        user_score_room.user.score = score;

        user_score_room.user.score += delta;
    }

    /**
     * @access private
     *
     * Function to get object with
     * score by user id and room id
     *
     * @param user_id  (int)
     * @param room_id  (string)
     */
    function getByUserIdAndRoomId(user_id, room_id) {
        return _.find(self.user_scores_collection[user_id], function (score_by_room) {
            return score_by_room.room_id === room_id;
        });
    }
};
