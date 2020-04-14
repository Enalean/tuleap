<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */


class Tracker_CannedResponseFactory
{

    /**
     * Constructor
     */
    protected function __construct()
    {
    }

    /**
     * Hold an instance of the class
     */
    protected static $instance;

    /**
     * The singleton method
     *
     * @return self An instance of canned response factory
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            $c = self::class;
            self::$instance = new $c();
        }
        return self::$instance;
    }

    /**
     * Build a CannedResponse instance
     *
     * @param array $row The data describing the canned response
     *
     * @return Tracker_CannedResponse
     */
    public function getInstanceFromRow($row)
    {
        return new Tracker_CannedResponse(
            $row['id'],
            $row['tracker'],
            $row['title'],
            $row['body']
        );
    }

    /**
     * Build a canned response from a xml snippet
     *
     * @param SimpleXMLElement $xml the xml snippet describing a canned response
     *
     * @return Tracker_CannedResponse
     */
    public function getInstanceFromXML($xml)
    {
        return new Tracker_CannedResponse(0, null, (string) $xml->title, (string) $xml->body);
    }

    /**
     * Get the canned responses related to a tracker
     *
     * @param Tracker $tracker the tracker
     *
     * @return array
     */
    public function getCannedResponses($tracker)
    {
        $responses = array();
        foreach ($this->getDao()->searchByTrackerId($tracker->id) as $row) {
            $row['tracker'] = $tracker;
            $responses[$row['id']] = $this->getInstanceFromRow($row);
        }
        return $responses;
    }

    /**
     * Get a canned response for a tracker
     *
     * @param Tracker $tracker the tracker
     * @param int     $id      the id of the canned response
     *
     * @return Tracker_CannedResponse|null or null if not found
     */
    public function getCannedResponse($tracker, $id)
    {
        $response = null;
        if ($row = $this->getDao()->searchById($tracker->id, $id)->getRow()) {
            $row['tracker'] = $tracker;
            $response = $this->getInstanceFromRow($row);
        }
        return $response;
    }

    /**
     * Create a canned response
     *
     * @param Tracker $tracker The tracker the canned response refers to
     * @param string  $title   The new title
     * @param string  $body    The new body
     *
     * @return int the id of the canned response. False if error
     */
    public function create($tracker, $title, $body)
    {
        return $this->getDao()->create($tracker->id, $title, $body);
    }

    /**
     * Update the canned response
     *
     * @param int     $id      The id of the canned response
     * @param Tracker $tracker The tracker this canned response belongs to
     * @param string  $title   The new title
     * @param string  $body    The new body
     *
     * @return bool true if success, false otherwise
     */
    public function update($id, $tracker, $title, $body)
    {
        $ok = false;
        if ($canned = $this->getCannedResponse($tracker, $id)) {
            if (trim($title) && trim($body)) {
                $canned->title = trim($title);
                $canned->body  = $body;
                $ok = $this->getDao()->save($canned);
            }
        }
        return $ok;
    }

    /**
     * Delete a CannedResponse
     *
     * @param int $id the id of the canned response to delete
     *
     * @return bool true if success
     */
    public function delete($id)
    {
        return $this->getDao()->delete($id);
    }

    /**
     * Duplicate all canned responses of tracker $from_tracker_id into $to_tracker_id
     *
     * @param int $from_tracker_id The Id of the tracker source
     * @param int $to_tracker_id   The Id of the tracker target
     *
     * @return void
     */
    public function duplicate($from_tracker_id, $to_tracker_id)
    {
        $tf = $this->getTrackerFactory();
        $from_tracker = $tf->getTrackerById($from_tracker_id);
        if ($from_tracker === null) {
            throw new RuntimeException('Tracker does not exist');
        }
        $to_tracker = $tf->getTrackerById($to_tracker_id);
        $from_canned_responses = $this->getCannedResponses($from_tracker);
        foreach ($from_canned_responses as $from_canned_response) {
            if ($to_tracker === null) {
                throw new RuntimeException('Tracker does not exist');
            }
            $this->create($to_tracker, $from_canned_response->getTitle(), $from_canned_response->getBody());
        }
    }

    /**
     * Save a CannedResponse object
     *
     * @param int                    $trackerId the id of the tracker
     * @param Tracker_CannedResponse $response  the object to save
     *
     * @return int the id of the new CannedResponse
     */
    public function saveObject($trackerId, $response)
    {
        return $this->getDao()->create($trackerId, $response->title, $response->body);
    }

    /**
     * Get the CannedResponse dao
     *
     * @return Tracker_CannedResponseDao
     */
    protected function getDao()
    {
        return new Tracker_CannedResponseDao();
    }

    /**
     * Get the Tracker Factory
     *
     * @return TrackerFactory An instance of tracker Factory
     */
    public function getTrackerFactory()
    {
        return TrackerFactory::instance();
    }
}
