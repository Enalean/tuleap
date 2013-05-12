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
	
	function sendDocumentIdToDocman(docid, defcs)
	{
		if (defcs == 'no') {
			window.opener.document.getElementById("cs_docid").value = docid;
		} else {
			window.opener.document.getElementById("default_dropbox_id").value = docid;
		}
		window.close();
	}
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
 
require_once('pre.php');
require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('www/project/export/project_export_utils.php');

require_once('../include/CloudStorageDao.class.php');
 
require_once("DropboxClient.php");

if(isset($_GET['folder'])) { $path = $_GET['folder']; } else { $path = ""; }
if(isset($_GET['docman'])) { $is_docman = "yes"; } else { $is_docman = "no"; }
if(isset($_GET['default'])) { $default = "yes"; } else { $default = "no"; }

// get default dropbox folder id
$cloudstorage_dao = new CloudstorageDao(CodendiDataAccess::instance());
$res_dropbox_defcsid = $cloudstorage_dao->select_default_cloudstorage_id('dropbox');

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
	$return_url = "https://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?group_id=1&action=dropbox&auth_callback=1";
	$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
	$request_token = $dropbox->GetRequestToken();
	store_token($request_token, $request_token['t']);
	die("Authentication required. <a href='$auth_url'>Click here.</a>");
}

//echo "<pre>";
//echo "<b>Account:</b>\r\n";
//print_r($dropbox->GetAccountInfo());

$files = $dropbox->GetFiles("",false);

//print_r(array_keys($files));

if(!empty($files)) {
	$file = reset($files);
	$test_file = "test_download_".basename($file->path);
	
	// if path is null and default folder id setup by user is not null 
	// then apply user default path
	if ($path == "" && $res_dropbox_defcsid != "" & $is_docman == "no") {
		$path = $res_dropbox_defcsid;
	}
	
	if ($path != "")
		echo "\r\n\r\n<b>Files of <a href='".$dropbox->GetLink($path)."'>$path</a>:</b>\r\n";
	else
		echo "\r\n\r\n<b>Files of root folder:</b>\r\n";
		
	//print_r($dropbox->GetMetadata($file->path));
	
	//print_r(objectToArray($dropbox->GetMetadata($file->path)));
	
	$arrMetadata = objectToArray($dropbox->GetMetadata($path));
	//print_r($arrMetadata[contents]);
	//str_replace($path,"",contents);
	
	print_results_header($is_docman);
	
    $content = $arrMetadata["contents"];
    for($i = 0; $i < sizeof($content); $i = $i + 1){
        $tabMetElt = (array)$content[$i];
        print_results_body($tabMetElt, $path, $is_docman, $default);
    }
    
	print_results_footer();              
	
	//echo array2table($arrMetadata[contents], true, $path);
	
	//echo "\r\n\r\n<b>Downloading $file->path:</b>\r\n";
	//print_r($dropbox->DownloadFile($file, $test_file));
		
	//echo "\r\n\r\n<b>Uploading $test_file:</b>\r\n";
	//print_r($dropbox->UploadFile($test_file));
	//echo "\r\n done!";
}

function print_results_header($docman)
{
	echo ("
		<table id=\"data\" class=\"display\" width=\"100%\" cellspacing=\"1\" cellpadding=\"2\" border=\"0\">
			<thead>
				<tr class=\"boxtable\">
					" . (($docman == 'yes') ? " <th class=\"boxtitle\" style='width:20%;'>Link folder</th>" : "") . "
					<th class=\"boxtitle\">Title</th>
					" . (($docman == 'no') ? " <th class=\"boxtitle\">Update date</th>" : "") . "
					" . (($docman == 'no') ? " <th class=\"boxtitle\">Type</th>" : "") . "
					" . (($docman == 'no') ? " <th class=\"boxtitle\">Size</th>" : "")  ."
				</tr>
			</thead>
			<tbody>
	");
}

function print_results_body($tabElt, $path, $docman, $setDefault)
{
    if (isset($tabElt["mime_type"]) == "") {
    	$mime = "Folder";
    	$item_url = "https://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?group_id=0&action=dropbox&auth_callback=1&folder=".$tabElt["path"].(($docman == 'yes') ? "&docman=yes" : "");
    	$item = str_replace($path, null, $tabElt["path"]);
    } else {
    	$mime = $tabElt["mime_type"];
    	$item_url = "";
    	$item = substr(str_replace($path, null, $tabElt["path"]), 1);
	}
    	
    if (isset($tabElt["mime_type"]) == "" && $docman == 'yes' || $docman == 'no'){
		echo("
			<tr class=\"boxitem\">
				" . (($docman == 'yes') ? " <td style='cursor:pointer; text-align:center;'><img src='themes/img/link.png' alt='Link' onclick='javascript:sendDocumentIdToDocman(\"".$item."\", \"".$setDefault."\");'/></td>" : "") . "
				<td><a href='".$item_url."'>".$item."</a></td>
				" . (($docman == 'no') ? " <td>".$tabElt["modified"]."</td>" : "") . "  
				" . (($docman == 'no') ? " <td>".clean_body_results($mime)."</td>" : "") . "
				" . (($docman == 'no') ? " <td>".$tabElt["size"]."</td>" : "") . "
			</tr>
		");
	}
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

function clean_body_results($content = "")
{
	$content = str_replace("text/plain","Fichier texte",$content);
	$content = str_replace("text/x-java","Fichier java",$content);
	$content = str_replace("application/msword","Word document",$content);
	$content = str_replace("application/vnd.openxmlformats-officedocument.wordprocessingml.document","Word document",$content);
	$content = str_replace("application/vnd.openxmlformats-officedocument.presentationml.presentation","Powerpoint presentation",$content);
	$content = str_replace("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet","Excel spreadsheet",$content);
	$content = str_replace("application/pdf","PDF file",$content);
	$content = str_replace("audio/x-mpegurl","Audio file",$content);
	$content = str_replace("application/x-msdos-program","Executable file",$content);
	$content = str_replace("application/octet-stream","Binary file",$content);
	$content = str_replace("image/gif","Image gif file",$content);
	$content = str_replace("image/png","Image png file",$content);
	$content = str_replace("image/jpeg","Image jpeg file",$content);
	$content = str_replace("image/jpg","Image jpg file",$content);
	return $content;
} 

function clean_header_results($content = "")
{
	$content = str_replace("path","File",$content);
	$content = str_replace("mime_type","Type",$content);
	$content = str_replace("modified","Update date",$content);
	$content = str_replace("size","Size",$content);
	return $content;
}
?>
