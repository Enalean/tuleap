package org.apache.solr.handler.extraction;

import org.apache.solr.handler.ContentStreamLoader;
import org.apache.solr.handler.XmlUpdateRequestHandler;
import org.apache.solr.update.processor.UpdateRequestProcessor;
import org.apache.solr.update.AddUpdateCommand;
import org.apache.solr.update.CommitUpdateCommand;
import org.apache.solr.update.RollbackUpdateCommand;
import org.apache.solr.update.DeleteUpdateCommand;
import org.apache.solr.request.LocalSolrQueryRequest;
import org.apache.solr.request.SolrQueryRequest;
import org.apache.solr.request.SolrQueryResponse;
import org.apache.solr.common.util.ContentStream;
import org.apache.solr.common.util.ContentStreamBase;
import org.apache.solr.common.util.StrUtils;
import org.apache.solr.common.SolrException;
import org.apache.solr.common.SolrInputDocument;
import org.apache.solr.common.SolrInputField;
import org.apache.solr.common.params.SolrParams;
import org.apache.solr.common.params.UpdateParams;
import org.apache.solr.core.*;
import org.apache.solr.schema.*;
import org.apache.solr.common.util.NamedList;
import org.apache.commons.io.IOUtils;

import javax.xml.stream.XMLStreamReader;
import javax.xml.stream.XMLStreamException;
import javax.xml.stream.FactoryConfigurationError;
import javax.xml.stream.XMLStreamConstants;
import javax.xml.stream.XMLInputFactory;
import javax.xml.transform.TransformerConfigurationException;


import java.io.File;
import java.io.Reader;
import java.io.StringReader;
import java.io.IOException;
import java.util.ArrayList;
import java.util.Collection;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Map;

/**
 * The class responsible for loading metadata (Xml Format) and extracted content into Solr.
 *
 **/
public class CeliExtractingDocumentLoader extends ContentStreamLoader {
	  protected UpdateRequestProcessor processor;
	  private XMLInputFactory inputFactory;
	  public CeliExtractingDocumentLoader(UpdateRequestProcessor processor, XMLInputFactory inputFactory) {
	    this.processor = processor;
	    this.inputFactory = inputFactory;
	  }

	  public void load(SolrQueryRequest req, SolrQueryResponse rsp, ContentStream stream) throws Exception {
	    errHeader = "CeliExtractingDocumentLoader: " + stream.getSourceInfo();
	    SolrCore core = req.getCore();
	    Reader reader = null;

	    try {
	      reader = stream.getReader();
	      if (CeliUpdateRequestHandler.log.isTraceEnabled()) {
	        String body = IOUtils.toString(reader);
	        CeliUpdateRequestHandler.log.trace("body", body);
	        reader = new StringReader(body);
	      }
	      
	      req.getParams().toString();
	      XMLStreamReader parser = inputFactory.createXMLStreamReader(reader);
	      this.processUpdate(processor, parser, core);
	      
	    }
	    catch (XMLStreamException e) {
	      throw new SolrException(SolrException.ErrorCode.BAD_REQUEST, e.getMessage(), e);
	    } finally {
	      IOUtils.closeQuietly(reader);
	    }
	  }
	  //extract only text from ritch document
	  private String getExtractioOnly(SolrCore core, String keyfieldvalue)throws Exception {
		  NamedList namedlisteextractonly = new NamedList();
		  namedlisteextractonly.add(ExtractingParams.EXTRACT_ONLY, "true");
		  namedlisteextractonly.add(ExtractingParams.EXTRACT_FORMAT, ExtractingDocumentLoader.TEXT_FORMAT);
		  LocalSolrQueryRequest reql = new LocalSolrQueryRequest(core, namedlisteextractonly);
		  List<ContentStream> cs = new ArrayList<ContentStream>();
	      cs.add(new ContentStreamBase.FileStream(new File(keyfieldvalue)));
	      reql.setContentStreams(cs);
		  SolrQueryResponse rspl = queryAndResponse("/update/extract", reql);
		  NamedList list = rspl.getValues();
		  
		  int lastindex = keyfieldvalue.lastIndexOf('/');
		  String filename = keyfieldvalue.substring(lastindex+1);
		  String extraction = (String) list.get(filename);
		  NamedList nliste = new NamedList();
		  nliste.remove(ExtractingParams.EXTRACT_ONLY);
		  SolrParams.toSolrParams(nliste);
		  reql.setParams(SolrParams.toSolrParams(nliste));
		  
		  return extraction;
	  }
	  

	  
	  private HashMap<String, String> getCeliFieldAndKeyFieldName(SolrCore score){
			java.util.Map<String,SchemaField> mfields = score.getSchema().getFields();
			HashMap<String, String> retmap = new HashMap();
			String keyFieldName = null;
			String celiField = null;
			Object[] keyset= mfields.keySet().toArray();
			for(int i = 0; mfields != null && i < mfields.size(); i++){
				SchemaField sf = mfields.get(keyset[i].toString());
				String typename = sf.getType().getTypeName();
				Object otypefield = sf.getType();

				if(otypefield instanceof org.apache.solr.schema.CeliExternalFileField ){
					celiField = sf.getName();
					org.apache.solr.schema.CeliExternalFileField ceff = (org.apache.solr.schema.CeliExternalFileField)otypefield;
					String keyfield = ceff.getKeyField();
					keyFieldName = mfields.get(keyfield).getName();
					retmap.put(celiField, keyFieldName);
				}
			}
			return retmap;
		  }	  
	  /**
	   * Put the Xml metada, ritch document content in Solr Input Document
	   *
	   */
	  SolrInputDocument readDoc(XMLStreamReader parser, SolrCore core) throws  Exception {
		  SolrInputDocument solrinputdoc = readDoc(parser);
	      HashMap<String,String> hcelifields = getCeliFieldAndKeyFieldName(core);
	      int size = hcelifields.size();
	      if(hcelifields.isEmpty()){
	    	  return solrinputdoc;
	      }
	      java.util.Set<String> skey = hcelifields.keySet();
	      Iterator<String>  iterator = skey.iterator();
		  while(iterator.hasNext()){
			  String celiField = iterator.next();
			  String keyfield = hcelifields.get(celiField);
			  SolrInputField sif = solrinputdoc.getField(keyfield);
			  if(sif != null){
				  String keyfieldvalue = solrinputdoc.getField(keyfield).getValue().toString();
				  String extraction = getExtractioOnly(core, keyfieldvalue);
				  solrinputdoc.addField(celiField, extraction, 1);
			  }
		  }	      
		  return solrinputdoc;
	  }	  	  
	  /**
	   * Given the Xml input stream /metada, read a document
	   *
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

	  
	  /**
	   * @since solr 1.2
	   */
	  void processUpdate(UpdateRequestProcessor processor, XMLStreamReader parser, SolrCore core)
	          throws XMLStreamException, IOException, FactoryConfigurationError,
	          InstantiationException, IllegalAccessException, Exception,
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
	            addCmd.solrDoc = readDoc(parser, core);
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
	  private SolrQueryResponse queryAndResponse(String handler, SolrQueryRequest req) throws Exception {
		    SolrQueryResponse rsp = new SolrQueryResponse();
		    SolrCore core = req.getCore();
		    core.execute(core.getRequestHandler(handler),req,rsp);
		    if (rsp.getException() != null) {
		      throw rsp.getException();
		    }
		    return rsp;
	  }


	}