<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\RealTime;

use Http_Client;
use Http_ClientException;
use ForgeConfig;
use BackendLogger;

class NodeJSClient implements Client {
    /**
     * @var String
     */
    private $url;

    public function __construct() {
        $this->url = 'https://' . ForgeConfig::get('nodejs_server');
    }

    /**
     * Method to send an Https request when
     * want to broadcast a message
     *
     * @param $sender_user_id  : Id of user
     * @param $sender_uuid     : Uuid to distinguish client with same user id
     * @param $room_id         : Room's id to broadcast message to this room
     * @param $rights          : To send at clients who have rights
     * @param $cmd             : Broadcast on event command
     * @param $data            : Data broadcasting
     * @throws \Http_ClientException
     */
    public function sendMessage($sender_user_id, $sender_uuid, $room_id, $rights, $cmd, $data) {
        if (ForgeConfig::get('nodejs_server') !== '') {
            $http_curl_client = new Http_Client();

            $options = array(
                CURLOPT_URL             => $this->url . '/message',
                CURLOPT_POST            => true,
                CURLOPT_POST            => 1,
                CURLOPT_HTTPHEADER      => array('Content-Type: application/json'),
                CURLOPT_POSTFIELDS      => json_encode(array(
                    'sender_user_id' => intval($sender_user_id),
                    'sender_uuid'    => $sender_uuid,
                    'room_id'        => $room_id,
                    'rights'         => $rights,
                    'cmd'            => $cmd,
                    'data'           => $data
                ))
            );

            $http_curl_client->addOptions($options);

            try {
                $http_curl_client->doRequest();
                $http_curl_client->close();
            } catch(Http_ClientException $e) {
                $logger = new BackendLogger();
                $logger->error('Unable to reach nodejs server '. $this->url .' -> '. $e->getMessage());
            }
        }
    }
}