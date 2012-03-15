<?php
/**
 *
 * Copyright (C) Villanova University 2007.
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

/*
 * @todo    Convert this to an AJAX approach to allow for client side access to
 *          images.  Also investigate local caching approach.  What about using
 *          Squid?
 */

require_once 'sys/Proxy_Request.php';

// Retrieve values from configuration file
$configArray = parse_ini_file('conf/config.ini', true);

// Proxy server settings
if (isset($configArray['Proxy']['host'])) {
  if (isset($configArray['Proxy']['port'])) {
    $proxy_server = $configArray['Proxy']['host'].":".$configArray['Proxy']['port'];
  } else {
    $proxy_server = $configArray['Proxy']['host'];
  }
  $proxy = array('http' => array('proxy' => "tcp://$proxy_server", 'request_fulluri' => true));
  stream_context_get_default($proxy);
}

if (!count($_GET)) {
    dieWithFailImage();
}

// Sanitize incoming parameters to avoid filesystem attacks.  We'll make sure the
// provided size matches a whitelist, and we'll strip illegal characters from the
// ISBN.
$validSizes = array('small', 'medium', 'large');
if (!in_array($_GET['size'], $validSizes)) {
    dieWithFailImage();
}
$_GET['isn'] = preg_replace('/[^0-9xX]/', '', $_GET['isn']);

// Do we have a non-empty ISBN?
if (isset($_GET['isn']) && !empty($_GET['isn'])) {
    $localFile = 'images/covers/' . $_GET['size'] . '/' . $_GET['isn'] . '.jpg';
    if (is_readable($localFile)) {
        // Load local cache if available
        header('Content-type: image/jpeg');
        echo readfile($localFile);
    } else {
        // Fetch from provider
        if (isset($configArray['Content']['coverimages'])) {
            $providers = explode(',', $configArray['Content']['coverimages']);
            $success = 0;
            foreach ($providers as $provider) {
                $provider = explode(':', $provider);
                $func = $provider[0];
                $key = $provider[1];
                if ($func($key)) {
                    $success = 1;
                    break;
                }
            }
            if (!$success) {
                dieWithFailImage();
            }
        } else {
            dieWithFailImage();
        }
    }
} else {
    dieWithFailImage();
}

/**
 * Display a "cover unavailable" graphic and terminate execution.
 */
function dieWithFailImage()
{
    header('Content-type: image/gif');
    echo readfile('images/noCover2.gif');
    exit();
}

/**
 * Load image from URL, store in cache if requested, display if possible.
 *
 * @param   $url        URL to load image from
 * @param   $cache      Boolean -- should we store in local cache?
 * @return  bool        True if image displayed, false on failure.
 */
function processImageURL($url, $cache = true)
{
    global $localFile;
    
    if ($image = @file_get_contents($url)) {
        // Figure out file paths -- $tempFile will be used to store the downloaded
        // image for analysis.  $finalFile will be used for long-term storage if
        // $cache is true or for temporary display purposes if $cache is false.
        $tempFile = str_replace('.jpg', uniqid(), $localFile);
        $finalFile = $cache ? $localFile : $tempFile . '.jpg';
        
        // If some services can't provide an image, they will serve a 1x1 blank
        // or give us invalid image data.  Let's analyze what came back before
        // proceeding.
        if (!@file_put_contents($tempFile, $image)) {
            die("Unable to write to image directory.");
        }
        list($width, $height, $type) = @getimagesize($tempFile);
        
        // File too small -- delete it and report failure.
        if ($width < 2 && $height < 2) {
            @unlink($tempFile);
            return false;
        }
        
        // Conversion needed -- do some normalization for non-JPEG images:
        if ($type != IMAGETYPE_JPEG) {
            // We no longer need the temp file:
            @unlink($tempFile);
            
            // Try to create a GD image and rewrite as JPEG, fail if we can't:
            if (!($imageGD = @imagecreatefromstring($image))) {
                return false;
            }
            if (!@imagejpeg($imageGD, $finalFile)) {
                return false;
            }
        } else {
            // If $tempFile is already a JPEG, let's store it in the cache.
            @rename($tempFile, $finalFile);
        }
        
        // Display the image:
        header('Content-type: image/jpeg');
        readfile($finalFile);
        
        // If we don't want to cache the image, delete it now that we're done.
        if (!$cache) {
            @unlink($finalFile);
        }
        
        return true;
    } else {
        return false;
    }
}

