<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" ></script>
<script type="text/javascript" src="themes/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="themes/js/ColReorder.min.js"></script>
<script type="text/javascript">	
	jQuery.noConflict();
	jQuery(document).ready(function(){
		jQuery('#data').dataTable( {
			"bPaginate": true,
			"aaSorting": [[ 1, "desc" ]],
			"sDom": 'Rlfrtip',
		});	
	});
</script>

<?php
/** 
 * DropPHP Metadata
 *
 * http://fabi.me/en/php-projects/dropphp-dropbox-api-client/
 *
 * @author     Fabian Schlieper <fabian@fabi.me>
 * @copyright  Fabian Schlieper 2012
 * @version    1.0
 * @license    See license.txt
 *
 */
 
error_reporting(E_ALL);

require_once("DropboxClient.php");

// you have to create an app at https://www.dropbox.com/developers/apps and enter details below:
$dropbox = new DropboxClient(array(
	'app_key' => "4aau6o4vokri8j8", 
	'app_secret' => "rngmtyykevrer3i",
	'app_full_access' => true,
),'en');


// first try to load existing access token
$access_token = load_token("access");
if(!empty($access_token)) {
	$dropbox->SetAccessToken($access_token);
	//print_r($access_token);
}
elseif(!empty($_GET['auth_callback'])) // are we coming from dropbox's auth page?
{
	// then load our previosly created request token
	$request_token = load_token($_GET['oauth_token']);
	if(empty($request_token)) die('Request token not found!');
	
	// get & store access token, the request token is not needed anymore
	$access_token = $dropbox->GetAccessToken($request_token);	
	store_token($access_token, "access");
	delete_token($_GET['oauth_token']);
}

// checks if access token is required
if(!$dropbox->IsAuthorized())
{
	// redirect user to dropbox auth page
	$return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?group_id=0&action=dropbox&auth_callback=1";
	$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
	$request_token = $dropbox->GetRequestToken();
	store_token($request_token, $request_token['t']);
	die("Authentication required. <a href='$auth_url'>Click here.</a>");
}

//echo "<pre>";
//echo "<b>Account:</b>\r\n";
//print_r($dropbox->GetAccountInfo());

$path = "/M1 ISC 2012_2013/";
$files = $dropbox->GetFiles("",false);

echo "\r\n\r\n<b>Files:</b>\r\n";
//print_r(array_keys($files));

if(!empty($files)) {
	$file = reset($files);
	$test_file = "test_download_".basename($file->path);
	
	echo "\r\n\r\n<b>Meta data of <a href='".$dropbox->GetLink($path)."'>$path</a>:</b>\r\n";
	//print_r($dropbox->GetMetadata($file->path));
	
	//print_r(objectToArray($dropbox->GetMetadata($file->path)));
	
	$arrMetadata = objectToArray($dropbox->GetMetadata($path));
	//print_r($arrMetadata[contents]);
	//str_replace($path,"",contents);
	
	print_results_header();
	
    $content = $arrMetadata["contents"];
    for($i = 0; $i < sizeof($content); $i = $i + 1){
        $tabMetElt = (array)$content[$i];
        print_results_body($tabMetElt, $path);
    }
    
	print_results_footer();              
	
	//echo array2table($arrMetadata[contents], true, $path);
	
	//echo "\r\n\r\n<b>Downloading $file->path:</b>\r\n";
	//print_r($dropbox->DownloadFile($file, $test_file));
		
	//echo "\r\n\r\n<b>Uploading $test_file:</b>\r\n";
	//print_r($dropbox->UploadFile($test_file));
	//echo "\r\n done!";
}

