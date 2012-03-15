<?php
  require_once('pre.php');

  echo "<html><body>Test Solr.</body></html>"; 

require_once( '../SolrPhpClient/Apache/Solr/CeliService.php' );

$solr = new Celi_Apache_Solr_Service( 'localhost', '8983', '/solr' );

  if ( ! $solr->ping() ) {
    echo "<html><body>Test dont work Solr.<br></body></html>"; 
   
  }



  if (  $solr->ping() ) {
    echo "<html><body>Test Work Solr.<br> </body></html>"; 
    
  }

  //
  //
  // Create a document
  //
  $document = new Apache_Solr_Document(); 
  $document->id = '0002_php';
  $document->comments= 'Test with PHP';
  $document->file_address = '/home/solr/apache-solr-1.4.0/example/exampledocs/Doc-Word.doc';
  echo "<html><body>$document->id<br></body></html>"; 
  echo "<html><body>$document->comments<br> </body></html>"; 
  echo "<html><body>$document->file_address<br> </body></html>"; 

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

 echo "<html><body>END\n END.</body></html>"; 



?>