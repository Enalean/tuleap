<?php
/**
  *
  * Copyright (c) Demian Katz 2010.
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
require_once '../util/util.inc.php';        // set up util environment
require_once 'sys/Proxy_Request.php';

// Read Config files
$configArray = parse_ini_file('../web/conf/config.ini', true);
$oaiSettings = @parse_ini_file('oai.ini', true);
if (empty($oaiSettings)) {
    die("Please add OAI-PMH settings to oai.ini.\n");
}

// If first command line parameter is set, see if we can limit to just the
// specified OAI harvester:
if (isset($argv[1])) {
    if (isset($oaiSettings[$argv[1]])) {
        $oaiSettings = array($argv[1] => $oaiSettings[$argv[1]]);
    } else {
        die("Could not load settings for {$argv[1]}.\n");
    }
}

// Loop through all the settings and perform harvests:
$processed = 0;
foreach($oaiSettings as $target => $settings) {
    if (!empty($target) && !empty($settings)) {
        echo "Processing {$target}...\n";
        $harvest = new HarvestOAI($target, $settings);
        $harvest->launch();
        $processed++;
    }
}

// All done.
die("Completed without errors -- {$processed} source(s) processed.\n");

/**
 * HarvestOAI Class
 *
 * This class harvests records via OAI-PMH using settings from oai.ini.
 *
 * @author      Demian Katz
 * @access      public
 */
class HarvestOAI
{
    private $baseURL;               // URL to harvest from
    private $set = null;            // Target set to harvest (null for all records)
    private $metadata = 'oai_dc';   // Metadata type to harvest
    private $idPrefix = '';         // OAI prefix to strip from ID values
    private $basePath;              // Directory for storing harvested files
    private $lastHarvestFile;       // File for tracking last harvest date
    private $startDate = null;      // Harvest start date (null for all records)
    private $granularity = 'auto';  // Date granularity

    // As we harvest records, we want to track the most recent date encountered
    // so we can set a start point for the next harvest.
    private $endDate = 0;

    /**
     * Constructor.
     *
     * @access  public
     * @param   string  $target         Target directory for harvest.
     * @param   array   $settings       OAI-PMH settings from oai.ini.
     */
    public function __construct($target, $settings)
    {
        global $configArray;

        // Don't time out during harvest!!
        set_time_limit(0);

        // Set up base directory for harvested files:
        $this->setBasePath($target);

        // Check if there is a file containing a start date:
        $this->lastHarvestFile = $this->basePath . 'last_harvest.txt';
        $this->loadLastHarvestedDate();

        // Set up base URL:
        if (empty($settings['url'])) {
            die("Missing base URL for {$target}.\n");
        }
        $this->baseURL = $settings['url'];
        if (isset($settings['set'])) {
            $this->set = $settings['set'];
        }
        if (isset($settings['metadataPrefix'])) {
            $this->metadata = $settings['metadataPrefix'];
        }
        if (isset($settings['idPrefix'])) {
            $this->idPrefix = $settings['idPrefix'];
        }
        if (isset($settings['dateGranularity'])) {
            $this->granularity = $settings['dateGranularity'];
        }
        if ($this->granularity == 'auto') {
            $this->loadGranularity();
        }
    }

    /**
     * Set a start date for the harvest (only harvest records AFTER this date).
     *
     * @access  public
     * @param   string      $date       Start date (YYYY-MM-DD format).
     */
    public function setStartDate($date)
    {
        $this->startDate = $date;
    }

    /**
     * Harvest all available documents.
     *
     * @access  public
     */
    public function launch()
    {
        $this->getRecordsByDate($this->startDate, $this->set);
    }

    /**
     * Set up directory structure for harvesting (support method for constructor).
     *
     * @access  private
     * @param   string  $target         The OAI-PMH target directory to create.
     */
    private function setBasePath($target)
    {
        // Get the base VuFind path:
        $home = getenv('VUFIND_HOME');
        if (empty($home)) {
            die("Please set the VUFIND_HOME environment variable.\n");
        }

        // Build the full harvest path:
        $this->basePath = $home . '/harvest/' . $target . '/';

        // Create the directory if it does not already exist:
        if (!is_dir($this->basePath)) {
            if (!mkdir($this->basePath)) {
                die("Problem creating directory {$this->basePath}.\n");
            }
        }
    }