function print_results_header()
{
	echo ("
		<table id=\"data\" class=\"display\" width=\"100%\" cellspacing=\"1\" cellpadding=\"2\" border=\"0\">
			<thead>
				<tr class=\"boxtable\">
					<th class=\"boxtitle\">Fichier</th>
					<th class=\"boxtitle\">Date de création</th>
					<th class=\"boxtitle\">Type</th>
					<th class=\"boxtitle\">Taille</th>
				</tr>
			</thead>
			<tbody>
	");
}

function print_results_body($tabElt, $path)
{
    if ($tabElt["mime_type"] == "")
    	$mime = "Folder";
    else
    	$mime = $tabElt["mime_type"];
	echo("
		<tr class=\"boxitem\">
			<td>".str_replace($path, null, $tabElt["path"])."</td>
			<td>".$tabElt["modified"]."</td>
			<td>".$mime."</td>
			<td>".$tabElt["size"]."</td>
		</tr>
	");
}

function print_results_footer()
{
	echo("
			</tbody>
		</table>
		<div style=\"clear:both\"></div>
	");
}

function store_token($token, $name)
{
	if(!file_put_contents("tokens/$name.token", serialize($token)))
		die('<br />Could not store token! <b>Make sure that the directory `tokens` exists and is writable!</b>');
}

function load_token($name)
{
	if(!file_exists("tokens/$name.token")) return null;
	return @unserialize(@file_get_contents("tokens/$name.token"));
}

function delete_token($name)
{
	@unlink("tokens/$name.token");
}

function objectToArray($d) {
	if (is_object($d)) {
		// Gets the properties of the given object
		// with get_object_vars function
		$d = get_object_vars($d);
	}
 
	if (is_array($d)) {
		/*
		 * Return array converted to object
		 * Using __FUNCTION__ (Magic constant)
		 * for recursive call
		 */
		return array_map(__FUNCTION__, $d);
	}
	else 
	{
		// Return array
		return $d;
	}
}

/**
 * Translate a result array into a HTML table
 *
 * @author      Aidan Lister <aidan@php.net>
 * @version     1.3.2
 * @link        http://aidanlister.com/2004/04/converting-arrays-to-human-readable-tables/
 * @param       array  $array      The result (numericaly keyed, associative inner) array.
 * @param       bool   $recursive  Recursively generate tables for multi-dimensional arrays
 * @param       string $null       String to output for blank cells
 */
function array2table($array, $recursive = false, $path, $null = '&nbsp;')
{
    // Sanity check
    if (empty($array) || !is_array($array)) {
        return false;
    }
 
    if (!isset($array[0]) || !is_array($array[0])) {
        $array = array($array);
    }
 
    // Start the table
    $table = "<table id=\"data\" class=\"display\" width=\"100%\" cellspacing=\"1\" cellpadding=\"2\" border=\"0\">\n";
 
    // The header
    $table .= "\t<thead><tr class=\"boxtable\">";
    // Take the keys from the first row as the headings
    foreach (array_keys($array[0]) as $heading) {
        $table .= '<th class="boxtitle">' . clean_header_results(null, $heading) . '</th>';
    }
    $table .= "</tr></thead>\n";
 
    // The body
    $table .= "<tbody>\n";
    foreach ($array as $row) {
    	if (sizeof($row)>10)
    	{
        $table .= "\t<tr class=\"boxitem\">" ;
        foreach ($row as $cell) 
        {
            $table .= '<td>';
 
            // Cast objects
            if (is_object($cell)) { $cell = (array) $cell; }
             
            if ($recursive === true && is_array($cell) && !empty($cell)) 
            {
                // Recursive mode
                $table .= "\n" . array2table($cell, true, true) . "\n";
            } 
            else 
            {
                $table .= (strlen($cell) > 0) ?
                    htmlspecialchars((string) clean_body_results($path, $cell)) : $null;
            }

            $table .= '</td>';
        }
 
        $table .= "</tr>\n";
        }
    }
    $table .= "</tbody>";
 
    $table .= '</table>';
    
    $table .= '<div style="clear:both" />';
    return $table;
}

function clean_body_results($path = "", $cell = "")
{
	$cell = str_replace($path,"",$cell);
	$cell = str_replace("text/plain","Fichier texte",$cell);
	$cell = str_replace("text/x-java","Fichier java",$cell);
	$cell = str_replace("application/vnd.openxmlformats-officedocument.wordprocessingml.document","Fichier word",$cell);
	$cell = str_replace("application/pdf","Fichier PDF",$cell);
	$cell = str_replace("audio/x-mpegurl","Fichier audio",$cell);
	$cell = str_replace("application/x-msdos-program","Fichier executable",$cell);
	$cell = str_replace("application/octet-stream","Fichier binaire",$cell);
	$cell = str_replace("image/gif","Fichier image",$cell);
	$cell = str_replace("image/png","Fichier image",$cell);
	$cell = str_replace("image/jpeg","Fichier image",$cell);
	$cell = str_replace("image/jpg","Fichier image",$cell);
	return $cell;
} 

function clean_header_results($path = "", $heading = "")
{
	$heading = str_replace("path","Fichier",$heading);
	$heading = str_replace("mime_type","Type",$heading);
	$heading = str_replace("modified","Date de création",$heading);
	$heading = str_replace("size","Taille",$heading);
	return $heading;
}
?>
