<?php
 
error_reporting(E_ALL);
require_once("DropboxClient.php");

// you have to create an app at https://www.dropbox.com/developers/apps and enter details below:
$dropbox = new DropboxClient(array(
	'app_key' => "", 
	'app_secret' => "",
	'app_full_access' => false,
),'en');

handle_dropbox_auth($dropbox); // see below

// if there is no upload, show the form
if(empty($_FILES['the_upload'])) {
?>
<form enctype="multipart/form-data" method="POST" action="">
<p>
	<label for="file">Upload File</label>
	<input type="file" name="the_upload" />
</p>
<p><input type="submit" name="submit-btn" value="Upload!"></p>
</form>
<?php } else { 

	$upload_name = $_FILES["the_upload"]["name"];
	echo "<pre>";
	echo "\r\n\r\n<b>Uploading $upload_name:</b>\r\n";
	$meta = $dropbox->UploadFile($_FILES["the_upload"]["tmp_name"], $upload_name);
	print_r($meta);
	echo "\r\n done!";
	echo "</pre>";
}


// ================================================================================
// store_token, load_token, delete_token are SAMPLE functions! please replace with your own!
function store_token($token, $name)
{
	file_put_contents("tokens/$name.token", serialize($token));
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
// ================================================================================

function handle_dropbox_auth($dropbox)
{
	// first try to load existing access token
	$access_token = load_token("access");
	if(!empty($access_token)) {
		$dropbox->SetAccessToken($access_token);
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
		$return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
		$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
		$request_token = $dropbox->GetRequestToken();
		store_token($request_token, $request_token['t']);
		die("Authentication required. <a href='$auth_url'>Click here.</a>");
	}
}