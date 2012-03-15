package org.apache.solr.handler.extraction;


import org.apache.solr.common.SolrException;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.handler.ContentStreamHandlerBase;
import org.apache.solr.handler.ContentStreamLoader;
import org.apache.solr.request.SolrQueryRequest;
import org.apache.solr.update.processor.UpdateRequestProcessor;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import javax.xml.stream.XMLInputFactory;
/**
 * Handler for Xml meta data and rich documents like PDF or Word or any other file format that Tika handles that need the text to be extracted
 * first from the document.
 * <p/>
 */
public class CeliUpdateRequestHandler extends ContentStreamHandlerBase  {
	  public static Logger log = LoggerFactory.getLogger(CeliUpdateRequestHandler.class);

	  public static final String UPDATE_PROCESSOR = "update.processor";

	  // XML Constants
	  public static final String ADD = "add";
	  public static final String DELETE = "delete";
	  public static final String OPTIMIZE = "optimize";
	  public static final String COMMIT = "commit";
	  public static final String ROLLBACK = "rollback";
	  public static final String WAIT_SEARCHER = "waitSearcher";
	  public static final String WAIT_FLUSH = "waitFlush";

	  public static final String OVERWRITE = "overwrite";
	  public static final String COMMIT_WITHIN = "commitWithin";
	  
	  /**
	   * @deprecated use {@link #OVERWRITE}
	   */
	  public static final String OVERWRITE_COMMITTED = "overwriteCommitted";
	  
	  /**
	   * @deprecated use {@link #OVERWRITE}
	   */
	  public static final String OVERWRITE_PENDING = "overwritePending";

	  /**
	   * @deprecated use {@link #OVERWRITE}
	   */
	  public static final String ALLOW_DUPS = "allowDups";

	  XMLInputFactory inputFactory;


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
	    }
	    catch (IllegalArgumentException ex) {
	      // Other implementations will likely throw this exception since "reuse-instance"
	      // isimplementation specific.
	      log.debug("Unable to set the 'reuse-instance' property for the input chain: " + inputFactory);
	    }
 
	  }

	  protected ContentStreamLoader newLoader(SolrQueryRequest req, UpdateRequestProcessor processor) {
		CeliExtractingDocumentLoader cedl =  new CeliExtractingDocumentLoader(processor, inputFactory);
	    return cedl;
	  }


	  /**
	   *
	   *//*
	  @Deprecated
	  public void doLegacyUpdate(Reader input, Writer output) */

	  //////////////////////// SolrInfoMBeans methods //////////////////////

	  @Override
	  public String getDescription() {
	    return "Add documents by XML metadata and Rich document";
	  }

	  @Override
	  public String getVersion() {
	    return "$Revision: 1 $";
	  }

	  @Override
	  public String getSourceId() {
	    return "$Id: CeliUpdateRequestHandler.java 730269 2009-12-30 23:11:22Z pelibossian $";
	  }

	  @Override
	  public String getSource() {
	    return "celi-france";
	  }
	}

