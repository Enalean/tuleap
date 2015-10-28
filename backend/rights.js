/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
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

if (typeof define !== 'function') {
    var define = require('amdefine')(module);
}

define(['lodash'], function (_) {
    var rights = function () {
        this.ugroups_collection = {};

        /**
         * @access public
         *
         * Function to get rights by
         * user id
         *
         * @param key (int): user id
         * @returns {*}
         */
        this.get = function(key) {
            return this.ugroups_collection[key] ?  this.ugroups_collection[key]: [];
        };

        /**
         * @access public
         *
         * Function to add for each user
         * their rights
         *
         * @param key   (int)  : user id
         * @param value (Array): rights for a user
         * @returns {boolean}
         */
        this.bind = function(key, value) {
            try {
                this.ugroups_collection[key] = value;
                return true;
            } catch (e) {
                return false;
            }
        };

        /**
         * @access public
         *
         * Function to remove by
         * user id
         *
         * @param key (int): user id
         */
        this.remove = function(key) {
            delete this.ugroups_collection[key];
        };

        /**
         * @access private
         *
         * Function to check if user has rights
         * to receive message from nodejs server
         *
         * @param key         (int)   : user id who will receive message
         * @param user_rights (Object): rights for a message
         * @returns {boolean}
         */
        this.hasUserRights = function(key, user_rights) {
            var me                  = this;
            var find_right_artifact = false;
            var find_right_tracker  = this.hasUserRightsExist(key, user_rights.tracker);

            if(user_rights.artifact.length > 1) {
                find_right_artifact = this.hasUserRightsExist(key, user_rights.artifact);
            } else {
                find_right_artifact = true;
            }
            return find_right_tracker && find_right_artifact;
        };

        /**
         * @access private
         *
         * Function to check if user has rights
         * to receive message from nodejs server
         *
         * @param key         (int)   : submitter id who will receive message
         * @param user_rights (Object): rights for a message
         * @returns {boolean}
         */
        this.hasUserRightsAsSubmitter = function(key, user_rights) {
            var me                   = this;
            var find_right_submitter = false;

            if (user_rights.submitter_can_view) {
                find_right_submitter = this.hasUserRightsExist(key, user_rights.submitter_only);
            }

            return find_right_submitter;
        };

        /**
         * @access public
         *
         * Function to check if user will
         * receive message from a socket
         * sender
         *
         * @param key          (int)   : user id who will receive message
         * @param user_rights  (Object): rights for a message
         * @returns {boolean}
         */
        this.userCanReceiveData = function(key, user_rights) {
            var data_submitter_id = user_rights.submitter_id;
            return (this.hasUserRights(key, user_rights))
                || (data_submitter_id === key && this.hasUserRightsAsSubmitter(key, user_rights));
        };

        /**
         * @access public
         *
         * Function to check rights content
         *
         * @param user_rights (Object): rights for a message
         * @returns {boolean}
         */
        this.isRightsContentWellFormed = function(user_rights) {
            return _.has(user_rights, 'submitter_id')
                && _.has(user_rights, 'submitter_can_view')
                && _.has(user_rights, 'submitter_only')
                && _.has(user_rights, 'tracker')
                && _.has(user_rights, 'artifact')
                && user_rights.tracker.length > 0;
        };

        /**
         * @access public
         *
         * Function to filter artifact
         * looking field artifact rights
         * for each user
         *
         * @param key          (int)   : user id who will receive message
         * @param user_rights  (Object): rights for a message
         * @param artifact     (Object): artifact in message to filter
         * @returns {Object}
         */
        this.filterMessageByRights = function(key, user_rights, artifact) {
            var me         = this;
            var new_fields = [];
            if (user_rights.field) {
                _.forEach(artifact.card_fields, function (field) {
                    var field_id = field.field_id ? field.field_id : field.id;
                    if (me.hasUserRightsExist(key, user_rights.field[field_id])) {
                        new_fields.push(field);
                    }
                });
            }

            var doesNotFilterLabel = _.some(new_fields, function(field) {
                return field.label === 'Summary';
            });

            if (! doesNotFilterLabel) {
                artifact.label = null;
            }

            artifact.card_fields = new_fields;
            return artifact;
        };

        /**
         * @access private
         *
         * Function to check if rights
         * match
         *
         * @param key
         * @param user_rights
         */
        this.hasUserRightsExist = function(key, user_rights) {
            var me = this;
            return _.some(user_rights, function(right) {
                return _.contains(me.ugroups_collection[key], right);
            });
        };
    };

    return rights;
});