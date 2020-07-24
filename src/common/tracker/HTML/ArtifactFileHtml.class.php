<?php
/**
  *
  * SourceForge Generic Tracker facility
  *
  * SourceForge: Breaking Down the Barriers to Open Source Development
  * Copyright 1999-2001 (c) VA Linux Systems
  * http://sourceforge.net
  *
  *
  */

class ArtifactFileHtml extends ArtifactFile // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const MAX_SIZE_DEFAULT = 16777216;

    /**
     *  ArtifactFileHtml() - constructor
     *
     *  Use this constructor if you are modifying an existing artifact
     *
     *  @param $Artifact object
     *  @param $data associative array (all fields from artifact_file_user) OR id from database
     *  @return true/false
     */
    public function __construct(&$Artifact, $data = false)
    {
        return parent::__construct($Artifact, $data);
    }

    /**
     * Upload a file to store in artifact_file
     *
     * @return bool
     */
    public function upload($input_file, $input_file_name, $input_file_type, $description, &$changes)
    {
            global $Language;
        $sys_max_size_attachment = ForgeConfig::get('sys_max_size_attachment', self::MAX_SIZE_DEFAULT);

        if (! util_check_fileupload($input_file)) {
            $this->setError($Language->getText('tracker_include_artifactfile', 'invalid_name'));
            return false;
        }
            $size = @filesize($input_file);
            $input_data = @fread(@fopen($input_file, 'r'), $size);
        if ((strlen($input_data) < 1) || (strlen($input_data) > $sys_max_size_attachment)) {
                $this->setError($Language->getText('tracker_include_artifactfile', 'not_attached', formatByteToMb($sys_max_size_attachment)));
                return false;
        }
            return $this->create($input_file_name, $input_file_type, $size, $input_data, $description, $changes);
    }
}
