/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

define([
    'lodash'
], function (
    _
) {
    var executions = function () {
        var self = this;

        self.presences_collection = {};

        /**
         * @access public
         *
         * Function to get user by
         * execution id
         *
         * @param execution_id (int)
         * @returns {*}
         */
        self.get = function(execution_id) {
            return self.presences_collection[execution_id] ?  self.presences_collection[execution_id]: [];
        };

        /**
         * @access public
         *
         * Function to add for each execution
         * users who are connected on
         *
         * @param execution_id   (int)
         * @param user           (Array)
         * @returns {boolean}
         */
        self.addUserByExecutionId = function(execution_id, user) {
            try {
                if (! _.has(self.presences_collection, execution_id)) {
                    self.presences_collection[execution_id] = [];
                }
                self.presences_collection[execution_id].push(user);
                return true;
            } catch (e) {
                return false;
            }
        };

        /**
         * @access public
         *
         * Function to remove an
         * execution
         *
         * @param execution_id (int)
         */
        self.remove = function(execution_id) {
            delete self.presences_collection[execution_id];
        };

        /**
         * @access public
         *
         * Function to remove user
         * in execution
         *
         * @param user_uuid (String)
         */
        self.removeByUserUUID = function(user_uuid) {
            _.forEach(self.presences_collection, function(presences, execution_id) {
                _.remove(presences, function(presence) {
                    return presence.uuid === user_uuid;
                });
                if (_.isEmpty(presences)) {
                    self.remove(execution_id);
                }
            });
        };

        /**
         * @access public
         *
         * Function to remove user
         * in execution
         *
         * @param execution_id  (String)
         * @param user_uuid     (String)
         */
        self.removeByExecutionIdAndUserUUID = function(execution_id, user_uuid) {
            _.remove(self.presences_collection[execution_id], function(presence) {
                return presence.uuid === user_uuid;
            });
        };

        /**
         * Function to add presence if doesn't exists
         * and modify data to broadcast
         *
         * @param data (Object): data to add
         * @returns {{}} data to broadcast
         */
        self.addWithVerification = function(data) {
            var data_to_broadcast = {};
            if (! verifyIfPresenceAlreadyExists(data.uuid)) {
                self.addUserByExecutionId(data.execution_id, data.user);
                data_to_broadcast = constructDataToBroadcast(data.execution_id, data.remove_from, data.user);
            }

            return data_to_broadcast;
        };

        /**
         * @access public
         *
         * Function to update presences
         * and send correct data
         *
         * @param data (Object): data to broadcast
         * @returns {{}}
         */
        self.update = function(data) {
            var data_to_broadcast = {};
            _.extend(data.user, {
                uuid: data.uuid
            });
            if (data.remove_from !== data.execution_id
                && data.remove_from !== ''
                && _.has(self.presences_collection, data.remove_from)) {
                self.removeByExecutionIdAndUserUUID(data.remove_from, data.uuid);
                data_to_broadcast = self.addWithVerification(data);
            } else if (data.remove_from === data.execution_id
                && _.has(self.presences_collection, data.remove_from)) {
                self.removeByExecutionIdAndUserUUID(data.remove_from, data.uuid);
                data_to_broadcast = constructDataToBroadcast('', data.remove_from, data.user);
            } else {
                data_to_broadcast = self.addWithVerification(data);
            }

            return data_to_broadcast;
        };

        /**
         * @access private
         *
         * Function to verify if
         * presence exists on executions
         *
         * @param uuid (String): user uuid
         * @returns {boolean}
         */
        function verifyIfPresenceAlreadyExists(uuid) {
            return _.some(self.presences_collection, function(presences) {
                return _.some(presences, function(presence) {
                    return presence.uuid === uuid;
                });
            });
        }

        /**
         * Function to adapt data to broadcast
         *
         * @param execution_to_add    (String): execution id to add
         * @param execution_to_remove (String): execution id to remove
         * @param user                (Object): user
         * @returns {{user: *}}
         */
        function constructDataToBroadcast(execution_to_add, execution_to_remove, user) {
            var data_to_broadcast = {
                user: user
            };

            if (execution_to_add !== '') {
                _.extend(data_to_broadcast, {
                    execution_to_add: execution_to_add,
                    execution_presences_to_add: self.presences_collection[execution_to_add]
                });
            }
            if (execution_to_remove !== '') {
                _.extend(data_to_broadcast, {
                    execution_to_remove: execution_to_remove,
                    execution_presences_to_remove: self.presences_collection[execution_to_remove]
                });
            }

            return data_to_broadcast;
        }
    };

    return executions;
});