    /**
     * Retrieve the date from the "last harvested" file and use it as our start
     * date if it is available.
     *
     * @access  private
     */
    private function loadLastHarvestedDate()
    {
        if (file_exists($this->lastHarvestFile)) {
            $lines = file($this->lastHarvestFile);
            if (is_array($lines)) {
                $date = trim($lines[0]);
                if (!empty($date)) {
                    $this->setStartDate(trim($date));
                }
            }
        }
    }

    /**
     * Normalize a date to a Unix timestamp.
     *
     * @param   string  $date           Date (ISO-8601 or YYYY-MM-DD HH:MM:SS)
     * @return  integer                 Unix timestamp (or false if $date invalid)
     * @access  protected
     */
    protected function normalizeDate($date)
    {
        // Remove timezone markers -- we don't want PHP to outsmart us by adjusting
        // the time zone!
        $date = str_replace(array('T', 'Z'), array(' ', ''), $date);
        
        // Translate to a timestamp:
        return strtotime($date);
    }

    /**
     * Save a date to the "last harvested" file.
     *
     * @access  private
     * @param   string      $date       Date to save.
     */
    private function saveLastHarvestedDate($date)
    {
        file_put_contents($this->lastHarvestFile, $date);
    }

    /**
     * Make an OAI-PMH request.  Die if there is an error; return a SimpleXML object
     * on success.
     *
     * @access  private
     * @param   string      $verb       OAI-PMH verb to execute.
     * @param   array       $params     GET parameters for ListRecords method.
     * @return  object                  SimpleXML-formatted response.
     */
    private function sendRequest($verb, $params = array())
    {
        // Set up the request:
        $request = new Proxy_Request();
        $request->setMethod(HTTP_REQUEST_METHOD_GET);
        $request->setURL($this->baseURL);

        // Load request parameters:
        $request->addQueryString('verb', $verb);
        foreach($params as $key => $value) {
            $request->addQueryString($key, $value);
        }

        // Perform request and die on error:
        $result = $request->sendRequest();
        if (PEAR::isError($result)) {
            die($result->getMessage() . "\n");
        }

        // If we got this far, there was no error -- send back response.
        $response = $request->getResponseBody();
        return $this->processResponse($response);
    }

    /**
     * Process an OAI-PMH response into a SimpleXML object.  Die if an error is
     * detected.
     *
     * @access  private
     * @param   string      $xml        OAI-PMH response XML.
     * @return  object                  SimpleXML-formatted response.
     */
    private function processResponse($xml)
    {
        // Parse the XML:
        $result = simplexml_load_string($xml);
        if (!$result) {
            die("Problem loading XML: {$xml}\n");
        }

        // Detect errors and die if one is found:
        if ($result->error) {
            $attribs = $result->error->attributes();
            die("OAI-PMH error -- code: {$attribs['code']}, value: {$result->error}\n");
        }

        // If we got this far, we have a valid response:
        return $result;
    }

    /**
     * Get the filename for a specific record ID.
     *
     * @access  private
     * @param   string      $id         ID of record to save.
     * @param   string      $ext        File extension to use.
     * @return                          Full path + filename.
     */
    private function getFilename($id, $ext)
    {
        return $this->basePath . time() . '_' . $id . '.' . $ext;
    }

    /**
     * Create a tracking file to record the deletion of a record.
     *
     * @access  private
     * @param   string      $id         ID of deleted record.
     */
    private function saveDeletedRecord($id)
    {
        $filename = $this->getFilename($id, 'delete');
        file_put_contents($filename, $id);
    }

