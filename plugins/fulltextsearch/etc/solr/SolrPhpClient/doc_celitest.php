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
  $document->id = '0002_doc_php';
  $document->comments= 'Test with PHP';
  $document->file_address = '/home/solr/apache-solr-1.4.0/example/exampledocs/Doc-Word.doc';
 
  $document->doc_title = 'Document Word to add Solr Index';
  $document->doc_description = 'Document Word to add Solr Index';
  $document->doc_owner = '4-101';
  $document->doc_create_date = '2005-12-31T23:59:59Z';
  $document->doc_update_date = '2007-01-22T23:01:02Z';
  $document->doc_language = 'english';
  $document->doc_extra_field = 'personale note: this test is simple';

  echo "<html><body>----- Solr document values -----<br></body></html>"; 
  echo "<html><body>$document->id<br></body></html>"; 
  echo "<html><body>$document->comments<br> </body></html>"; 
  echo "<html><body>$document->file_address<br> </body></html>"; 
  echo "<html><body>$document->doc_title <br></body></html>"; 
  echo "<html><body>$document->doc_description <br> </body></html>"; 
  echo "<html><body>$document->doc_owner <br> </body></html>"; 
  echo "<html><body>$document->doc_create_date <br></body></html>"; 
  echo "<html><body>$document->doc_update_date <br> </body></html>"; 
  echo "<html><body>$document->doc_language <br> </body></html>"; 
  echo "<html><body>$document->doc_extra_field <br> </body></html>"; 



  // use seli update handler
  $gus = $solr->_getUpdateServlet();
  echo "<html><body>$solr->--- $gus ----<br></body></html>"; 

  $gus ='update/celi';
  echo "<html><body>$solr->--- befor set this value : $gus ----<br></body></html>"; 
  $solr->_setUpdateServlet($gus);

  $gus = $solr->_getUpdateServlet();
  echo "<html><body>$solr->--- after set this value : $gus ----<br></body></html>"; 


  $solr->addDocument($document);
  echo "<html><body>2solr->addDocument <br></body></html>"; 

  $solr->commit(); //commits to see the deletes and the document
  echo "<html><body>solr->commit <br></body></html>"; 

  $solr->optimize(); //merges multiple segments into one
  echo "<html><body>solr->optimize <br></body></html>"; 

  echo "<html><body>END <br> </body></html>"; 



?>