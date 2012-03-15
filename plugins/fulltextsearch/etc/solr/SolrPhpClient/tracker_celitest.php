<?php
  require_once('pre.php');

  echo "<html><body>Test Solr..<br> </body></html>"; 
  require_once( '../SolrPhpClient/Apache/Solr/CeliService.php' );
  $solr = new Celi_Apache_Solr_Service( 'localhost', '8983', '/solr' );
  if ( ! $solr->ping() ) {
    echo "<html><body>Test do not work Solr with php client.<br></body></html>";    
  }
  if (  $solr->ping() ) {
    echo "<html><body>Test Work Solr with php client.<br> </body></html>";     
  }

  //
  //
  // Create a document
  //
  $document = new Apache_Solr_Document(); 
  $document->id = '0002_source_php';
 
  $document->tracker_title = 'Java source external field celi';
  $document->tracker_identifier= '2314';
  $document->tracker_type = 'Enhancement';
  $document->tracker_description = 'Java source external field celi';
  $document->tracker_subject = 'blocker';
  $document->tracker_creator = 'pelibossian';
  $document->tracker_modified = '2008-09-16T08:42:11.265Z';
  $document->tracker_name = 'tracker name';
  $document->tracker_created = '2008-09-16T08:42:11.265Z';
  $document->tracker_project= 'solr celi';
  $document->tracker_component= 'celi extractor';
  $document->tracker_status= 'InProgress';
  $document->tracker_owner= 'pelibossian';
  $document->tracker_priority= 'High';
  $document->tracker_severity= 'Blocker';
  $document->tracker_relatedChangeRequests= 'http://myserver/mycmapp/bugs/1235 http://remoteserver/mycmapp/defects/abc123';
  $document->tracker_changeSets = '34ef31af cs1';
  $document->tracker_comments= 'Java source external field celi';

  $document->tracker_attachments_address = '/usr/share/codendi/plugins/fulltextsearch/etc/solr/celi/src/org/apache/solr/schema/CeliExternalFileField.java';


  echo "<html><body>----- Solr document values -----<br></body></html>"; 
  echo "<html><body>$document->id<br></body></html>"; 
  echo "<html><body>$document->tracker_title<br> </body></html>"; 
  echo "<html><body>$document->tracker_identifier<br> </body></html>"; 
  echo "<html><body>$document->tracker_status <br> </body></html>"; 
  echo "<html><body>$document->tracker_created<br></body></html>"; 
  echo "<html><body>$document->tracker_owner <br> </body></html>"; 
  echo "<html><body>$document->tracker_severity <br></body></html>"; 
  echo "<html><body>$document->tracker_priority <br> </body></html>"; 
  echo "<html><body>$document->tracker_attachments_address<br> </body></html>"; 

  // use seli update handler
  $gus = $solr->_getUpdateServlet();
  echo "<html><body>$solr->--- $gus ----<br></body></html>"; 

  $solr->addDocument($document);
  echo "<html><body>2solr->addDocument <br></body></html>"; 

  $solr->commit(); //commits to see the deletes and the document
  echo "<html><body>solr->commit <br></body></html>"; 

  $solr->optimize(); //merges multiple segments into one
  echo "<html><body>solr->optimize <br></body></html>"; 

  echo "<html><body>END <br> </body></html>"; 



?>