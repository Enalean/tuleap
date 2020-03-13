<?php
/**
 * ArtifactFile.class.php - Class to handle files within an artifact
 *
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * Copyright 1999-2001 (c) VA Linux Systems
 * http://sourceforge.net
 *
 *
 */


class ArtifactFile
{

    public const ROOT_DIRNAME = 'trackerv3';

    /**
     * The artifact object.
     *
     * @var        object    $Artifact.
     */
    public $Artifact; //object

    /**
     * Array of file data
     *
     * @var        array    $data_array
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
     *  ArtifactFile - constructor.
     *
     *    @param    object    The Artifact object.
     *  @param    array    (all fields from artifact_file_user_vw) OR id from database.
     *  @return bool success.
     */
    public function __construct(&$Artifact, $data = false)
    {
        global $Language;

     //was Artifact legit?
        if (!$Artifact || !is_object($Artifact)) {
            $this->setError('ArtifactFile: ' . $Language->getText('tracker_common_file', 'invalid'));
            return false;
        }
     //did ArtifactType have an error?
        if ($Artifact->isError()) {
            $this->setError('ArtifactFile: ' . $Artifact->getErrorMessage());
            return false;
        }
        $this->Artifact = $Artifact;
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
     *    @para    string    Filename of the item.
     *    @param    string    Item filetype.
     *    @param    string    Item filesize.
     *    @param    binary    Binary item data.
     *    @param    string    Item description.
     *  @return id on success / false on failure.
     */
    public function create($filename, $filetype, $filesize, $bin_data, $description, &$changes)
    {
        global $Language;

        if (!$description) {
            $description = $Language->getText('global', 'none');
        }
        $old_value = $this->Artifact->getAttachedFileNames();

     // Some browsers don't supply mime type if they don't know it
        if (!$filetype) {
         // Let's be on safe side?
            $filetype = 'application/octet-stream';
        }

     //    data validation
        if (!$filename || !$filetype || !$filesize || !$bin_data) {
            $GLOBALS['Response']->addFeedback('error', '<P>|' . $filename . '|' . $filetype . '|' . $filesize . '|' . $bin_data . '|');
            $this->setError('ArtifactFile: ' . $Language->getText('tracker_common_file', 'name_requ'));
            return false;
        }

        if (user_isloggedin()) {
            $userid = UserManager::instance()->getCurrentUser()->getId();
        } else {
            $userid = 100;
        }

        $res = db_query("INSERT INTO artifact_file
			(artifact_id,description,bin_data,filename,filesize,filetype,adddate,submitted_by)
			VALUES
			('" . db_ei($this->Artifact->getID()) . "','" . db_es($description) . "','','" . db_es($filename) . "',
			'" .  db_ei($filesize)  . "','" .  db_es($filetype)  . "','" . time() . "','" .  db_ei($userid)  . "')");

        $id = db_insertid($res, 'artifact_file', 'id');

        if (!$res || !$id) {
            $this->setError('ArtifactFile: ' . db_error());
            return false;
        } else {
            $this->clearError();
                        $this->createOnFileSystem($id, $bin_data);

            $changes['attach']['description'] = $description;
            $changes['attach']['name'] = $filename;
            $changes['attach']['size'] = $filesize;

            if ($old_value == '') {
                    $new_value = $filename;
            } else {
                $new_value = $old_value . "," . $filename;
            }
            $this->Artifact->addHistory('attachment', $old_value, $new_value);

            $changes['attach']['href'] = HTTPRequest::instance()->getServerUrl() .
             "/tracker/download.php?artifact_id=" . $this->Artifact->getID() . "&id=$id";

            return $id;
        }
    }

    private function createOnFileSystem($id, $bin_data)
    {
        $parent_directory = $this->getParentDirectory();
        if (! is_dir($parent_directory)) {
            mkdir($parent_directory, 0750, true);
        }
        file_put_contents($parent_directory . DIRECTORY_SEPARATOR . $id, $bin_data);
    }

    public function getParentDirectory()
    {
        return self::getParentDirectoryForArtifact($this->Artifact->getArtifactType());
    }

    public static function getParentDirectoryForArtifact(ArtifactType $artifact_type)
    {
        return self::getParentDirectoryForArtifactTypeId($artifact_type->getID());
    }

    public static function getParentDirectoryForArtifactTypeId($artifact_type_id)
    {
        return ForgeConfig::get('sys_data_dir') . DIRECTORY_SEPARATOR . self::ROOT_DIRNAME . DIRECTORY_SEPARATOR . $artifact_type_id;
    }

    public static function getPathOnFilesystem(Artifact $artifact, $attachment_id)
    {
        return self::getParentDirectoryForArtifact($artifact->getArtifactType()) . DIRECTORY_SEPARATOR . $attachment_id;
    }

    public static function getPathOnFilesystemByArtifactTypeId($artifact_type_id, $attachment_id)
    {
        return self::getParentDirectoryForArtifactTypeId($artifact_type_id) . DIRECTORY_SEPARATOR . $attachment_id;
    }

        /**
     *    delete - delete this artifact file from the db.
     *
     *    @return bool success.
     */
    public function delete()
    {
        global $Language;

        $old_value = $this->Artifact->getAttachedFileNames();
        $sql = "DELETE FROM artifact_file WHERE id=" . db_ei($this->getID());
     //echo "sql=$sql<br>";
        $res = db_query($sql);
        if (!$res || db_affected_rows($res) < 1) {
            $this->setError('ArtifactFile: ' . $Language->getText('tracker_common_file', 'del_err'));
            return false;
        } else {
                    $this->deleteOnFileSystem();
            $new_value = $this->Artifact->getAttachedFileNames();
            $this->Artifact->addHistory('attachment', $old_value, $new_value);
            return true;
        }
    }

    private function deleteOnFileSystem()
    {
        $attachement_path = self::getPathOnFilesystem($this->Artifact, $this->getID());
        if (is_file($attachement_path)) {
            unlink($attachement_path);
        }
    }

    public static function deleteAllByArtifactType($artifact_type_id)
    {
        $parent_path = self::getParentDirectoryForArtifactTypeId($artifact_type_id);
        if (is_dir($parent_path)) {
            try {
                $iterator = new DirectoryIterator($parent_path);
                foreach ($iterator as $file) {
                    if (! $file->isDot()) {
                        unlink($file->getPathname());
                    }
                }
            } catch (Exception $exception) {
            }
            rmdir($parent_path);
        }
    }

    /**
     *    fetchData - re-fetch the data for this ArtifactFile from the database.
     *
     *    @param    int    The file_id.
     *    @return bool success.
     */
    public function fetchData($id)
    {
        global $Language;

        $sql = "SELECT af.id, af.artifact_id, af.description, af.bin_data, af.filename, af.filesize, af.filetype, af.adddate, af.submitted_by, user.user_name, user.realname
                FROM artifact_file af, user
                WHERE (af.submitted_by = user.user_id) and af.id=" . db_ei($id);
     //echo $sql;
        $res = db_query($sql);
        if (!$res || db_numrows($res) < 1) {
            $this->setError('ArtifactFile: ' . $Language->getText('tracker_common_file', 'invalid_id'));
            return false;
        }
        $this->data_array = db_fetch_array($res);
        db_free_result($res);
        return true;
    }

    /**
     *    getArtifact - get the Artifact Object this ArtifactFile is associated with.
     *
     *    @return object    Artifact.
     */
    public function getArtifact()
    {
        return $this->Artifact;
    }

    /**
     *    getID - get this ArtifactFile's ID.
     *
     *    @return    int    The id #.
     */
    public function getID()
    {
        return $this->data_array['id'];
    }

    /**
     *    getName - get the filename.
     *
     *    @return string filename.
     */
    public function getName()
    {
        return $this->data_array['filename'];
    }

    /**
     *    getType - get the type.
     *
     *    @return string type.
     */
    public function getType()
    {
        return $this->data_array['filetype'];
    }

    /**
     *    getData - get the binary data from the db.
     *
     *    @return binary.
     */
    public function getData()
    {
        return base64_decode($this->data_array['bin_data']);
    }

    /**
     *    getSize - get the size.
     *
     *    @return int size.
     */
    public function getSize()
    {
        return $this->data_array['filesize'];
    }

    /**
     *    getDescription - get the description.
     *
     *    @return string description.
     */
    public function getDescription()
    {
        return $this->data_array['description'];
    }

    /**
     *    getDate - get the date file was added.
     *
     *    @return int unix time.
     */
    public function getDate()
    {
        return $this->data_array['adddate'];
    }

    /**
     *    getSubmittedBy - get the user_id of the submitter.
     *
     *    @return int user_id.
     */
    public function getSubmittedBy()
    {
        return $this->data_array['submitted_by'];
    }

    /**
     *    getSubmittedRealName - get the real name of the submitter.
     *
     *    @return    string    name.
     */
    public function getSubmittedRealName()
    {
        return $this->data_array['realname'];
    }

    /**
     *    getSubmittedUnixName - get the unix name of the submitter.
     *
     *    @return    string    unixname.
     */
    public function getSubmittedUnixName()
    {
        return $this->data_array['user_name'];
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
