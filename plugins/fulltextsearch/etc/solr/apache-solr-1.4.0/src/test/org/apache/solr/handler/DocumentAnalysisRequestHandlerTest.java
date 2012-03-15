/**
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements.  See the NOTICE file distributed with
 * this work for additional information regarding copyright ownership.
 * The ASF licenses this file to You under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with
 * the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

package org.apache.solr.handler;

import org.apache.solr.client.solrj.request.DocumentAnalysisRequest;
import org.apache.solr.common.SolrInputDocument;
import org.apache.solr.common.SolrInputField;
import org.apache.solr.common.params.ModifiableSolrParams;
import org.apache.solr.common.util.ContentStream;
import org.apache.solr.common.util.ContentStreamBase;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.request.SolrQueryRequest;
import org.apache.solr.request.SolrQueryRequestBase;

import java.util.ArrayList;
import java.util.List;

/**
 * A test for {@link DocumentAnalysisRequestHandler}.
 *
 * @version $Id: DocumentAnalysisRequestHandlerTest.java 801751 2009-08-06 18:09:56Z yonik $
 * @since solr 1.4
 */
public class DocumentAnalysisRequestHandlerTest extends AnalysisRequestHandlerTestBase {

  private DocumentAnalysisRequestHandler handler;

  @Override
  public String getSchemaFile() {
    return "schema.xml";
  }

  @Override
  public String getSolrConfigFile() {
    return "solrconfig.xml";
  }

  @Override
  public void setUp() throws Exception {
    super.setUp();
    handler = new DocumentAnalysisRequestHandler();
    handler.init(new NamedList());
  }

  /**
   * Tests the {@link DocumentAnalysisRequestHandler#resolveAnalysisRequest(org.apache.solr.request.SolrQueryRequest)}
   */
  public void testResolveAnalysisRequest() throws Exception {

    String docsInput =
            "<docs>" +
                    "<doc>" +
                    "<field name=\"id\">1</field>" +
                    "<field name=\"whitetok\">The Whitetok</field>" +
                    "<field name=\"text\">The Text</field>" +
                    "</doc>" +
                    "</docs>";

    final List<ContentStream> contentStreams = new ArrayList<ContentStream>(1);
    contentStreams.add(new ContentStreamBase.StringStream(docsInput));
    ModifiableSolrParams params = new ModifiableSolrParams();
    params.add("analysis.query", "The Query String");
    params.add("analysis.showmatch", "true");
    SolrQueryRequest req = new SolrQueryRequestBase(h.getCore(), params) {
      @Override
      public Iterable<ContentStream> getContentStreams() {
        return contentStreams;
      }
    };

    DocumentAnalysisRequest request = handler.resolveAnalysisRequest(req);

    assertNotNull(request);
    assertTrue(request.isShowMatch());
    assertNotNull(request.getQuery());
    assertEquals("The Query String", request.getQuery());
    List<SolrInputDocument> documents = request.getDocuments();
    assertNotNull(documents);
    assertEquals(1, documents.size());
    SolrInputDocument document = documents.get(0);
    SolrInputField field = document.getField("id");
    assertNotNull(field);
    assertEquals("1", field.getFirstValue());
    field = document.getField("whitetok");
    assertNotNull(field);
    assertEquals("The Whitetok", field.getFirstValue());
    field = document.getField("text");
    assertNotNull(field);
    assertEquals("The Text", field.getFirstValue());
  }

