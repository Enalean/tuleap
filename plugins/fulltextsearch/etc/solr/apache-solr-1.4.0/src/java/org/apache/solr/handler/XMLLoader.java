package org.apache.solr.handler;
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

import org.apache.solr.update.processor.UpdateRequestProcessor;
import org.apache.solr.update.AddUpdateCommand;
import org.apache.solr.update.CommitUpdateCommand;
import org.apache.solr.update.RollbackUpdateCommand;
import org.apache.solr.update.DeleteUpdateCommand;
import org.apache.solr.request.SolrQueryRequest;
import org.apache.solr.request.SolrQueryResponse;
import org.apache.solr.common.util.ContentStream;
import org.apache.solr.common.util.StrUtils;
import org.apache.solr.common.SolrException;
import org.apache.solr.common.SolrInputDocument;
import org.apache.solr.common.params.UpdateParams;
import org.apache.commons.io.IOUtils;

import javax.xml.stream.XMLStreamReader;
import javax.xml.stream.XMLStreamException;
import javax.xml.stream.FactoryConfigurationError;
import javax.xml.stream.XMLStreamConstants;
import javax.xml.stream.XMLInputFactory;
import javax.xml.transform.TransformerConfigurationException;
import java.io.Reader;
import java.io.StringReader;
import java.io.IOException;


/**
 *
 *
 **/
class XMLLoader extends ContentStreamLoader {
  protected UpdateRequestProcessor processor;
  private XMLInputFactory inputFactory;

  public XMLLoader(UpdateRequestProcessor processor, XMLInputFactory inputFactory) {
    this.processor = processor;
    this.inputFactory = inputFactory;
  }

  public void load(SolrQueryRequest req, SolrQueryResponse rsp, ContentStream stream) throws Exception {
    errHeader = "XMLLoader: " + stream.getSourceInfo();
    Reader reader = null;
    try {
      reader = stream.getReader();
      if (XmlUpdateRequestHandler.log.isTraceEnabled()) {
        String body = IOUtils.toString(reader);
        XmlUpdateRequestHandler.log.trace("body", body);
        reader = new StringReader(body);
      }

      XMLStreamReader parser = inputFactory.createXMLStreamReader(reader);
      this.processUpdate(processor, parser);
    }
    catch (XMLStreamException e) {
      throw new SolrException(SolrException.ErrorCode.BAD_REQUEST, e.getMessage(), e);
    } finally {
      IOUtils.closeQuietly(reader);
    }
  }