    /**
     * Save a record to disk.
     *
     * @access  private
     * @param   string      $id         ID of record to save.
     * @param   object      $metadata   Metadata to save (in SimpleXML format).
     */
    private function saveRecord($id, $metadata)
    {
        // Extract the actual metadata from inside the <metadata></metadata> tags;
        // there is probably a cleaner way to do this, but this simple method avoids
        // the complexity of dealing with namespaces in SimpleXML:
        $xml = trim($metadata->asXML());
        $xml = preg_replace('/(^<metadata>)|(<\/metadata>$)/m', '', $xml);
        file_put_contents($this->getFilename($id, 'xml'), trim($xml));
    }

    /**
     * Load date granularity from the server.
     *
     * @access  private
     */
    private function loadGranularity()
    {
        echo "Autodetecting date granularity... ";
        $response = $this->sendRequest('Identify');
        $this->granularity = (string)$response->Identify->granularity;
        echo "found {$this->granularity}.\n";
    }

    /**
     * Extract the ID from a record object (support method for processRecords()).
     *
     * @access  private
     * @param   object      $record     SimpleXML record.
     * @return  string                  The ID value.
     */
    private function extractID($record)
    {
        // Normalize to string:
        $id = (string)$record->header->identifier;

        // Strip prefix if found:
        if (substr($id, 0, strlen($this->idPrefix)) == $this->idPrefix) {
            $id = substr($id, strlen($this->idPrefix));
        }

        // Return final value:
        return $id;
    }

    /**
     * Save harvested records to disk and track the end date.
     *
     * @access  private
     * @param   object      $records    SimpleXML records.
     */
    private function processRecords($records)
    {
        echo 'Processing ' . count($records) . " records...\n";

        // Loop through the records:
        foreach($records as $record) {
            // Die if the record is missing its header:
            if (empty($record->header)) {
                die("Unexpected missing record header.\n");
            }

            // Get the ID of the current record:
            $id = $this->extractID($record);

            // Save the current record, either as a deleted or as a regular file:
            $attribs = $record->header->attributes();
            if (strtolower($attribs['status']) == 'deleted') {
                $this->saveDeletedRecord($id);
            } else if (!isset($record->metadata)) {
                die("Unexpected missing record metadata.\n");
            } else {
                $this->saveRecord($id, $record->metadata);
            }

            // If the current record's date is newer than the previous end date,
            // remember it for future reference:
            $date = $this->normalizeDate($record->header->datestamp);
            if ($date && $date > $this->endDate) {
                $this->endDate = $date;
            }
        }
    }

    /**
     * Harvest records using OAI-PMH.
     *
     * @access  private
     * @param   array       $params     GET parameters for ListRecords method.
     */
    private function getRecords($params)
    {
        // Make the OAI-PMH request:
        $response = $this->sendRequest('ListRecords', $params);

        // Save the records from the response:
        if ($response->ListRecords->record) {
            $this->processRecords($response->ListRecords->record);
        }

        // If we have a resumption token, keep going; otherwise, we're done -- save
        // the end date.
        if ($response->ListRecords->resumptionToken) {
            $this->getRecordsByToken($response->ListRecords->resumptionToken);
        } else if ($this->endDate > 0) {
            $dateFormat = ($this->granularity == 'YYYY-MM-DD') ?
                'Y-m-d' : 'Y-m-d\TH:i:s\Z';
            $this->saveLastHarvestedDate(date($dateFormat, $this->endDate));
        }
    }

    /**
     * Harvest records via OAI-PMH using date and set.
     *
     * @access  private
     * @param   string      $date       Harvest start date (null for all records).
     * @param   string      $set        Set to harvest (null for all records).
     */
    private function getRecordsByDate($date = null, $set = null)
    {
        $params = array('metadataPrefix' => $this->metadata);
        if (!empty($date)) {
            $params['from'] = $date;
        }
        if (!empty($set)) {
            $params['set'] = $set;
        }
        $this->getRecords($params);
    }

    /**
     * Harvest records via OAI-PMH using resumption token.
     *
     * @access  private
     * @param   string      $token      Resumption token.
     */
    private function getRecordsByToken($token)
    {
        $this->getRecords(array('resumptionToken' => $token));
    }
}


?>