function syndetics($id)
{
    global $configArray;

    switch ($_GET['size']) {
        case 'small':
            $size = 'SC.GIF';
            break;
        case 'medium':
            $size = 'MC.GIF';
            break;
        case 'large':
            $size = 'LC.JPG';
            break;
    }

    $url = isset($configArray['Syndetics']['url']) ? 
            $configArray['Syndetics']['url'] : 'http://syndetics.com';
    $url .= "/index.aspx?type=xw12&isbn={$_GET['isn']}/{$size}&client={$id}";
    return processImageURL($url);
}

function librarything($id)
{
    $url = 'http://covers.librarything.com/devkey/' . $id . '/' . $_GET['size'] . '/isbn/' . $_GET['isn'];
    return processImageURL($url);
}

function openlibrary()
{
    // Convert internal size value to openlibrary equivalent:
    switch($_GET['size']) {
        case 'large':
            $size = 'L';
            break;
        case 'medium': 
            $size = 'M';
            break;
        case 'small':
        default:
            $size = 'S';
            break;
    }
    
    // Retrieve the image; the default=false parameter indicates that we want a 404
    // if the ISBN is not supported.
    $url = "http://covers.openlibrary.org/b/isbn/{$_GET['isn']}-{$size}.jpg?default=false";
    return processImageURL($url);
}

function google()
{
    if (is_callable('json_decode')) {
        $url = 'http://books.google.com/books?jscmd=viewapi&' .
               'bibkeys=ISBN:' . $_GET['isn'] . '&callback=addTheCover';
        $client = new Proxy_Request();
        $client->setMethod(HTTP_REQUEST_METHOD_GET);
        $client->setURL($url);

        $result = $client->sendRequest();
        if (!PEAR::isError($result)) {
            $json = $client->getResponseBody();

            // strip off addthecover( -- note that we need to account for length of ISBN (10 or 13)
            $json = substr($json, 21 + strlen($_GET['isn']));
            // strip off );
            $json = substr($json, 0, -3);
            // convert \x26 to &
            $json = str_replace("\\x26", "&", $json);
            if ($json = json_decode($json, true)) {
                return processImageURL($json['thumbnail_url'], false);
            }
        }
    }
    return false;
}

function amazon($id)
{
    require_once 'sys/Amazon.php';
    require_once 'XML/Unserializer.php';

    $params = array('ResponseGroup' => 'Images', 'ItemId' => $_GET['isn']);
    $request = new AWS_Request($id, 'ItemLookup', $params);
    $result = $request->sendRequest();
    if (!PEAR::isError($result)) {
        $unxml = new XML_Unserializer();
        $unxml->unserialize($result);
        $data = $unxml->getUnserializedData();
        if (PEAR::isError($data)) {
            return false;
        }
        if (!$data['Items']['Item']['ASIN']) {
            $data['Items']['Item'] = $data['Items']['Item'][0];
        }
        if (isset($data['Items']['Item'])) {
            // Where in the XML can we find the URL we need?
            switch ($_GET['size']) {
                case 'small':
                    $imageIndex = 'SmallImage';
                    break;
                case 'medium':
                    $imageIndex = 'MediumImage';
                    break;
                case 'large':
                    $imageIndex = 'LargeImage';
                    break;
                default:
                    $imageIndex = false;
                    break;
            }
            
            // Does a URL exist?
            if ($imageIndex && isset($data['Items']['Item'][$imageIndex]['URL'])) {
                $imageUrl = $data['Items']['Item'][$imageIndex]['URL'];
                return processImageURL($imageUrl, false);
            }
        }
    }

    return false;
}
?>
