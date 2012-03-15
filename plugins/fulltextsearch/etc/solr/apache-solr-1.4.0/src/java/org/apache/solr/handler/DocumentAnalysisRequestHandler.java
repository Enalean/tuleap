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

import org.apache.commons.io.IOUtils;
import org.apache.lucene.analysis.Analyzer;
import org.apache.lucene.analysis.Token;
import org.apache.solr.client.solrj.request.DocumentAnalysisRequest;
import org.apache.solr.common.SolrException;
import org.apache.solr.common.SolrInputDocument;
import org.apache.solr.common.params.AnalysisParams;
import org.apache.solr.common.params.CommonParams;
import org.apache.solr.common.params.SolrParams;
import org.apache.solr.common.util.ContentStream;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.common.util.SimpleOrderedMap;
import org.apache.solr.request.SolrQueryRequest;
import org.apache.solr.schema.FieldType;
import org.apache.solr.schema.IndexSchema;
import org.apache.solr.schema.SchemaField;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import javax.xml.stream.XMLInputFactory;
import javax.xml.stream.XMLStreamConstants;
import javax.xml.stream.XMLStreamException;
import javax.xml.stream.XMLStreamReader;
import java.io.IOException;
import java.io.Reader;
import java.util.*;

/**
 * An analysis handler that provides a breakdown of the analysis process of provided docuemnts. This handler expects a
 * (single) content stream of the following format:
 * <p/>
 * <pre><code>
 *  &lt;docs&gt;
 *      &lt;doc&gt;
 *          &lt;field name="id"&gt;1&lt;/field&gt;
 *          &lt;field name="name"&gt;The Name&lt;/field&gt;
 *          &lt;field name="text"&gt;The Text Value&lt;/field&gt;
 *      &lt;doc&gt;
 *      &lt;doc&gt;...&lt;/doc&gt;
 *      &lt;doc&gt;...&lt;/doc&gt;
 *      ...
 *  &lt;/docs&gt;
 * </code></pre>
 * <p/>
 * <em><b>Note: Each document must contain a field which serves as the unique key. This key is used in the returned
 * response to assoicate an analysis breakdown to the analyzed document.</b></em>
 * <p/>
 * <p/>
 * <p/>
 * Like the {@link org.apache.solr.handler.FieldAnalysisRequestHandler}, this handler also supports query analysis by
 * sending either an "analysis.query" or "q" request paraemter that holds the query text to be analyzed. It also
 * supports the "analysis.showmatch" parameter which when set to {@code true}, all field tokens that match the query
 * tokens will be marked as a "match".
 *
 * @version $Id: DocumentAnalysisRequestHandler.java 824333 2009-10-12 13:40:27Z ehatcher $
 * @since solr 1.4
 */
public class DocumentAnalysisRequestHandler extends AnalysisRequestHandlerBase {

  public static final Logger log = LoggerFactory.getLogger(DocumentAnalysisRequestHandler.class);

  private static final float DEFAULT_BOOST = 1.0f;

  private XMLInputFactory inputFactory;

  @Override
  public void init(NamedList args) {
    super.init(args);

    inputFactory = XMLInputFactory.newInstance();
    try {
      // The java 1.6 bundled stax parser (sjsxp) does not currently have a thread-safe
      // XMLInputFactory, as that implementation tries to cache and reuse the
      // XMLStreamReader.  Setting the parser-specific "reuse-instance" property to false
      // prevents this.
      // All other known open-source stax parsers (and the bea ref impl)
      // have thread-safe factories.
      inputFactory.setProperty("reuse-instance", Boolean.FALSE);
    } catch (IllegalArgumentException ex) {
      // Other implementations will likely throw this exception since "reuse-instance"
      // isimplementation specific.
      log.debug("Unable to set the 'reuse-instance' property for the input factory: " + inputFactory);
    }
  }

  /**
   * {@inheritDoc}
   */
  protected NamedList doAnalysis(SolrQueryRequest req) throws Exception {
    DocumentAnalysisRequest analysisRequest = resolveAnalysisRequest(req);
    return handleAnalysisRequest(analysisRequest, req.getSchema());
  }

  @Override
  public String getDescription() {
    return "Provides a breakdown of the analysis process of provided documents";
  }

  @Override
  public String getVersion() {
    return "$Revision: 824333 $";
  }

