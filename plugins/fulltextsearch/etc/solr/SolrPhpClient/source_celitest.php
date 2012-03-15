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
  $document->source_file_name_address = '/usr/share/codendi/plugins/fulltextsearch/etc/solr/celi/src/org/apache/solr/schema/CeliExternalFileField.java';
 
  $document->source_file_name = 'Java source external field celi';
  $document->source_description = 'Java source external field celi';
  $document->source_owner = '4-101';
  $document->source_create_date = '2005-12-31T23:59:59Z';
  $document->source_update_date = '2007-01-22T23:01:02Z';
  $document->source_last_commiter = 'pelibossian';

  echo "<html><body>----- Solr document values -----<br></body></html>"; 
  echo "<html><body>$document->id<br></body></html>"; 
  echo "<html><body>$document->source_file_name<br> </body></html>"; 
  echo "<html><body>$document->source_file_name_address <br> </body></html>"; 
  echo "<html><body>$document->source_description<br></body></html>"; 
  echo "<html><body>$document->source_owner <br> </body></html>"; 
  echo "<html><body>$document->source_create_date <br></body></html>"; 
  echo "<html><body>$document->source_update_date <br> </body></html>"; 
  echo "<html><body>$document->source_last_commiter <br> </body></html>"; 

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