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
package org.apache.solr.handler.extraction;

import org.apache.commons.io.IOUtils;
import org.apache.solr.common.SolrException;
import org.apache.solr.common.params.SolrParams;
import org.apache.solr.common.params.UpdateParams;
import org.apache.solr.common.util.ContentStream;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.request.SolrQueryRequest;
import org.apache.solr.request.SolrQueryResponse;
import org.apache.solr.schema.IndexSchema;
import org.apache.solr.update.AddUpdateCommand;
import org.apache.solr.update.processor.UpdateRequestProcessor;
import org.apache.solr.handler.ContentStreamLoader;
import org.apache.tika.config.TikaConfig;
import org.apache.tika.metadata.Metadata;
import org.apache.tika.parser.AutoDetectParser;
import org.apache.tika.parser.Parser;
import org.apache.tika.sax.XHTMLContentHandler;
import org.apache.tika.sax.xpath.Matcher;
import org.apache.tika.sax.xpath.MatchingContentHandler;
import org.apache.tika.sax.xpath.XPathParser;
import org.apache.tika.exception.TikaException;
import org.apache.xml.serialize.OutputFormat;
import org.apache.xml.serialize.BaseMarkupSerializer;
import org.apache.xml.serialize.XMLSerializer;
import org.apache.xml.serialize.TextSerializer;
import org.xml.sax.ContentHandler;
import org.xml.sax.SAXException;

import java.io.IOException;
import java.io.InputStream;
import java.io.StringWriter;


/**
 * The class responsible for loading extracted content into Solr.
 *
 **/
public class ExtractingDocumentLoader extends ContentStreamLoader {
  /**
   * Extract Only supported format
   */
  public static final String TEXT_FORMAT = "text";
  /**
   * Extract Only supported format.  Default
   */
  public static final String XML_FORMAT = "xml";
  /**
   * XHTML XPath parser.
   */
  private static final XPathParser PARSER =
          new XPathParser("xhtml", XHTMLContentHandler.XHTML);

  final IndexSchema schema;
  final SolrParams params;
  final UpdateRequestProcessor processor;
  protected AutoDetectParser autoDetectParser;

  private final AddUpdateCommand templateAdd;

  protected TikaConfig config;
  protected SolrContentHandlerFactory factory;
  //protected Collection<String> dateFormats = DateUtil.DEFAULT_DATE_FORMATS;

  public ExtractingDocumentLoader(SolrQueryRequest req, UpdateRequestProcessor processor,
                           TikaConfig config, SolrContentHandlerFactory factory) {
    this.params = req.getParams();
    schema = req.getSchema();
    this.config = config;
    this.processor = processor;

    templateAdd = new AddUpdateCommand();
    templateAdd.allowDups = false;
    templateAdd.overwriteCommitted = true;
    templateAdd.overwritePending = true;

    if (params.getBool(UpdateParams.OVERWRITE, true)) {
      templateAdd.allowDups = false;
      templateAdd.overwriteCommitted = true;
      templateAdd.overwritePending = true;
    } else {
      templateAdd.allowDups = true;
      templateAdd.overwriteCommitted = false;
      templateAdd.overwritePending = false;
    }
    //this is lightweight
    autoDetectParser = new AutoDetectParser(config);
    this.factory = factory;
  }


  /**
   * this must be MT safe... may be called concurrently from multiple threads.
   *
   * @param
   * @param
   */
  void doAdd(SolrContentHandler handler, AddUpdateCommand template)
          throws IOException {
    template.solrDoc = handler.newDocument();
    processor.processAdd(template);
  }

  void addDoc(SolrContentHandler handler) throws IOException {
    templateAdd.indexedId = null;
    doAdd(handler, templateAdd);
  }