  /**
   * @since solr 1.2
   */
  void processUpdate(UpdateRequestProcessor processor, XMLStreamReader parser)
          throws XMLStreamException, IOException, FactoryConfigurationError,
          InstantiationException, IllegalAccessException,
          TransformerConfigurationException {
    AddUpdateCommand addCmd = null;
    while (true) {
      int event = parser.next();
      switch (event) {
        case XMLStreamConstants.END_DOCUMENT:
          parser.close();
          return;

        case XMLStreamConstants.START_ELEMENT:
          String currTag = parser.getLocalName();
          if (currTag.equals(XmlUpdateRequestHandler.ADD)) {
            XmlUpdateRequestHandler.log.trace("SolrCore.update(add)");

            addCmd = new AddUpdateCommand();
            boolean overwrite = true;  // the default

            Boolean overwritePending = null;
            Boolean overwriteCommitted = null;
            for (int i = 0; i < parser.getAttributeCount(); i++) {
              String attrName = parser.getAttributeLocalName(i);
              String attrVal = parser.getAttributeValue(i);
              if (XmlUpdateRequestHandler.OVERWRITE.equals(attrName)) {
                overwrite = StrUtils.parseBoolean(attrVal);
              } else if (XmlUpdateRequestHandler.ALLOW_DUPS.equals(attrName)) {
                overwrite = !StrUtils.parseBoolean(attrVal);
              } else if (XmlUpdateRequestHandler.COMMIT_WITHIN.equals(attrName)) {
                addCmd.commitWithin = Integer.parseInt(attrVal);
              } else if (XmlUpdateRequestHandler.OVERWRITE_PENDING.equals(attrName)) {
                overwritePending = StrUtils.parseBoolean(attrVal);
              } else if (XmlUpdateRequestHandler.OVERWRITE_COMMITTED.equals(attrName)) {
                overwriteCommitted = StrUtils.parseBoolean(attrVal);
              } else {
                XmlUpdateRequestHandler.log.warn("Unknown attribute id in add:" + attrName);
              }
            }

            // check if these flags are set
            if (overwritePending != null && overwriteCommitted != null) {
              if (overwritePending != overwriteCommitted) {
                throw new SolrException(SolrException.ErrorCode.BAD_REQUEST,
                        "can't have different values for 'overwritePending' and 'overwriteCommitted'");
              }
              overwrite = overwritePending;
            }
            addCmd.overwriteCommitted = overwrite;
            addCmd.overwritePending = overwrite;
            addCmd.allowDups = !overwrite;
          } else if ("doc".equals(currTag)) {
            XmlUpdateRequestHandler.log.trace("adding doc...");
            addCmd.clear();
            addCmd.solrDoc = readDoc(parser);
            processor.processAdd(addCmd);
          } else if (XmlUpdateRequestHandler.COMMIT.equals(currTag) || XmlUpdateRequestHandler.OPTIMIZE.equals(currTag)) {
            XmlUpdateRequestHandler.log.trace("parsing " + currTag);

            CommitUpdateCommand cmd = new CommitUpdateCommand(XmlUpdateRequestHandler.OPTIMIZE.equals(currTag));

            boolean sawWaitSearcher = false, sawWaitFlush = false;
            for (int i = 0; i < parser.getAttributeCount(); i++) {
              String attrName = parser.getAttributeLocalName(i);
              String attrVal = parser.getAttributeValue(i);
              if (XmlUpdateRequestHandler.WAIT_FLUSH.equals(attrName)) {
                cmd.waitFlush = StrUtils.parseBoolean(attrVal);
                sawWaitFlush = true;
              } else if (XmlUpdateRequestHandler.WAIT_SEARCHER.equals(attrName)) {
                cmd.waitSearcher = StrUtils.parseBoolean(attrVal);
                sawWaitSearcher = true;
              } else if (UpdateParams.MAX_OPTIMIZE_SEGMENTS.equals(attrName)) {
                cmd.maxOptimizeSegments = Integer.parseInt(attrVal);
              } else if (UpdateParams.EXPUNGE_DELETES.equals(attrName)) {
                cmd.expungeDeletes = StrUtils.parseBoolean(attrVal);
              } else {
                XmlUpdateRequestHandler.log.warn("unexpected attribute commit/@" + attrName);
              }
            }

            // If waitFlush is specified and waitSearcher wasn't, then
            // clear waitSearcher.
            if (sawWaitFlush && !sawWaitSearcher) {
              cmd.waitSearcher = false;
            }
            processor.processCommit(cmd);
          } // end commit
          else if (XmlUpdateRequestHandler.ROLLBACK.equals(currTag)) {
            XmlUpdateRequestHandler.log.trace("parsing " + currTag);

            RollbackUpdateCommand cmd = new RollbackUpdateCommand();

            processor.processRollback(cmd);
          } // end rollback
          else if (XmlUpdateRequestHandler.DELETE.equals(currTag)) {
            XmlUpdateRequestHandler.log.trace("parsing delete");
            processDelete(processor, parser);
          } // end delete
          break;
      }
    }
  }

