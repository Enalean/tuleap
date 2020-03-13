<?php
/**
 * ArtifactCanned.class.php - Class to handle canned responses
 *
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * Copyright 1999-2001 (c) VA Linux Systems
 * http://sourceforge.net
 *
 *
 */


class ArtifactCanned
{

    /**
     * The artifact type object.
     *
     * @var        object    $ArtifactType.
     */
    public $ArtifactType;
    public $atid;

    /**
     * Array of artifact data.
     *
     * @var        array    $data_array.
     */
    public $data_array;
    /**
     * @var string
     */
    private $error_message = '';
    /**
     * @var bool
     */
    private $error_state = false;

    /**
     *  ArtifactCanned - constructor.
     *
     *    @param    object    The Artifact Type object.
     *  @param    array    (all fields from artifact_file_user_vw) OR id from database.
     *  @return bool success.
     */
    public function __construct(&$ArtifactType, $data = false)
    {
        global $Language;

     //was ArtifactType legit?
        if (!$ArtifactType) {
            $this->setError('ArtifactCanned: No ArtifactType');
            return false;
        }
        if (!is_object($ArtifactType)) {
            $this->setError('ArtifactCanned: ' . $Language->getText('tracker_common_canned', 'not_valid'));
            return false;
        }
     //did ArtifactType have an error?
        if ($ArtifactType->isError()) {
            $this->setError('ArtifactCanned: ' . $Artifact->getErrorMessage());
            return false;
        }
        $this->ArtifactType = $ArtifactType;
        $this->atid = $ArtifactType->getID();

        if ($data) {
            if (is_array($data)) {
                $this->data_array = $data;
                return true;
            } else {
                if (!$this->fetchData($data)) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }

    /**
     *    create - create a new item in the database.
     *
     *    @param    string    The item title.
     *    @param    string    The item body.
     *  @return id on success / false on failure.
     */
    public function create($title, $body)
    {
        global $Language;

     //    data validation
        if (!$title || !$body) {
            $this->setError('ArtifactCanned: ' . $Language->getText('tracker_common_canned', 'name_requ'));
            return false;
        }
        if (!$this->ArtifactType->userIsAdmin()) {
            $this->setError($Language->getText('tracker_common_canned', 'perm_denied'));
            return false;
        }

        $sql = "INSERT INTO artifact_canned_responses (group_artifact_id,title,body)
			VALUES ('" . db_ei($this->ArtifactType->getID()) . "',
			'" . db_es(htmlspecialchars($title)) . "','" . db_es(htmlspecialchars($body))  . "')";

        $result = db_query($sql);

        if ($result && db_affected_rows($result) > 0) {
            $this->clearError();
            return true;
        } else {
            $this->setError(db_error());
            return false;
        }

   /*
      //
      //    Now set up our internal data structures
      //
      if (!$this->fetchData($id)) {
       return false;
      }
   */
    }

    /**
     *    fetchData - re-fetch the data for this ArtifactCanned from the database.
     *
     *    @param int    The ID number.
     *    @return bool success.
     */
    public function fetchData($id)
    {
        global $Language;

        $res = db_query("SELECT * FROM artifact_canned_responses WHERE artifact_canned_id='" . db_ei($id) . "' AND group_artifact_id='" . db_ei($this->atid) . "'");
        if (!$res || db_numrows($res) < 1) {
            $this->setError('ArtifactCanned: ' . $Language->getText('tracker_common_canned', 'invalid_id'));
            return false;
        }
        $this->data_array = db_fetch_array($res);
        db_free_result($res);
        return true;
    }

    /**
     *    getArtifactType - get the ArtifactType Object this ArtifactCanned message is associated with.
     *
     *    @return ArtifactType.
     */
    public function getArtifactType()
    {
        return $this->ArtifactType;
    }

    /**
     *    getID - get this ArtifactCanned message's ID.
     *
     *    @return    int    The id #.
     */
    public function getID()
    {
        return $this->data_array['artifact_canned_id'];
    }

    /**
     *    getTitle - get the title.
     *
     *    @return    string    The title.
     */
    public function getTitle()
    {
        return $this->data_array['title'];
    }

    /**
     *    getBody - get the body of this message.
     *
     *    @return    string    The message body.
     */
    public function getBody()
    {
        return $this->data_array['body'];
    }

    /**
     *  update - update an ArtifactCanned message.
     *
     *  @param    string    Title of the message.
     *  @param    string    Body of the message.
     *  @return bool success.
     */
    public function delete($artifact_canned_id)
    {
        global $Language;

        if (!$this->ArtifactType->userIsAdmin()) {
            $this->setError($Language->getText('tracker_common_canned', 'perm_denied'));
            return false;
        }

        $sql = "delete from artifact_canned_responses

			WHERE group_artifact_id='" .  db_ei($this->ArtifactType->getID())  . "' AND artifact_canned_id='" .  db_ei($artifact_canned_id)  . "'";

        $result = db_query($sql);

        if (!$result) {
            $this->setError(db_error());
            return false;
        } else {
             return true;
        }
    }

    /**
     *  update - update an ArtifactCanned message.
     *
     *  @param    string    Title of the message.
     *  @param    string    Body of the message.
     *  @return bool success.
     */
    public function update($title, $body)
    {
        global $Language;

        if (!$this->ArtifactType->userIsAdmin()) {
            $this->setError($Language->getText('tracker_common_canned', 'perm_denied'));
            return false;
        }
        if (!$title || !$body) {
            $this->setError($Language->getText('tracker_common_canned', 'missing_param'));
            return false;
        }

        $sql = "UPDATE artifact_canned_responses
			SET title='" . db_es(htmlspecialchars($title))  . "',body='" . db_es(htmlspecialchars($body))  . "'
			WHERE group_artifact_id='" .  db_ei($this->ArtifactType->getID())  . "' AND artifact_canned_id='" .  db_ei($this->getID())  . "'";

        $result = db_query($sql);

        if ($result && db_affected_rows($result) > 0) {
            return true;
        } else {
            $this->setError(db_error());
            return false;
        }
    }

    /**
     * @param $string
     */
    public function setError($string)
    {
        $this->error_state = true;
        $this->error_message = $string;
    }

    public function clearError()
    {
        $this->error_state = false;
        $this->error_message = '';
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        if ($this->error_state) {
            return $this->error_message;
        } else {
            return $GLOBALS['Language']->getText('include_common_error', 'no_err');
        }
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->error_state;
    }
}