  @Override
  public String getSourceId() {
    return "$Id: DocumentAnalysisRequestHandler.java 824333 2009-10-12 13:40:27Z ehatcher $";
  }

  @Override
  public String getSource() {
    return "$URL: https://svn.apache.org/repos/asf/lucene/solr/branches/branch-1.4/src/java/org/apache/solr/handler/DocumentAnalysisRequestHandler.java $";
  }


  //================================================ Helper Methods ==================================================

  /**
   * Resolves the {@link DocumentAnalysisRequest} from the given solr request.
   *
   * @param req The solr request.
   *
   * @return The resolved document analysis request.
   *
   * @throws IOException        Thrown when reading/parsing the content stream of the request fails.
   * @throws XMLStreamException Thrown when reading/parsing the content stream of the request fails.
   */
  DocumentAnalysisRequest resolveAnalysisRequest(SolrQueryRequest req) throws IOException, XMLStreamException {

    DocumentAnalysisRequest request = new DocumentAnalysisRequest();

    SolrParams params = req.getParams();

    String query = params.get(AnalysisParams.QUERY, params.get(CommonParams.Q, null));
    request.setQuery(query);

    boolean showMatch = params.getBool(AnalysisParams.SHOW_MATCH, false);
    request.setShowMatch(showMatch);

    ContentStream stream = extractSingleContentStream(req);
    Reader reader = stream.getReader();
    XMLStreamReader parser = inputFactory.createXMLStreamReader(reader);

    try {

      while (true) {
        int event = parser.next();
        switch (event) {
          case XMLStreamConstants.END_DOCUMENT: {
            parser.close();
            return request;
          }
          case XMLStreamConstants.START_ELEMENT: {
            String currTag = parser.getLocalName();
            if ("doc".equals(currTag)) {
              log.trace("Reading doc...");
              SolrInputDocument document = readDocument(parser, req.getSchema());
              request.addDocument(document);
            }
            break;
          }
        }
      }

    } finally {
      parser.close();
      IOUtils.closeQuietly(reader);
    }
  }

  /**
   * Handles the resolved {@link DocumentAnalysisRequest} and returns the analysis response as a named list.
   *
   * @param request The {@link DocumentAnalysisRequest} to be handled.
   * @param schema  The index schema.
   *
   * @return The analysis response as a named list.
   */
  NamedList<Object> handleAnalysisRequest(DocumentAnalysisRequest request, IndexSchema schema) {

    SchemaField uniqueKeyField = schema.getUniqueKeyField();
    NamedList<Object> result = new SimpleOrderedMap<Object>();

    for (SolrInputDocument document : request.getDocuments()) {

      NamedList<NamedList> theTokens = new SimpleOrderedMap<NamedList>();
      result.add(document.getFieldValue(uniqueKeyField.getName()).toString(), theTokens);
      for (String name : document.getFieldNames()) {

        // there's no point of providing analysis to unindexed fields.
        SchemaField field = schema.getField(name);
        if (!field.indexed()) {
          continue;
        }

        NamedList<Object> fieldTokens = new SimpleOrderedMap<Object>();
        theTokens.add(name, fieldTokens);

        FieldType fieldType = schema.getFieldType(name);

        Set<String> termsToMatch = new HashSet<String>();
        if (request.getQuery() != null && request.isShowMatch()) {
          try {
            List<Token> tokens = analyzeValue(request.getQuery(), fieldType.getQueryAnalyzer());
            for (Token token : tokens) {
              termsToMatch.add(token.term());
            }
          } catch (Exception e) {
            // ignore analysis exceptions since we are applying arbitrary text to all fields
          }
        }

        if (request.getQuery() != null) {
          try {
            AnalysisContext analysisContext = new AnalysisContext(fieldType, fieldType.getQueryAnalyzer(), Collections.EMPTY_SET);
            NamedList<List<NamedList>> tokens = analyzeValue(request.getQuery(), analysisContext);
            fieldTokens.add("query", tokens);
          } catch (Exception e) {
            // ignore analysis exceptions since we are applying arbitrary text to all fields
          }
        }

        Analyzer analyzer = fieldType.getAnalyzer();
        AnalysisContext analysisContext = new AnalysisContext(fieldType, analyzer, termsToMatch);
        Collection<Object> fieldValues = document.getFieldValues(name);
        NamedList<NamedList<List<NamedList>>> indexTokens = new SimpleOrderedMap<NamedList<List<NamedList>>>();
        for (Object fieldValue : fieldValues) {
          NamedList<List<NamedList>> tokens = analyzeValue(fieldValue.toString(), analysisContext);
          indexTokens.add(String.valueOf(fieldValue), tokens);
        }
        fieldTokens.add("index", indexTokens);
      }
    }

    return result;
  }