  /**
   * @since solr 1.3
   */
  void processDelete(UpdateRequestProcessor processor, XMLStreamReader parser) throws XMLStreamException, IOException {
    // Parse the command
    DeleteUpdateCommand deleteCmd = new DeleteUpdateCommand();
    deleteCmd.fromPending = true;
    deleteCmd.fromCommitted = true;
    for (int i = 0; i < parser.getAttributeCount(); i++) {
      String attrName = parser.getAttributeLocalName(i);
      String attrVal = parser.getAttributeValue(i);
      if ("fromPending".equals(attrName)) {
        deleteCmd.fromPending = StrUtils.parseBoolean(attrVal);
      } else if ("fromCommitted".equals(attrName)) {
        deleteCmd.fromCommitted = StrUtils.parseBoolean(attrVal);
      } else {
        XmlUpdateRequestHandler.log.warn("unexpected attribute delete/@" + attrName);
      }
    }

    StringBuilder text = new StringBuilder();
    while (true) {
      int event = parser.next();
      switch (event) {
        case XMLStreamConstants.START_ELEMENT:
          String mode = parser.getLocalName();
          if (!("id".equals(mode) || "query".equals(mode))) {
            XmlUpdateRequestHandler.log.warn("unexpected XML tag /delete/" + mode);
            throw new SolrException(SolrException.ErrorCode.BAD_REQUEST,
                    "unexpected XML tag /delete/" + mode);
          }
          text.setLength(0);
          break;

        case XMLStreamConstants.END_ELEMENT:
          String currTag = parser.getLocalName();
          if ("id".equals(currTag)) {
            deleteCmd.id = text.toString();
          } else if ("query".equals(currTag)) {
            deleteCmd.query = text.toString();
          } else if ("delete".equals(currTag)) {
            return;
          } else {
            XmlUpdateRequestHandler.log.warn("unexpected XML tag /delete/" + currTag);
            throw new SolrException(SolrException.ErrorCode.BAD_REQUEST,
                    "unexpected XML tag /delete/" + currTag);
          }
          processor.processDelete(deleteCmd);
          deleteCmd.id = null;
          deleteCmd.query = null;
          break;

          // Add everything to the text
        case XMLStreamConstants.SPACE:
        case XMLStreamConstants.CDATA:
        case XMLStreamConstants.CHARACTERS:
          text.append(parser.getText());
          break;
      }
    }
  }


  /**
   * Given the input stream, read a document
   *
   * @since solr 1.3
   */
  SolrInputDocument readDoc(XMLStreamReader parser) throws XMLStreamException {
    SolrInputDocument doc = new SolrInputDocument();

    String attrName = "";
    for (int i = 0; i < parser.getAttributeCount(); i++) {
      attrName = parser.getAttributeLocalName(i);
      if ("boost".equals(attrName)) {
        doc.setDocumentBoost(Float.parseFloat(parser.getAttributeValue(i)));
      } else {
        XmlUpdateRequestHandler.log.warn("Unknown attribute doc/@" + attrName);
      }
    }

    StringBuilder text = new StringBuilder();
    String name = null;
    float boost = 1.0f;
    boolean isNull = false;
    while (true) {
      int event = parser.next();
      switch (event) {
        // Add everything to the text
        case XMLStreamConstants.SPACE:
        case XMLStreamConstants.CDATA:
        case XMLStreamConstants.CHARACTERS:
          text.append(parser.getText());
          break;

        case XMLStreamConstants.END_ELEMENT:
          if ("doc".equals(parser.getLocalName())) {
            return doc;
          } else if ("field".equals(parser.getLocalName())) {
            if (!isNull) {
              doc.addField(name, text.toString(), boost);
              boost = 1.0f;
            }
          }
          break;

        case XMLStreamConstants.START_ELEMENT:
          text.setLength(0);
          String localName = parser.getLocalName();
          if (!"field".equals(localName)) {
            XmlUpdateRequestHandler.log.warn("unexpected XML tag doc/" + localName);
            throw new SolrException(SolrException.ErrorCode.BAD_REQUEST,
                    "unexpected XML tag doc/" + localName);
          }
          boost = 1.0f;
          String attrVal = "";
          for (int i = 0; i < parser.getAttributeCount(); i++) {
            attrName = parser.getAttributeLocalName(i);
            attrVal = parser.getAttributeValue(i);
            if ("name".equals(attrName)) {
              name = attrVal;
            } else if ("boost".equals(attrName)) {
              boost = Float.parseFloat(attrVal);
            } else if ("null".equals(attrName)) {
              isNull = StrUtils.parseBoolean(attrVal);
            } else {
              XmlUpdateRequestHandler.log.warn("Unknown attribute doc/field/@" + attrName);
            }
          }
          break;
      }
    }
  }

}
