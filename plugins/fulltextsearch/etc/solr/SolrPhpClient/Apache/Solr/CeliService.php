<?php
/**

 */

// See Issue #1 (http://code.google.com/p/solr-php-client/issues/detail?id=1)
// Doesn't follow typical include path conventions, but is more convenient for users
require_once(dirname(__FILE__) . '/Document.php');
require_once(dirname(__FILE__) . '/Response.php');
require_once(dirname(__FILE__) . '/Service.php');


/**
 * Starting point for the Solr API. Represents a Solr server resource and has
 * methods for pinging, adding, deleting, committing, optimizing and searching.
 *
 * Example Usage:
 * <code>
 * ...
 * $solr = new Apache_Solr_Service(); //or explicitly new Apache_Solr_Service('localhost', 8180, '/solr')
 *
 * if ($solr->ping())
 * {
 * 		$solr->deleteByQuery('*:*'); //deletes ALL documents - be careful :)
 *
 * 		$document = new Apache_Solr_Document();
 * 		$document->id = uniqid(); //or something else suitably unique
 *
 * 		$document->title = 'Some Title';
 * 		$document->content = 'Some content for this wonderful document. Blah blah blah.';
 *
 * 		$solr->addDocument($document); 	//if you're going to be adding documents in bulk using addDocuments
 * 										//with an array of documents is faster
 *
 * 		$solr->commit(); //commit to see the deletes and the document
 * 		$solr->optimize(); //merges multiple segments into one
 *
 * 		//and the one we all care about, search!
 * 		//any other common or custom parameters to the request handler can go in the
 * 		//optional 4th array argument.
 * 		$solr->search('content:blah', 0, 10, array('sort' => 'timestamp desc'));
 * }
 * ...
 * </code>
 *
 * @todo Investigate using other HTTP clients other than file_get_contents built-in handler. Could provide performance
 * improvements when dealing with multiple requests by using HTTP's keep alive functionality
 */
class Celi_Apache_Solr_Service extends Apache_Solr_Service
{
 //default value for update
 protected $_updateServlet;
 /*
 * Set update handler URL value . Example: update/celi, update, update/extract
 */


	/**
	 * Constructor. All parameters are optional and will take on default values
	 * if not specified.
	 *
	 * @param string $host
	 * @param string $port
	 * @param string $path
	 */
 public function __construct($host = 'localhost', $port = 8180, $path = '/solr/')
 {
  $servletvalue = 'update/celi';
  $this->_setUpdateServlet($servletvalue);
  parent::__construct($host, $port, $path );
  $this->_initUrls();
 }

 public function _setUpdateServlet($updateServlet)
 {
   $this->_updateServlet = $updateServlet;
 }

 /*
 * Get update handler URL value . Example: update/celi, update, update/extract
 */
 public function _getUpdateServlet()
 {
  return  $this->_updateServlet ;
 }
 /**
 * Construct the Full URLs for the three servlets we reference
 */
 //modify this methode , we use  _updateServlet and not  UPDATE_SERVLET
 protected function _initUrls()
 {
  //Initialize our full servlet URLs now that we have server information
  $this->_pingUrl = $this->_constructUrl(self::PING_SERVLET);
  $this->_updateUrl = $this->_constructUrl($this->_updateServlet, array('wt' => self::SOLR_WRITER ));
  $this->_searchUrl = $this->_constructUrl(self::SEARCH_SERVLET);
  $this->_threadsUrl = $this->_constructUrl(self::THREADS_SERVLET, array('wt' => self::SOLR_WRITER ));
  $this->_urlsInited = true;
 }
}