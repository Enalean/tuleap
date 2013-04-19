<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js" ></script>
<script type="text/javascript" src="themes/js/jquery.dataTables.min.js"></script>
<script type="text/javascript"> 
	jQuery.noConflict();
</script>
<script type="text/javascript">
	var CLIENT_ID = '716871252082-qrqagr432idmdrh5um2cg4monu4b42qa.apps.googleusercontent.com';
	var SCOPES = 'https://www.googleapis.com/auth/drive';
	var tabString=
		"<thead>" +
			"<tr class=\"boxtable\">" +
				"<th class=\"boxtitle\" scope=\"col\">Titre</th>" +
				"<th class=\"boxtitle\" scope=\"col\">Type</th>" +
				"<th class=\"boxtitle\" scope=\"col\">Date création</th>" +
				"<th class=\"boxtitle\" scope=\"col\">Propriétaire</th>" +
			"</tr>" +
		"</thead>"
	;
	
	/**
	 * Called when the client library is loaded to start the auth flow.
	 */
	function handleClientLoad() {
		window.setTimeout(checkAuth, 1);
	}

	/**
	 * Check if the current user has authorized the application.
	 */
	function checkAuth() 
	{
    	gapi.auth.authorize(
            { 'client_id': CLIENT_ID, 'scope': SCOPES, 'immediate': true }, handleAuthResult); 
    }

	/**
	* Called when authorization server replies.
	* @param {Object} authResult Authorization result.
	*/
	function handleAuthResult(authResult) 
	{
		var authButton = document.getElementById('authorizeButton');
        //var filePicker = document.getElementById('filePicker');
		//var directory = document.getElementById('directory');
        authButton.style.display = 'none';
        //filePicker.style.display = 'none';
        //directory.style.display = 'none';
        if (authResult && !authResult.error) 
        {
        	// Access token has been successfully retrieved, requests can be sent to the API.
        	//filePicker.style.display = 'block';
        	filesList.style.display = 'block';
		  	//directory.style.display = 'block';
          	//filePicker.onchange = uploadFile;
          	jQuery('#data').fadeOut('fast');jQuery('#load').fadeIn('slow');retrieveAllFiles(alertons); 
        	     	
        } 
        else 
        {
        	// No access token could be retrieved, show the button to start the authorization flow.
        	authButton.style.display = 'block';
        	authButton.onclick = function() 
        	{
            	gapi.auth.authorize(
                	{ 'client_id': CLIENT_ID, 'scope': SCOPES, 'immediate': false}, handleAuthResult); };
			}
		}

	/**
	 * Start the file upload.
	 * @param {Object} evt Arguments from the file selector.
	 */
	function uploadFile(evt) {
		gapi.client.load('drive', 'v2', function() {
			var file = evt.target.files[0];
			insertFile(file);
			alert("Votre fichier a bien été uploadé");
		});
	}

    /**
    * Insert new file.
    *
    * @param {File} fileData File object to read data from.
    * @param {Function} callback Function to call when the request is complete.
    */
	function insertFile(fileData, callback) 
	{
		const boundary = '-------314159265358979323846';
        const delimiter = "\r\n--" + boundary + "\r\n";
        const close_delim = "\r\n--" + boundary + "--";

        var reader = new FileReader();
        reader.readAsBinaryString(fileData);
        reader.onload = function(e) {
			var contentType = fileData.type || 'application/octet-stream';
          	var metadata = {
            	'title': fileData.name,
            	'mimeType': contentType
			};

			var base64Data = btoa(reader.result);
          	var multipartRequestBody =
				delimiter +
              	'Content-Type: application/json\r\n\r\n' +
              	JSON.stringify(metadata) +
              	delimiter +
              	'Content-Type: ' + contentType + '\r\n' +
              	'Content-Transfer-Encoding: base64\r\n' +
              	'\r\n' +
              	base64Data +
              	close_delim;

			var request = gapi.client.request({
				'path': '/upload/drive/v2/files',
              	'method': 'POST',
              	'params': {'uploadType': 'multipart'},
              	'headers': {
                	'Content-Type': 'multipart/mixed; boundary="' + boundary + '"'
              	},
				'body': multipartRequestBody});
				if (!callback) {
					callback = function(file) {
              			console.log(file)
            		};
				}
			request.execute(callback);
		}
	}
    /**
     * Insert new Directory.
     *
     * @param {Name} name of the directory
     * @param {Function} callback Function to call when the request is complete.
     */
	function insertDirectory(fileData, callback) 
	{
        data = new Object();
  		data.title = fileData;
  		data.mimeType = "application/vnd.google-apps.folder";
		gapi.client.load('drive', 'v2', function() {
  			gapi.client.drive.files.insert({'resource': data}).execute(callback);
		});
	}

	/**
	 * Retrieve a list of File resources.
	 * @param {Function} callback Function to call when the request is complete.
	 */
	function retrieveAllFiles(callback) 
	{
		document.getElementById('data').innerHTML=tabString;
	
  		var retrievePageOfFiles = function(request, result) 
  		{
    		request.execute(function(resp) 
    		{
      			result = result.concat(resp.items);
	  			for(var i = 0; i<resp.items.length;i++)
	  				printFile(resp.items[i].id);
      			var nextPageToken = resp.nextPageToken;
      			if (nextPageToken) 
      			{	
        			gapi.client.load('drive', 'v2', function() 
        			{
          				request = gapi.client.drive.files.list({
          					'pageToken': nextPageToken
        				});
        				retrievePageOfFiles(request, result);
    				});
				} 
				else 
				{	
					callback(result);
						
      			}
    		});
		}
		
		gapi.client.load('drive', 'v2', function() 
		{
			/*var initialRequest = gapi.client.drive.files.list({
				'folderId': '0B-SQbuOXoGUyUVJqVmtnbnYwXzg'
			});*/
			
			var initialRequest = gapi.client.drive.children.list({
				'folderId' : '0B-SQbuOXoGUyUVJqVmtnbnYwXzg'
			});
					
			retrievePageOfFiles(initialRequest, []);
		});
	}

	function alertons(result)
	{
		//document.getElementById('data').style.display= 'block';
		jQuery('#load').fadeOut('fast');
		jQuery('#data').fadeIn('slow');
	}

	function printFile(fileId) 
	{
		gapi.client.load('drive', 'v2', function() 
		{
			var request = gapi.client.drive.files.get({
				'fileId': fileId
			});
			
			request.execute(function(resp) 
			{
				var type="";

				switch (resp.mimeType) 
				{
					case "application/vnd.google-apps.document":
						type="Document Google";
						break;
					
					case "application/pdf":
						type="Document PDF";
						break;
					
					case "image/jpeg":
						type="Image";
						break;
					
					case "application/vnd.google-apps.folder":
						type="Dossier";
						break;
					
					case "text/plain":
						type="Document texte";
						break;
					
					case "application/vnd.google-apps.presentation":
						type="Présentation Google";
						break;
					
					case "application/vnd.openxmlformats-officedocument.presentationml.presentation":
						type="Présentation Powerpoint";
						break;
					
					case "application/vnd.openxmlformats-officedocument.wordprocessingml.document":
						type="Document Word";
						break;
					
					case "application/vnd.google-apps.spreadsheet":
						type="Tableur Google";
						break;
					
					default: 
						type="Type Inconnu";
						break;
				}

				var dateCreation=resp.createdDate.substr(8,2)+"/"+resp.createdDate.substr(5,2)+"/"+resp.createdDate.substr(0,4);

				if(resp.webContentLink != null)
				{
					clean_results = document.getElementById('data').innerHTML;
					clean_results = clean_results.replace('<tbody>', '');
					clean_results = clean_results.replace('</tbody>', '');
									
					document.getElementById('data').innerHTML = clean_results  +
						"<tr class=\"boxitem\">" +
							"<th><a id=\"a"+fileId+"\" href=\""+resp.webContentLink+"\">"+resp.title+"</a></th>" +
							"<td>"+type+"</td>" +
							"<td>"+dateCreation+"</td>" +
							"<td>"+resp.ownerNames+" ("+resp.userPermission.role+")</td>" +
						"</tr>";
				}
				else if(resp.exportLinks != null)
				{
					clean_results = document.getElementById('data').innerHTML;
					clean_results = clean_results.replace('<tbody>', '');
					clean_results = clean_results.replace('</tbody>', '');
			
					document.getElementById('data').innerHTML = clean_results  +
						"<tr class=\"boxitem\">" +
							"<td><a id=\"a"+fileId+"\" href=\""+resp.exportLinks["application/pdf"]+"\">"+resp.title+"</a></th>" +
							"<td>"+type+"</td>" +
							"<td>"+dateCreation+"</td>" +
							"<td>"+resp.ownerNames+" ("+resp.userPermission.role+")</td>" +
						"</tr>";
				}
				else
				{
					clean_results = document.getElementById('data').innerHTML;
					clean_results = clean_results.replace('<tbody>', '');
					clean_results = clean_results.replace('</tbody>', '');
									
					document.getElementById('data').innerHTML = clean_results  +
						"<tr class=\"boxitem\">" +
							"<th>"+resp.title+"</th>" +
							"<td>"+type+"</td>" +
							"<td>"+dateCreation+"</td>" +
							"<td>"+resp.ownerNames+" ("+resp.userPermission.role+")</td>" +
						"</tr>";
				}

				var requestRev = gapi.client.drive.revisions.list({
					'fileId': fileId
				});
				
				jQuery(document).ready(function(){
					jQuery('#data').dataTable({
						"bPaginate": true,
						"bDestroy": true,	
    					"aaSorting": [[ 2, "desc" ]],
    					"bAutoWidth": false,
    					"sDom": 'Rlfrtip'
					});		
				});				
			});
		});	
	}

	function alertDir()
	{
		// Nothing to do
	}
	
	function revAlert()
	{
		// Nothing to do
	}

	/**
	 * Permanently delete a file, skipping the trash.
	 * @param {String} fileId ID of the file to delete.
	 */
	function deleteFile(fileId) 
	{
	  var request = gapi.client.drive.files.delete({
	    'fileId': fileId
	});

	jQuery("#"+fileId+"").remove();
		request.execute(function(resp) { });
	}
</script>

<script type="text/javascript" src="https://apis.google.com/js/client.js?onload=handleClientLoad"></script>

<!--Add a file picker for the user to start the upload process -->
<div style="margin-bottom: 10px;">
    <!--<input type="file" id="filePicker" style="display: none" />-->
	<input type="button" id="filesList" value="Refresh files list" onClick="jQuery('#data').fadeOut('fast');jQuery('#load').fadeIn('slow');retrieveAllFiles(alertons)" style="display: none" />
    <input type="button" id="authorizeButton" style="display: none" value="Authorize" />
	<!--<input type="button" id="directory" style="display: none" value="Creer un repertoire" onClick="insertDirectory(prompt('Nommer votre repertoire'),alertDir);"/>-->
</div>
	
<div id="load" style="display:none;">
	<img src="themes/img/loading.gif" alt="loading...">
</div>	
<table id="data" class="display dataTable" cellspacing="1" cellpadding="2" border="0" style="width: 100%; clear:both; display: none"></table>

<div style="clear:both;"></div>
