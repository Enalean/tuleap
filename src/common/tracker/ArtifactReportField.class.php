<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id:ArtifactReportField.class.php 4446 2006-12-08 16:18:48 +0000 (Fri, 08 Dec 2006) ahardyau $
//
//  Written for CodeX by Stephane Bouhet
//

//require_once('common/tracker/ArtifactField.class.php');

//
// This class inherits from ArtifactField
//
class ArtifactReportField extends ArtifactField {

	// Show this field for the query
	var $show_on_query;
	
	// Show this for the result 
	var $show_on_result;
	
	// The place order for the query
	var $place_query;
	
	// The place order for the result
	var $place_result;
	
	// The column width
	var $col_width;

	/**
	 *  Constructor.
	 *
	 *	@param		
	 *	@return	boolean	success.
	 */
	function ArtifactReportField() {
		$this->ArtifactField();
		return true;
	}
	
	/**
	 *  Set the different field attributes, specific to a report field
	 *
	 *	@param field_array: the array of these attributes
	 *
	 */
	function setReportFieldsFromArray($field_array) {
		//echo "setReportFieldsFromArray<br>";
		$this->show_on_query = $field_array['show_on_query'];
		$this->show_on_result = $field_array['show_on_result'];
		$this->place_query = $field_array['place_query'];
		$this->place_result = $field_array['place_result'];
		$this->col_width = $field_array['col_width'];
		
	}
	
	/**
	 *  Get the attribute show_on_query
	 *
	 *	@return string
	 *
	 */
	function getShowOnQuery() {
		return $this->show_on_query;
	}
	
	/**
	 *  Get the attribute show_on_result
	 *
	 *	@return string
	 *
	 */
	function getShowOnResult() {
		return $this->show_on_result;
	}
	
	/**
	 *  Return if the show_on_query attribute is equal to 1
	 *
	 *	@return boolean
	 *
	 */
	function isShowOnQuery() {
		return ( $this->show_on_query == 1 );
	}

	/**
	 *  Return if the show_on_result attribute is equal to 1
	 *
	 *	@return string
	 *
	 */
	function isShowOnResult() {
		return ( $this->show_on_result == 1 );
	}

	/**
	 *  Get the attribute place_query
	 *
	 *	@return string
	 *
	 */
	function getPlaceQuery() {
		return $this->place_query;
	}
	
	/**
	 *  Get the attribute place_result
	 *
	 *	@return string
	 *
	 */
	function getPlaceResult() {
		return $this->place_result;
	}
	
	/**
	 *  Get the attribute col_width
	 *
	 *	@return int
	 *
	 */
	function getColWidth() {
		return $this->col_width;
	}

	/**
	 *  Dump the object
	 *
	 */
	function dump() {
		return "show_on_query=".$this->show_on_query.
			   " - show_on_result=".$this->show_on_result.
			   " - place_query=".$this->place_query.
			   " - place_result=".$this->place_result.
			   " - col_width=".$this->col_width;
	}		
}

?>
