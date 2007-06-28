<?php
/**
 * ArtifactFile.class.php - Class to handle files within an artifact
 *
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * Copyright 1999-2001 (c) VA Linux Systems
 * http://sourceforge.net
 *
 *
 */
//require_once('common/include/Error.class.php');

$Language->loadLanguageMsg('tracker/tracker');

class ArtifactFile extends Error {

	/** 
	 * The artifact object.
	 *
	 * @var		object	$Artifact.
	 */
	var $Artifact; //object

	/**
	 * Array of file data
	 *
	 * @var		array	$data_array
	 */
	var $data_array;

	/**
	 *  ArtifactFile - constructor.
	 *
	 *	@param	object	The Artifact object.
	 *  @param	array	(all fields from artifact_file_user_vw) OR id from database.
	 *  @return	boolean	success.
	 */
	function ArtifactFile(&$Artifact, $data=false) {
	  global $Language;

		$this->Error(); 

		//was Artifact legit?
		if (!$Artifact || !is_object($Artifact)) {
			$this->setError('ArtifactFile: '.$Language->getText('tracker_common_file','invalid'));
			return false;
		}
		//did ArtifactType have an error?
		if ($Artifact->isError()) {
			$this->setError('ArtifactFile: '.$Artifact->getErrorMessage());
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
	 *	create - create a new item in the database.
	 *
	 *	@para	string	Filename of the item.
	 *	@param	string	Item filetype.
	 *	@param	string	Item filesize.
	 *	@param	binary	Binary item data.
	 *	@param	string	Item description.
	 *  @return id on success / false on failure.
	 */
	function create($filename, $filetype, $filesize, $bin_data, $description=false,&$changes) {
	  global $Language;

	  if (!$description) $description=$Language->getText('global','none');
		$old_value = $this->Artifact->getAttachedFileNames();

		// Some browsers don't supply mime type if they don't know it
		if (!$filetype) {
			// Let's be on safe side?
			$filetype = 'application/octet-stream';
		}

		//
		//	data validation
		//
		if (!$filename || !$filetype || !$filesize || !$bin_data) {
			echo '<P>|'.$filename.'|'.$filetype.'|'.$filesize.'|'.$bin_data.'|';
			$this->setError('ArtifactFile: '.$Language->getText('tracker_common_file','name_requ'));
			return false;
		}

		if (user_isloggedin()) {
			$userid=user_getid();
		} else {
			$userid=100;
		}

		$res=db_query("INSERT INTO artifact_file
			(artifact_id,description,bin_data,filename,filesize,filetype,adddate,submitted_by)
			VALUES 
			('".$this->Artifact->getID()."','$description','". $bin_data ."','$filename',
			'$filesize','$filetype','". time() ."','$userid')"); 

		$id=db_insertid($res,'artifact_file','id');

		if (!$res || !$id) {
			$this->setError('ArtifactFile: '.db_error());
			return false;
		} else {
			$this->clearError();

			$changes['attach']['description'] = $description;
			$changes['attach']['name'] = $filename;
			$changes['attach']['size'] = $filesize;

			if ($old_value == '') {
            		$new_value = $filename;
			} else {
	    		$new_value = $old_value .",".$filename;
			}
			$this->Artifact->addHistory('attachment',$old_value,$new_value);

			$changes['attach']['href'] = get_server_url() .
			    "/tracker/download.php?artifact_id=".$this->Artifact->getID()."&id=$id";

			return $id;
		}
	}

	/**
	 *	delete - delete this artifact file from the db.
	 *
	 *	@return	boolean	success.
	 */
	function delete() {
	  global $Language;
 
		$old_value = $this->Artifact->getAttachedFileNames();
		$sql = "DELETE FROM artifact_file WHERE id=".$this->getID();
		//echo "sql=$sql<br>";
		$res=db_query($sql);
		if (!$res || db_affected_rows($res) < 1) {
			$this->setError('ArtifactFile: '.$Language->getText('tracker_common_file','del_err'));
			return false;
		} else {
			$new_value = $this->Artifact->getAttachedFileNames();
			$this->Artifact->addHistory('attachment',$old_value,$new_value);
			return true;
		}
	}

	/**
	 *	fetchData - re-fetch the data for this ArtifactFile from the database.
	 *
	 *	@param	int	The file_id.
	 *	@return	boolean	success.
	 */
	function fetchData($id) {
	  global $Language;

		$sql = "SELECT af.id, af.artifact_id, af.description, af.bin_data, af.filename, af.filesize, af.filetype, af.adddate, af.submitted_by, user.user_name, user.realname FROM artifact_file af, user WHERE (af.submitted_by = user.user_id) and af.id=$id";
		//echo $sql;
		$res=db_query($sql);
		if (!$res || db_numrows($res) < 1) {
			$this->setError('ArtifactFile: '.$Language->getText('tracker_common_file','invalid_id'));
			return false;
		}
		$this->data_array = db_fetch_array($res);
		db_free_result($res);
		return true;
	}

	/**
	 *	getArtifact - get the Artifact Object this ArtifactFile is associated with.
	 *
	 *	@return object	Artifact.
	 */
	function getArtifact() {
		return $this->Artifact;
	}
	
	/**
	 *	getID - get this ArtifactFile's ID.
	 *
	 *	@return	int	The id #.
	 */
	function getID() {
		return $this->data_array['id'];
	}

	/**
	 *	getName - get the filename.
	 *
	 *	@return string filename.
	 */
	function getName() {
		return $this->data_array['filename'];
	}

	/**
	 *	getType - get the type.
	 *
	 *	@return string type.
	 */
	function getType() {
		return $this->data_array['filetype'];
	}

	/**
	 *	getData - get the binary data from the db.
	 *
	 *	@return binary.
	 */
	function getData() {
		return base64_decode($this->data_array['bin_data']);
	}

	/**
	 *	getSize - get the size.
	 *
	 *	@return int size.
	 */
	function getSize() {
		return $this->data_array['filesize'];
	}

	/**
	 *	getDescription - get the description.
	 *
	 *	@return string description.
	 */
	function getDescription() {
		return $this->data_array['description'];
	}

	/**
	 *	getDate - get the date file was added.
	 *
	 *	@return int unix time.
	 */
	function getDate() {
		return $this->data_array['adddate'];
	}

	/**
	 *	getSubmittedBy - get the user_id of the submitter.
	 *
	 *	@return int user_id.
	 */
	function getSubmittedBy() {
		return $this->data_array['submitted_by'];
	}

	/**
	 *	getSubmittedRealName - get the real name of the submitter.
	 *
	 *	@return	string	name.
	 */
	function getSubmittedRealName() {
		return $this->data_array['realname'];
	}

	/**
	 *	getSubmittedUnixName - get the unix name of the submitter.
	 *
	 *	@return	string	unixname.
	 */
	function getSubmittedUnixName() {
		return $this->data_array['user_name'];
	}

}

?>
