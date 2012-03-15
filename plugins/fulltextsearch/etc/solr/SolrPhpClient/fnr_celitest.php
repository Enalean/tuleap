<?php
  require_once('pre.php');

  echo "<html><body>Test Solr forum fields..<br> </body></html>"; 

require_once( '../SolrPhpClient/Apache/Solr/Service.php' );

$solr = new Apache_Solr_Service( 'localhost', '8983', '/solr' );

echo "<html><body>Test Solr forum fields..<br> </body></html>"; 


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
  $document->id = '0002_forum_php';
 
  $document->forum_name = 'Forums about forges';
  $document->forum_subject = 'Coclico: Solr and Forges';
  $document->forum_subject_address = 'https://forge.projet-coclico.org/forum/forum.php?thread_id=29&forum_id=18&group_id=9';
  $document->forum_body = 'just for test';
  $document->forum_author = 'Hratchia Pélibossian';
  $document->forum_date = '2007-01-22T23:01:02Z';


  echo "<html><body>----- Solr document values -----<br></body></html>"; 
  echo "<html><body>$document->id<br></body></html>"; 
  echo "<html><body>$document->forum_name <br> </body></html>"; 
  echo "<html><body>$document->forum_subject <br> </body></html>"; 
  echo "<html><body>$document->forum_subject_address<br></body></html>"; 
  echo "<html><body>$document->forum_body <br> </body></html>"; 
  echo "<html><body>$document->forum_author <br> </body></html>"; 
  echo "<html><body>$document->forum_date <br></body></html>"; 




  // can't test  seli update handler because we are in not celi class
  //$gus = $solr->_getUpdateServlet();
  //echo "<html><body>$solr->--- $gus ----<br></body></html>"; 

  
  $solr->addDocument($document);
  echo "<html><body>solr->addDocument <br></body></html>"; 

  $solr->commit(); //commits to see the deletes and the document
  echo "<html><body>solr->commit <br></body></html>"; 

  //$solr->optimize(); //merges multiple segments into one
  //echo "<html><body>solr->optimize <br></body></html>"; 

  echo "<html><body>END Forum add document<br> </body></html>"; 
  echo "<html><body>------------------------<br> </body></html>"; 
  echo "<html><body>------------------------<br> </body></html>"; 
  echo "<html><body>Test Solr News fields..<br> </body></html>"; 



  $document = new Apache_Solr_Document(); 
  $document->id = '0002_news_php';
 
  $document->news_name = 'News about forges';
  $document->news_subject = 'Coclico news: Solr and Forges';
  $document->news_subject_address = 'https://forge.projet-coclico.org/forum/forum.php?thread_id=29&forum_id=18&group_id=9';
  $document->news_body = 'just for test';
  $document->news_author = 'Hratchia Pélibossian';
  $document->news_date = '2007-01-22T23:01:02Z';


  echo "<html><body>----- Solr document values -----<br></body></html>"; 
  echo "<html><body>$document->id<br></body></html>"; 
  echo "<html><body>$document->news_name <br> </body></html>"; 
  echo "<html><body>$document->news_subject <br> </body></html>"; 
  echo "<html><body>$document->news_subject_address<br></body></html>"; 
  echo "<html><body>$document->news_body <br> </body></html>"; 
  echo "<html><body>$document->news_author <br> </body></html>"; 
  echo "<html><body>$document->news_date <br></body></html>"; 



  // can't test  seli update handler because we are in not celi class
  //$gus = $solr->_getUpdateServlet();
  //echo "<html><body>$solr->--- $gus ----<br></body></html>"; 

  

  $solr->addDocument($document);
  echo "<html><body>solr->addDocument <br></body></html>"; 

  $solr->commit(); //commits to see the deletes and the document
  echo "<html><body>solr->commit <br></body></html>"; 

  //$solr->optimize(); //merges multiple segments into one
 // echo "<html><body>solr->optimize <br></body></html>"; 

  echo "<html><body>END News add document<br> </body></html>"; 
  echo "<html><body>------------------------<br> </body></html>"; 
  echo "<html><body>------------------------<br> </body></html>"; 
  echo "<html><body>Test Solr realease fields..<br> </body></html>"; 


  $document = new Apache_Solr_Document(); 
  $document->id = '0002_release_php';
 
  $document->release_package_name = 'Release forges';
  $document->release_release_name = 'Coclico: Forges Release 1.1';
  $document->release_release_name_address = 'https://forge.projet-coclico.org/forum/forum.php?thread_id=29&forum_id=18&group_id=9';
  $document->release_owner = 'Xerox-celi';
  $document->release_date = '2007-01-22T23:01:02Z';


  echo "<html><body>----- Solr document values -----<br></body></html>"; 
  echo "<html><body>$document->id<br></body></html>"; 
  echo "<html><body>$document->release_package_name <br> </body></html>"; 
  echo "<html><body>$document->release_release_name <br> </body></html>"; 
  echo "<html><body>$document->release_release_name_address <br></body></html>"; 
  echo "<html><body>$document->release_owner <br> </body></html>"; 
  echo "<html><body>$document->release_date <br> </body></html>"; 


  // can't test  seli update handler because we are in not celi class
  //$gus = $solr->_getUpdateServlet();
  //echo "<html><body>$solr->--- $gus ----<br></body></html>"; 


  $solr->addDocument($document);
  echo "<html><body>solr->addDocument <br></body></html>"; 

  $solr->commit(); //commits to see the deletes and the document
  echo "<html><body>solr->commit <br></body></html>"; 

  $solr->optimize(); //merges multiple segments into one
  echo "<html><body>solr->optimize <br></body></html>"; 

  echo "<html><body>END release add document<br> </body></html>"; 
  echo "<html><body>------------------------<br> </body></html>"; 
  echo "<html><body>------------------------<br> </body></html>"; 


?>