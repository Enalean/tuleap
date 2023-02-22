/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

    self.ugroups_collection = {};

    /**
     * @access public
     *
     * Function to get rights by
     * user id
     *
     * @param user_id (int)
     * @returns {*}
     */
    self.get = function(user_id) {
        return self.ugroups_collection[user_id] ?  self.ugroups_collection[user_id]: [];
    };

    /**
     * @access public
     *
     * Function to add for each user
     * their rights
     *
     * @param user_id  (int)
     * @param rights   (Array): rights for a user
     * @returns {boolean}
     */
    this.addRightsByUserId = function(user_id, rights) {
        try {
            this.ugroups_collection[user_id] = rights;
            return true;
        } catch (e) {
            return false;
        }
    };

    /**
     * @access public
     *
     * Function to update by
     * user id
     *
     * @param user_id (int)
     * @param rights  (Array): rights for a user
     */
    self.update = function(user_id, rights) {
        if (_.has(self.ugroups_collection, user_id)) {
            delete self.ugroups_collection[user_id];
        }
        self.addRightsByUserId(user_id, rights);
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
        delete self.ugroups_collection[user_id];
    };

    /**
     * @access public
     *
     * Function to check if user will
     * receive message from a socket
     * sender
     *
     * @param user_id      (int)   : user id who will receive message
     * @param user_rights  (Object): rights for a message
     * @returns {boolean}
     */
    self.userCanReceiveData = function(user_id, user_rights) {
        var data_submitter_id = user_rights.submitter_id;
        return (hasUserRights(self.ugroups_collection[user_id], user_rights))
            || (data_submitter_id === user_id && hasUserRightsAsSubmitter(self.ugroups_collection[user_id], user_rights));
    };

    /**
     * @access public
     *
     * Function to check rights content
     *
     * @param user_rights (Object): rights for a message
     * @returns {boolean}
     */
    self.isRightsContentWellFormed = function(user_rights) {
        return _.has(user_rights, 'submitter_id')
            && _.has(user_rights, 'submitter_can_view')
            && _.has(user_rights, 'submitter_only')
            && _.has(user_rights, 'tracker')
            && _.has(user_rights, 'artifact')
            && user_rights.tracker.length > 0;
    };

    /**
     * @access private
     *
     * Function to check if user has rights
     * to receive message from nodejs server
     *
     * @param u_group     (array) : user group corresponding to a user
     * @param user_rights (Object): rights for a message
     * @returns {boolean}
     */
    function hasUserRights(u_group, user_rights) {
        var find_right_artifact = false;
        var find_right_tracker  = hasUserRightsExist(u_group, user_rights.tracker);

        if(user_rights.artifact.length > 0) {
            find_right_artifact = hasUserRightsExist(u_group, user_rights.artifact);
        } else {
            find_right_artifact = true;
        }
        return find_right_tracker && find_right_artifact;
    }

    /**
     * @access private
     *
     * Function to check if user has rights
     * to receive message from nodejs server
     *
     * @param u_group     (array) : user group corresponding to a user
     * @param user_rights (Object): rights for a message
     * @returns {boolean}
     */
    function hasUserRightsAsSubmitter(u_group, user_rights) {
        var find_right_submitter = false;

        if (user_rights.submitter_can_view) {
            find_right_submitter = hasUserRightsExist(u_group, user_rights.submitter_only);
        }

        return find_right_submitter;
    }

    /**
     * @access private
     *
     * Function to check if rights
     * match
     *
     * @param u_group     (array) : user group corresponding to a user
     * @param user_rights (Object): rights for a message
     */
    function hasUserRightsExist(u_group, user_rights) {
        return _.some(user_rights, function(right) {
            return _.contains(u_group, right);
        });
    }
};
