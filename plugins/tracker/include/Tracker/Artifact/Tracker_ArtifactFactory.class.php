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


class Tracker_ArtifactFactory {
    
    protected $artifacts;
    /**
     * A protected constructor; prevents direct creation of object
     */
    protected function __construct() {
        $this->artifacts = array();
    }

    /**
     * Hold an instance of the class
     */
    protected static $instance;
    
    /**
     * The singleton method
     *
     * @return Tracker_ArtifactFactory an instance of this class
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::setInstance(new $c);
        }
        return self::$instance;
    }
    
    /**
     * Allows to inject a fake factory for test. DO NOT USE IT IN PRODUCTION!
     * 
     * @param Tracker_ArtifactFactory $factory 
     */
    public static function setInstance(Tracker_ArtifactFactory $factory) {
        self::$instance = $factory;
    }

    /**
     * Allows clear factory instance for test. DO NOT USE IT IN PRODUCTION!
     * 
     * @param Tracker_ArtifactFactory $factory 
     */
    public static function clearInstance() {
        self::$instance = null;
    }
    
    /**
     * Return the artifact with the id $id, or null if not found
     *
     * @param int $id the id of the artifact to retrieve
     *
     * @return Tracker_Artifact the artifact identified by id (null if not found)
     */
    public function getArtifactById($id) {
        if (!isset($this->artifacts[$id])) {
            $this->artifacts[$id] = null;
            $row = $this->getDao()->searchById($id)->getRow();
            if ($row) {
                $this->artifacts[$id] = $this->getInstanceFromRow($row);
            }
        }
        return $this->artifacts[$id];
    }
    
    /**
     * Return the artifact corresponding to $id the user can access
     * 
     * @param PFUser    $user
     * @param Integer $id
     * 
     * @return Tracker_Artifact
     */
    public function getArtifactByIdUserCanView(PFUser $user, $id) {
        $artifact = $this->getArtifactById($id);
        if ($artifact && $artifact->userCanView($user)) {
            return $artifact;
        }
        return null;
    }
    
    /**
     * Returns all the artifacts of the tracker racker_id
     *
     * @param int $tracker_id the id of the tracker
     *
     * @return array of Tracker_Artifact identified by id (array() if not found)
     */
    public function getArtifactsByTrackerId($tracker_id) {
        $artifacts = array();
        foreach ($this->getDao()->searchByTrackerId($tracker_id) as $row) {
            $artifacts[$row['id']] = $this->getInstanceFromRow($row);
        }
        return $artifacts;
    }
    
    /**
     * Given a list of artifact ids, return corresponding artifact objects if any
     * 
     * @param array $artifact_ids
     * 
     * @return array of Tracker_Artifact
     */
    public function getArtifactsByArtifactIdList(array $artifact_ids) {
        $artifacts = array();
        foreach ($artifact_ids as $artifact_id) {
            if ($artifact = $this->getArtifactById($artifact_id)) {
                $artifacts[$artifact_id] = $artifact;
            }
        }
        return $artifacts;
    }
    
    /**
     * Returns all the artifacts of the tracker with id $tracker_id the User $user can read
     *
     * @param PFUser $user       User who want to access to artifacts
     * @param int  $tracker_id the id of the tracker
     *
     * @return array of Tracker_Artifact identified by id (array() if not found)
     */
    public function getArtifactsByTrackerIdUserCanView(PFUser $user, $tracker_id) {
        $artifacts = array();
        foreach ($this->getDao()->searchByTrackerId($tracker_id) as $row) {
            $artifact = $this->getInstanceFromRow($row);
            if ($artifact->userCanView($user)) {
                $artifacts[$row['id']] = $artifact; 
            }
        }
        return $artifacts;
    }
    
    public function getOpenArtifactsByTrackerIdUserCanView(PFUser $user, $tracker_id) {
        $artifacts = array();
        foreach ($this->getDao()->searchOpenByTrackerId($tracker_id) as $row) {
            $artifact = $this->getInstanceFromRow($row);
            if ($artifact->userCanView($user)) {
                $artifacts[$row['id']] = $artifact; 
            }
        }
        return $artifacts;
    }
    
    /**
     * Returns the "open" artifacts 
     *  - assigned to user $user_id OR
     *  - submitted by user $user_id OR
     *  - submitted by or assigned to user $user_id.
     * regarding the callback method (respectively
     *  - searchOpenAssignedToUserId,
     *  - searchOpenSubmittedByUserId
     *  - searchOpenSubmittedByOrAssignedToUserId)
     *
     * in an array of the form:
     * array(
     *    $tracker_id => array(
     *                      'tracker'   => $tracker (Tracker),
     *                      'artifacts' => array(
     *                                          'artifact' => $artifact (Tracker_Artifact),
     *                                          'title'    => $title (string)
     *                                     )
     *                   )
     * )
     *
     * @param int    $user_id  the id of the user
     * @param string $callback the callback method
     *
     * @return array Complex array of artifacts group by trackers (see above)
     */
    protected function getUserOpenArtifacts($user_id, $callback) {
        $tf = TrackerFactory::instance();
        $artifacts = array();
        foreach ($this->getDao()->$callback($user_id) as $row) {
            if (!isset($artifacts[$row['tracker_id']])) {
                $tracker = $tf->getTrackerById($row['tracker_id']);
                
                $with_title = false;
                if ($title_field = Tracker_Semantic_Title::load($tracker)->getField()) {
                    if ($title_field->userCanRead()) {
                        $with_title = true;
                    }
                }
                
                $artifacts[$row['tracker_id']] = array(
                    'tracker'    => $tracker,
                    'with_title' => $with_title,
                    'artifacts'  => array(),
                );
            }
            if (!isset($artifacts[$row['tracker_id']]['artifacts'][$row['id']])) {
                $artifact = $this->getInstanceFromRow($row);
                if ($artifact->userCanView()) {
                    $artifacts[$row['tracker_id']]['artifacts'][$row['id']] = array(
                        'artifact' => $artifact,
                        'title'    => $artifacts[$row['tracker_id']]['with_title'] ? $row['title'] : '',
                    );
                }
            }
        }
        return $artifacts;
    }
    