  /**
   * @param req
   * @param stream
   * @throws java.io.IOException
   */
  public void load(SolrQueryRequest req, SolrQueryResponse rsp, ContentStream stream) throws IOException {
    errHeader = "ExtractingDocumentLoader: " + stream.getSourceInfo();
    Parser parser = null;
    String streamType = req.getParams().get(ExtractingParams.STREAM_TYPE, null);
    if (streamType != null) {
      //Cache?  Parsers are lightweight to construct and thread-safe, so I'm told
      parser = config.getParser(streamType.trim().toLowerCase());
    } else {
      parser = autoDetectParser;
    }
    if (parser != null) {
      Metadata metadata = new Metadata();
      metadata.add(ExtractingMetadataConstants.STREAM_NAME, stream.getName());
      metadata.add(ExtractingMetadataConstants.STREAM_SOURCE_INFO, stream.getSourceInfo());
      metadata.add(ExtractingMetadataConstants.STREAM_SIZE, String.valueOf(stream.getSize()));
      metadata.add(ExtractingMetadataConstants.STREAM_CONTENT_TYPE, stream.getContentType());

      // If you specify the resource name (the filename, roughly) with this parameter,
      // then Tika can make use of it in guessing the appropriate MIME type:
      String resourceName = req.getParams().get(ExtractingParams.RESOURCE_NAME, null);
      if (resourceName != null) {
        metadata.add(Metadata.RESOURCE_NAME_KEY, resourceName);
      }

      SolrContentHandler handler = factory.createSolrContentHandler(metadata, params, schema);
      InputStream inputStream = null;
      try {
        inputStream = stream.getStream();
        String xpathExpr = params.get(ExtractingParams.XPATH_EXPRESSION);
        boolean extractOnly = params.getBool(ExtractingParams.EXTRACT_ONLY, false);
        ContentHandler parsingHandler = handler;

        StringWriter writer = null;
        BaseMarkupSerializer serializer = null;
        if (extractOnly == true) {
          String extractFormat = params.get(ExtractingParams.EXTRACT_FORMAT, "xml");
          writer = new StringWriter();
          if (extractFormat.equals(TEXT_FORMAT)) {
            serializer = new TextSerializer();
            serializer.setOutputCharStream(writer);
            serializer.setOutputFormat(new OutputFormat("Text", "UTF-8", true));
          } else {
            serializer = new XMLSerializer(writer, new OutputFormat("XML", "UTF-8", true));
          }
          if (xpathExpr != null) {
            Matcher matcher =
                    PARSER.parse(xpathExpr);
            serializer.startDocument();//The MatchingContentHandler does not invoke startDocument.  See http://tika.markmail.org/message/kknu3hw7argwiqin
            parsingHandler = new MatchingContentHandler(serializer, matcher);
          } else {
            parsingHandler = serializer;
          }
        } else if (xpathExpr != null) {
          Matcher matcher =
                  PARSER.parse(xpathExpr);
          parsingHandler = new MatchingContentHandler(handler, matcher);
        } //else leave it as is

        //potentially use a wrapper handler for parsing, but we still need the SolrContentHandler for getting the document.
        parser.parse(inputStream, parsingHandler, metadata);
        if (extractOnly == false) {
          addDoc(handler);
        } else {
          //serializer is not null, so we need to call endDoc on it if using xpath
          if (xpathExpr != null){
            serializer.endDocument();
          }
          rsp.add(stream.getName(), writer.toString());
          writer.close();
          String[] names = metadata.names();
          NamedList metadataNL = new NamedList();
          for (int i = 0; i < names.length; i++) {
            String[] vals = metadata.getValues(names[i]);
            metadataNL.add(names[i], vals);
          }
          rsp.add(stream.getName() + "_metadata", metadataNL);
        }
      } catch (SAXException e) {
        throw new SolrException(SolrException.ErrorCode.SERVER_ERROR, e);
      } catch (TikaException e) {
        throw new SolrException(SolrException.ErrorCode.SERVER_ERROR, e);
      } finally {
        IOUtils.closeQuietly(inputStream);
      }
    } else {
      throw new SolrException(SolrException.ErrorCode.BAD_REQUEST, "Stream type of " + streamType + " didn't match any known parsers.  Please supply the " + ExtractingParams.STREAM_TYPE + " parameter.");
    }
  }


}