  /**
   * Tests the {@link DocumentAnalysisRequestHandler#handleAnalysisRequest(org.apache.solr.client.solrj.request.DocumentAnalysisRequest,
   * org.apache.solr.schema.IndexSchema)}
   */
  public void testHandleAnalysisRequest() throws Exception {

    SolrInputDocument document = new SolrInputDocument();
    document.addField("id", 1);
    document.addField("whitetok", "Jumping Jack");
    document.addField("text", "The Fox Jumped Over The Dogs");

    DocumentAnalysisRequest request = new DocumentAnalysisRequest()
            .setQuery("JUMPING")
            .setShowMatch(true)
            .addDocument(document);

    NamedList<Object> result = handler.handleAnalysisRequest(request, h.getCore().getSchema());
    assertNotNull("result is null and it shouldn't be", result);
    NamedList<NamedList<NamedList<Object>>> documentResult = (NamedList<NamedList<NamedList<Object>>>) result.get("1");
    assertNotNull("An analysis for document with key '1' should be returned", documentResult);

    // the id field
    NamedList<NamedList<Object>> idResult = documentResult.get("id");
    assertNotNull("an analysis for the 'id' field should be returned", idResult);

    NamedList<Object> queryResult;
    List<NamedList> tokenList;
    NamedList<Object> indexResult;
    NamedList<List<NamedList>> valueResult;

    /*** Much of this test seems invalid for a numeric "id" field
    NamedList<Object> queryResult = idResult.get("query");
    assertEquals("Only the default analyzer should be applied", 1, queryResult.size());
    String name = queryResult.getName(0);
    assertTrue("Only the default analyzer should be applied", name.matches("org.apache.solr.schema.FieldType\\$DefaultAnalyzer.*"));
    List<NamedList> tokenList = (List<NamedList>) queryResult.getVal(0);
    assertEquals("Query has only one token", 1, tokenList.size());
    assertToken(tokenList.get(0), new TokenInfo("JUMPING", null, "word", 0, 7, 1, null, false));
    NamedList<Object> indexResult = idResult.get("index");

    assertEquals("The id field has only a single value", 1, indexResult.size());
    NamedList<List<NamedList>> valueResult = (NamedList<List<NamedList>>) indexResult.get("1");
    assertEquals("Only the default analyzer should be applied", 1, valueResult.size());
    name = queryResult.getName(0);
    assertTrue("Only the default analyzer should be applied", name.matches("org.apache.solr.schema.FieldType\\$DefaultAnalyzer.*"));
    tokenList = valueResult.getVal(0);
    assertEquals("The 'id' field value has only one token", 1, tokenList.size());
    assertToken(tokenList.get(0), new TokenInfo("1", null, "word", 0, 1, 1, null, false));
    ***/
  
    // the name field
    NamedList<NamedList<Object>> whitetokResult = documentResult.get("whitetok");
    assertNotNull("an analysis for the 'whitetok' field should be returned", whitetokResult);
    queryResult = whitetokResult.get("query");
    tokenList = (List<NamedList>) queryResult.get("org.apache.lucene.analysis.WhitespaceTokenizer");
    assertNotNull("Expecting the 'WhitespaceTokenizer' to be applied on the query for the 'whitetok' field", tokenList);
    assertEquals("Query has only one token", 1, tokenList.size());
    assertToken(tokenList.get(0), new TokenInfo("JUMPING", null, "word", 0, 7, 1, null, false));
    indexResult = whitetokResult.get("index");
    assertEquals("The 'whitetok' field has only a single value", 1, indexResult.size());
    valueResult = (NamedList<List<NamedList>>) indexResult.get("Jumping Jack");
    tokenList = valueResult.getVal(0);
    assertEquals("Expecting 2 tokens to be present", 2, tokenList.size());
    assertToken(tokenList.get(0), new TokenInfo("Jumping", null, "word", 0, 7, 1, null, false));
    assertToken(tokenList.get(1), new TokenInfo("Jack", null, "word", 8, 12, 2, null, false));

    // the text field
    NamedList<NamedList<Object>> textResult = documentResult.get("text");
    assertNotNull("an analysis for the 'text' field should be returned", textResult);
    queryResult = textResult.get("query");
    tokenList = (List<NamedList>) queryResult.get("org.apache.lucene.analysis.standard.StandardTokenizer");
    assertNotNull("Expecting the 'StandardTokenizer' to be applied on the query for the 'text' field", tokenList);
    assertEquals("Query has only one token", 1, tokenList.size());
    assertToken(tokenList.get(0), new TokenInfo("JUMPING", null, "<ALPHANUM>", 0, 7, 1, null, false));
    tokenList = (List<NamedList>) queryResult.get("org.apache.lucene.analysis.standard.StandardFilter");
    assertNotNull("Expecting the 'StandardFilter' to be applied on the query for the 'text' field", tokenList);
    assertEquals("Query has only one token", 1, tokenList.size());
    assertToken(tokenList.get(0), new TokenInfo("JUMPING", null, "<ALPHANUM>", 0, 7, 1, null, false));
    tokenList = (List<NamedList>) queryResult.get("org.apache.lucene.analysis.LowerCaseFilter");
    assertNotNull("Expecting the 'LowerCaseFilter' to be applied on the query for the 'text' field", tokenList);
    assertEquals("Query has only one token", 1, tokenList.size());
    assertToken(tokenList.get(0), new TokenInfo("jumping", null, "<ALPHANUM>", 0, 7, 1, null, false));
    tokenList = (List<NamedList>) queryResult.get("org.apache.lucene.analysis.StopFilter");
    assertNotNull("Expecting the 'StopFilter' to be applied on the query for the 'text' field", tokenList);
    assertEquals("Query has only one token", 1, tokenList.size());
    assertToken(tokenList.get(0), new TokenInfo("jumping", null, "<ALPHANUM>", 0, 7, 1, null, false));
    tokenList = (List<NamedList>) queryResult.get("org.apache.solr.analysis.EnglishPorterFilter");
    assertNotNull("Expecting the 'EnglishPorterFilter' to be applied on the query for the 'text' field", tokenList);
    assertEquals("Query has only one token", 1, tokenList.size());
    assertToken(tokenList.get(0), new TokenInfo("jump", null, "<ALPHANUM>", 0, 7, 1, null, false));
    indexResult = textResult.get("index");
    assertEquals("The 'text' field has only a single value", 1, indexResult.size());
    valueResult = (NamedList<List<NamedList>>) indexResult.get("The Fox Jumped Over The Dogs");
    tokenList = valueResult.get("org.apache.lucene.analysis.standard.StandardTokenizer");
    assertNotNull("Expecting the 'StandardTokenizer' to be applied on the index for the 'text' field", tokenList);
    assertEquals("Expecting 6 tokens", 6, tokenList.size());
    assertToken(tokenList.get(0), new TokenInfo("The", null, "<ALPHANUM>", 0, 3, 1, null, false));
    assertToken(tokenList.get(1), new TokenInfo("Fox", null, "<ALPHANUM>", 4, 7, 2, null, false));
    assertToken(tokenList.get(2), new TokenInfo("Jumped", null, "<ALPHANUM>", 8, 14, 3, null, false));
    assertToken(tokenList.get(3), new TokenInfo("Over", null, "<ALPHANUM>", 15, 19, 4, null, false));
    assertToken(tokenList.get(4), new TokenInfo("The", null, "<ALPHANUM>", 20, 23, 5, null, false));
    assertToken(tokenList.get(5), new TokenInfo("Dogs", null, "<ALPHANUM>", 24, 28, 6, null, false));
    tokenList = valueResult.get("org.apache.lucene.analysis.standard.StandardFilter");
    assertNotNull("Expecting the 'StandardFilter' to be applied on the index for the 'text' field", tokenList);
    assertEquals("Expecting 6 tokens", 6, tokenList.size());
    assertToken(tokenList.get(0), new TokenInfo("The", null, "<ALPHANUM>", 0, 3, 1, null, false));
    assertToken(tokenList.get(1), new TokenInfo("Fox", null, "<ALPHANUM>", 4, 7, 2, null, false));
    assertToken(tokenList.get(2), new TokenInfo("Jumped", null, "<ALPHANUM>", 8, 14, 3, null, false));
    assertToken(tokenList.get(3), new TokenInfo("Over", null, "<ALPHANUM>", 15, 19, 4, null, false));
    assertToken(tokenList.get(4), new TokenInfo("The", null, "<ALPHANUM>", 20, 23, 5, null, false));
    assertToken(tokenList.get(5), new TokenInfo("Dogs", null, "<ALPHANUM>", 24, 28, 6, null, false));
    tokenList = valueResult.get("org.apache.lucene.analysis.LowerCaseFilter");
    assertNotNull("Expecting the 'LowerCaseFilter' to be applied on the index for the 'text' field", tokenList);
    assertEquals("Expecting 6 tokens", 6, tokenList.size());
    assertToken(tokenList.get(0), new TokenInfo("the", null, "<ALPHANUM>", 0, 3, 1, null, false));
    assertToken(tokenList.get(1), new TokenInfo("fox", null, "<ALPHANUM>", 4, 7, 2, null, false));
    assertToken(tokenList.get(2), new TokenInfo("jumped", null, "<ALPHANUM>", 8, 14, 3, null, false));
    assertToken(tokenList.get(3), new TokenInfo("over", null, "<ALPHANUM>", 15, 19, 4, null, false));
    assertToken(tokenList.get(4), new TokenInfo("the", null, "<ALPHANUM>", 20, 23, 5, null, false));
    assertToken(tokenList.get(5), new TokenInfo("dogs", null, "<ALPHANUM>", 24, 28, 6, null, false));
    tokenList = valueResult.get("org.apache.lucene.analysis.StopFilter");
    assertNotNull("Expecting the 'StopFilter' to be applied on the index for the 'text' field", tokenList);
    assertEquals("Expecting 4 tokens after stop word removal", 4, tokenList.size());
    assertToken(tokenList.get(0), new TokenInfo("fox", null, "<ALPHANUM>", 4, 7, 1, null, false));
    assertToken(tokenList.get(1), new TokenInfo("jumped", null, "<ALPHANUM>", 8, 14, 2, null, false));
    assertToken(tokenList.get(2), new TokenInfo("over", null, "<ALPHANUM>", 15, 19, 3, null, false));
    assertToken(tokenList.get(3), new TokenInfo("dogs", null, "<ALPHANUM>", 24, 28, 4, null, false));
    tokenList = valueResult.get("org.apache.solr.analysis.EnglishPorterFilter");
    assertNotNull("Expecting the 'EnglishPorterFilter' to be applied on the index for the 'text' field", tokenList);
    assertEquals("Expecting 4 tokens", 4, tokenList.size());
    assertToken(tokenList.get(0), new TokenInfo("fox", null, "<ALPHANUM>", 4, 7, 1, null, false));
    assertToken(tokenList.get(1), new TokenInfo("jump", null, "<ALPHANUM>", 8, 14, 2, null, true));
    assertToken(tokenList.get(2), new TokenInfo("over", null, "<ALPHANUM>", 15, 19, 3, null, false));
    assertToken(tokenList.get(3), new TokenInfo("dog", null, "<ALPHANUM>", 24, 28, 4, null, false));
  }
}