    /**
     * Returns the "open" artifacts assigned to user $user_id
     * in an array of the form: 
     * @see getUserOpenArtifacts
     *
     * @param int $user_id the id of the user
     *
     * @return array Complex array of artifacts group by trackers (see above)
     */
    public function getUserOpenArtifactsAssignedTo($user_id) {
        return $this->getUserOpenArtifacts($user_id, 'searchOpenAssignedToUserId');
    }
    
    /**
     * Returns the "open" artifacts submitted by user $user_id
     * in an array of the form: 
     * @see getUserOpenArtifacts
     * 
     * @param int $user_id the id of the user
     *
     * @return array Complex array of artifacts group by trackers (see above)
     */
    public function getUserOpenArtifactsSubmittedBy($user_id) {
        return $this->getUserOpenArtifacts($user_id, 'searchOpenSubmittedByUserId');
    }
    
    /**
     * Returns the "open" artifacts assigned to or submitted by user $user_id
     * in an array of the form:
     * @see getUserOpenArtifacts
     *
     * @param int $user_id the id of the user
     *
     * @return array Complex array of artifacts group by trackers (see above)
     */
    public function getUserOpenArtifactsSubmittedByOrAssignedTo($user_id) {
        return $this->getUserOpenArtifacts($user_id, 'searchOpenSubmittedByOrAssignedToUserId');
    }
    
    /**
     * Buil an instance of artifact
     *
     * @param array $row the value of the artifact form the db
     *
     * @return Tracker_Artifact
     */
    public function getInstanceFromRow($row) {
        $artifact = new Tracker_Artifact(
            $row['id'], 
            $row['tracker_id'], 
            $row['submitted_by'], 
            $row['submitted_on'], 
            $row['use_artifact_permissions']
        );
        if (isset($row['title'])) {
            $artifact->setTitle($row['title']);
        }
        return $artifact;
    }
    
    protected $dao;
    /**
     * Returns the Tracker_ArtifactDao
     *
     * @return Tracker_ArtifactDao
     */
    protected function getDao() {
        if (!$this->dao) {
            $this->dao = new Tracker_ArtifactDao();
        }
        return $this->dao;
    }
    
    /**
     * Add an artefact in the tracker
     * 
     * @param Tracker $tracker           The tracker this artifact belongs to
     * @param array   $fields_data       The data of the artifact to create
     * @param PFUser    $user              The user that want to create the artifact
     * @param string  $email             The email if the user is anonymous (null if anonymous)
     * @param boolean $send_notification true if a notification must be sent, false otherwise
     * 
     * @return Tracker_Artifact or false if an error occured
     */
    public function createArtifact(Tracker $tracker, $fields_data, PFUser $user, $email, $send_notification = true) {
        $artifact = $this->getInstanceFromRow(
            array(
                'id'                       => 0, 
                'tracker_id'               => $tracker->id, 
                'submitted_by'             => $user->getId(), 
                'submitted_on'             => $_SERVER['REQUEST_TIME'], 
                'use_artifact_permissions' => 0,
            )
        );
        
        //validate the request
        if ($ok = $artifact->validateFields($fields_data, true)) {
            //If all is ok, save the artifact
            $use_artifact_permissions = 0;
            if ($id = $this->getDao()->create($tracker->id, $user->getId(), $use_artifact_permissions)) {
                $artifact->setId($id);                
                //create the first changeset
                if ($changeset_id = $artifact->createInitialChangeset($fields_data, $user, $email)) {                    
                    $submitted_by = $artifact->getSubmittedBy();
                    $submitted_on = $artifact->getSubmittedOn();
                    $changeset     = new Tracker_Artifact_Changeset($changeset_id, $artifact, $submitted_by, $submitted_on, $email );
                    if ($send_notification) {
                        $changeset->notify();
                    }
                    return $artifact;
                }
            }
        }
        return false;
    }
    
    public function save(Tracker_Artifact $artifact) {
        return $this->getDao()->save($artifact->getId(), $artifact->getTrackerId(), $artifact->useArtifactPermissions());
    }

    /**
     * @return Tracker_Artifact[]
     */
    public function getChildren(Tracker_Artifact $artifact) {
        return $this->getDao()->getChildren($artifact->getId())->instanciateWith(array($this, 'getInstanceFromRow'));
    }
}
?>