  /**
   * Reads the document from the given xml stream reader. The following document format is expected:
   * <p/>
   * <pre><code>
   * &lt;doc&gt;
   *    &lt;field name="id"&gt;1&lt;/field&gt;
   *    &lt;field name="name"&gt;The Name&lt;/field&gt;
   *    &lt;field name="text"&gt;The Text Value&lt;/field&gt;
   * &lt;/doc&gt;
   * </code></pre>
   * <p/>
   * <p/>
   * <em>NOTE: each read document is expected to have at least one field which serves as the unique key.</em>
   *
   * @param reader The {@link XMLStreamReader} from which the document will be read.
   * @param schema The index schema. The schema is used to validate that the read document has a unique key field.
   *
   * @return The read document.
   *
   * @throws XMLStreamException When reading of the document fails.
   */
  SolrInputDocument readDocument(XMLStreamReader reader, IndexSchema schema) throws XMLStreamException {
    SolrInputDocument doc = new SolrInputDocument();

    String uniqueKeyField = schema.getUniqueKeyField().getName();

    StringBuilder text = new StringBuilder();
    String fieldName = null;
    boolean hasId = false;

    while (true) {
      int event = reader.next();
      switch (event) {
        // Add everything to the text
        case XMLStreamConstants.SPACE:
        case XMLStreamConstants.CDATA:
        case XMLStreamConstants.CHARACTERS:
          text.append(reader.getText());
          break;

        case XMLStreamConstants.END_ELEMENT:
          if ("doc".equals(reader.getLocalName())) {
            if (!hasId) {
              throw new SolrException(SolrException.ErrorCode.BAD_REQUEST,
                      "All documents must contain a unique key value: '" + doc.toString() + "'");
            }
            return doc;
          } else if ("field".equals(reader.getLocalName())) {
            doc.addField(fieldName, text.toString(), DEFAULT_BOOST);
            if (uniqueKeyField.equals(fieldName)) {
              hasId = true;
            }
          }
          break;

        case XMLStreamConstants.START_ELEMENT:
          text.setLength(0);
          String localName = reader.getLocalName();
          if (!"field".equals(localName)) {
            log.warn("unexpected XML tag doc/" + localName);
            throw new SolrException(SolrException.ErrorCode.BAD_REQUEST, "unexpected XML tag doc/" + localName);
          }

          for (int i = 0; i < reader.getAttributeCount(); i++) {
            String attrName = reader.getAttributeLocalName(i);
            if ("name".equals(attrName)) {
              fieldName = reader.getAttributeValue(i);
            }
          }
          break;
      }
    }
  }

  /**
   * Extracts the only content stream from the request. {@link org.apache.solr.common.SolrException.ErrorCode#BAD_REQUEST}
   * error is thrown if the request doesn't hold any content stream or holds more than one.
   *
   * @param req The solr request.
   *
   * @return The single content stream which holds the documents to be analyzed.
   */
  private ContentStream extractSingleContentStream(SolrQueryRequest req) {
    Iterable<ContentStream> streams = req.getContentStreams();
    if (streams == null) {
      throw new SolrException(SolrException.ErrorCode.BAD_REQUEST,
              "DocumentAnlysisRequestHandler expects a single content stream with documents to analyze");
    }
    Iterator<ContentStream> iter = streams.iterator();
    if (!iter.hasNext()) {
      throw new SolrException(SolrException.ErrorCode.BAD_REQUEST,
              "DocumentAnlysisRequestHandler expects a single content stream with documents to analyze");
    }
    ContentStream stream = iter.next();
    if (iter.hasNext()) {
      throw new SolrException(SolrException.ErrorCode.BAD_REQUEST,
              "DocumentAnlysisRequestHandler expects a single content stream with documents to analyze");
    }
    return stream;
  }
}
