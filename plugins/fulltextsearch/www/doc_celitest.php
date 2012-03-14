<?php

require_once('pre.php');

echo '<html><body>';
echo '<h1>Test Solr...</h1>';

require_once( '../etc/solr/SolrPhpClient/Apache/Solr/CeliService.php' );

$solr = new Celi_Apache_Solr_Service( 'localhost', '8983', '/solr' );

if ( ! $solr->ping() ) {
    echo "<p>Test do not work Solr with php client.</p>"; 
} else {
    echo "<p>Test Work Solr with php client.</p>";
}


  //
  //
  // Create a document
  //
  $document = new Apache_Solr_Document(); 
  $document->id = '0002_doc_php';
  $document->comments= 'Test with PHP';
  $document->file_address = '/usr/share/codendi/plugins/fulltextsearch/www/solr_test.txt';
 
  $document->doc_title = 'Document Word to add Solr Index';
  $document->doc_description = 'Document Word to add Solr Index';
  $document->doc_owner = '4-101';
  $document->doc_create_date = '2005-12-31T23:59:59Z';
  $document->doc_update_date = '2007-01-22T23:01:02Z';
  $document->doc_language = 'english';
  $document->doc_extra_field = 'personale note: this test is simple';

  echo "<p>----- Solr document values -----</p>"; 
  echo "<p>" . $document->id . "</p>"; 
  echo "<p>" . $document->comments . "</p>"; 
  echo "<p>" . $document->file_address . "</p>"; 
  echo "<p>" . $document->doc_title . "</p>"; 
  echo "<p>" . $document->doc_description . "</p>"; 
  echo "<p>" . $document->doc_owner . "</p>"; 
  echo "<p>" . $document->doc_create_date . "</p>"; 
  echo "<p>" . $document->doc_update_date . "</p>"; 
  echo "<p>" . $document->doc_language . "</p>"; 
  echo "<p>" . $document->doc_extra_field . "</p>"; 


  // use seli update handler
  $gus = $solr->_getUpdateServlet();
  var_dump("<pre>", $solr, "</pre>");
  echo "<p>--- $gus ----</p>"; 

  $gus ='update/celi';
  
  var_dump("<pre>", $solr, "</pre>");
  echo "<p>--- before set this value : $gus ----</p>"; 
  $solr->_setUpdateServlet($gus);

  var_dump("<pre>", $solr, "</pre>");
  $gus = $solr->_getUpdateServlet();
  echo "<p>--- after set this value : $gus ----</p>"; 

  try {
      echo "<p>solr->addDocument</p>";
      if ($solr->addDocument($document)) {
          echo '<p>Document added</p>';
      } else {
          echo '<p>Document was NOT added</p>';
      }
  } catch (Exception $e) {
      echo '<p><strong>' . $e->getMessage() . '</strong></p>';
      var_dump('<pre>', $e,'</pre>');
  }
   

  $solr->commit(); //commits to see the deletes and the document
  echo "<p>solr->commit</p>"; 

  $solr->optimize(); //merges multiple segments into one
  echo "<p>solr->optimize </p>"; 

  echo 'END';
  echo '</body></html>'; 

?>