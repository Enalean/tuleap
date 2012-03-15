<?php
/**
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

/**
 * Record Driver Interface
 *
 * This interface class is the definition of the required methods for 
 * interacting with a particular metadata record format.
 */
interface RecordInterface
{
    /**
     * Constructor.  We build the object using all the data retrieved 
     * from the (Solr) index (which also happens to include the 
     * 'fullrecord' field containing raw metadata).  Since we have to 
     * make a search call to find out which record driver to construct, 
     * we will already have this data available, so we might as well 
     * just pass it into the constructor.
     *
     * @param   array   $indexFields    All fields retrieved from the index.
     * @access  public
     */
    public function __construct($indexFields);

    /**
     * Get text that can be displayed to represent this record in 
     * breadcrumbs.
     *
     * @access  public
     * @return  string              Breadcrumb text to represent this record.
     */
    public function getBreadcrumb();

    /**
     * Assign necessary Smarty variables and return a template name 
     * to load in order to display the requested citation format.  
     * For legal values, see getCitationFormats().  Returns null if 
     * format is not supported.
     *
     * @param   string  $format     Citation format to display.
     * @access  public
     * @return  string              Name of Smarty template file to display.
     */
    public function getCitation($format);

    /**
     * Get an array of strings representing citation formats supported 
     * by this record's data (empty if none).  Legal values: "APA", "MLA".
     *
     * @access  public
     * @return  array               Strings representing citation formats.
     */
    public function getCitationFormats();

    /**
     * Assign necessary Smarty variables and return a template name to 
     * load in order to display core metadata (the details shown in the 
     * top portion of the record view pages, above the tabs).
     *
     * @access  public
     * @return  string              Name of Smarty template file to display.
     */
    public function getCoreMetadata();

    /**
     * Get an array of search results for other editions of the title 
     * represented by this record (empty if unavailable).  In most cases, 
     * this will use the XISSN/XISBN logic to find matches.
     *
     * @access  public
     * @return  mixed               Editions in index engine result format.
     *                              (or null if no hits, or PEAR_Error object).
     */
    public function getEditions();

    /**
     * Get the text to represent this record in the body of an email.
     *
     * @access  public
     * @return  string              Text for inclusion in email.
     */
    public function getEmail();

    /**
     * Get any excerpts associated with this record.  For details of
     * the return format, see sys/Excerpts.php.
     *
     * @access  public
     * @return  array               Excerpt information.
     */
    public function getExcerpts();

    /**
     * Assign necessary Smarty variables and return a template name to 
     * load in order to export the record in the requested format.  For 
     * legal values, see getExportFormats().  Returns null if format is 
     * not supported.
     *
     * @param   string  $format     Export format to display.
     * @access  public
     * @return  string              Name of Smarty template file to display.
     */
    public function getExport($format);

    /**
     * Get an array of strings representing formats in which this record's 
     * data may be exported (empty if none).  Legal values: "RefWorks", 
     * "EndNote", "MARC", "RDF".
     *
     * @access  public
     * @return  array               Strings representing export formats.
     */
    public function getExportFormats();

    /**
     * Assign necessary Smarty variables and return a template name to 
     * load in order to display extended metadata (more details beyond 
     * what is found in getCoreMetadata() -- used as the contents of the 
     * Description tab of the record view).
     *
     * @access  public
     * @return  string              Name of Smarty template file to display.
     */
    public function getExtendedMetadata();

    /**
     * Assign necessary Smarty variables and return a template name to 
     * load in order to display holdings extracted from the base record 
     * (i.e. URLs in MARC 856 fields).  This is designed to supplement, 
     * not replace, holdings information extracted through the ILS driver  
     * and displayed in the Holdings tab of the record view page.  Returns 
     * null if no data is available.
     *
     * @access  public
     * @return  string              Name of Smarty template file to display.
     */
    public function getHoldings();

    /**
     * Assign necessary Smarty variables and return a template name to
     * load in order to display a summary of the item suitable for use in
     * user's favorites list.
     *
     * @access  public
     * @param   object  $user       User object owning tag/note metadata.
     * @param   int     $listId     ID of list containing desired tags/notes (or 
     *                              null to show tags/notes from all user's lists).
     * @param   bool    $allowEdit  Should we display edit controls?
     * @return  string              Name of Smarty template file to display.
     */
    public function getListEntry($user, $listId = null, $allowEdit = true);

    /**
     * Get the OpenURL parameters to represent this record (useful for the 
     * title attribute of a COinS span tag).
     *
     * @access  public
     * @return  string              OpenURL parameters.
     */
    public function getOpenURL();

    /**
     * Get an XML RDF representation of the data in this record.
     *
     * @access  public
     * @return  mixed               XML RDF data (false if unsupported or error).
     */
    public function getRDFXML();

    /**
     * Get any reviews associated with this record.  For details of
     * the return format, see sys/Reviews.php.
     *
     * @access  public
     * @return  array               Review information.
     */
    public function getReviews();

    /**
     * Assign necessary Smarty variables and return a template name to
     * load in order to display a summary of the item suitable for use in
     * search results.
     *
     * @access  public
     * @return  string              Name of Smarty template file to display.
     */
    public function getSearchResult();

    /**
     * Assign necessary Smarty variables and return a template name to 
     * load in order to display the full record information on the Staff 
     * View tab of the record view page.
     *
     * @access  public
     * @return  string              Name of Smarty template file to display.
     */
    public function getStaffView();

    /**
     * Assign necessary Smarty variables and return a template name to 
     * load in order to display the Table of Contents extracted from the 
     * record.  Returns null if no Table of Contents is available.
     *
     * @access  public
     * @return  string              Name of Smarty template file to display.
     */
    public function getTOC();

    /**
     * Return the unique identifier of this record within the Solr index;
     * useful for retrieving additional information (like tags and user
     * comments) from the external MySQL database.
     *
     * @access  public
     * @return  string              Unique identifier.
     */
    public function getUniqueID();

    /**
     * Does this record have audio content available?
     *
     * @access  public
     * @return  bool
     */
    public function hasAudio();

    /**
     * Does this record have an excerpt available?
     *
     * @access  public
     * @return  bool
     */
    public function hasExcerpt();

    /**
     * Does this record have searchable full text in the index?
     *
     * Note: As of this writing, searchable full text is not a VuFind feature,
     *       but this method will be useful if/when it is eventually added.
     *
     * @access  public
     * @return  bool
     */
    public function hasFullText();

    /**
     * Does this record have image content available?
     *
     * @access  public
     * @return  bool
     */
    public function hasImages();

    /**
     * Does this record support an RDF representation?
     *
     * @access  public
     * @return  bool
     */
    public function hasRDF();

    /**
     * Does this record have reviews available?
     *
     * @access  public
     * @return  bool
     */
    public function hasReviews();

    /**
     * Does this record have a Table of Contents available?
     *
     * @access  public
     * @return  bool
     */
    public function hasTOC();

    /**
     * Does this record have video content available?
     *
     * @access  public
     * @return  bool
     */
    public function hasVideo();
}

?>