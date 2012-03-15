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

package org.apache.solr.util.xslt;

import java.io.IOException;
import java.io.InputStream;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import javax.xml.transform.Templates;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerConfigurationException;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.stream.StreamSource;

import org.apache.solr.common.ResourceLoader;
import org.apache.solr.core.SolrConfig;

/** Singleton that creates a Transformer for the XSLTServletFilter.
 *  For now, only caches the last created Transformer, but
 *  could evolve to use an LRU cache of Transformers.
 *  
 *  See http://www.javaworld.com/javaworld/jw-05-2003/jw-0502-xsl_p.html for
 *  one possible way of improving caching. 
 */

public class TransformerProvider {
  public static TransformerProvider instance = new TransformerProvider();

  private final TransformerFactory tFactory = TransformerFactory.newInstance();
  private String lastFilename;
  private Templates lastTemplates = null;
  private long cacheExpires = 0;
  
  private static Logger log;
  
  /** singleton */
  private TransformerProvider() {
    log = LoggerFactory.getLogger(TransformerProvider.class.getName());
    
    // tell'em: currently, we only cache the last used XSLT transform, and blindly recompile it
    // once cacheLifetimeSeconds expires
    log.warn(
        "The TransformerProvider's simplistic XSLT caching mechanism is not appropriate "
        + "for high load scenarios, unless a single XSLT transform is used"
        + " and xsltCacheLifetimeSeconds is set to a sufficiently high value."
    );
  }
  
  /** Return a new Transformer, possibly created from our cached Templates object  
   * @throws TransformerConfigurationException 
   */ 
  public synchronized Transformer getTransformer(SolrConfig solrConfig, String filename,int cacheLifetimeSeconds) throws IOException {
    // For now, the Templates are blindly reloaded once cacheExpires is over.
    // It'd be better to check the file modification time to reload only if needed.
    if(lastTemplates!=null && filename.equals(lastFilename) && System.currentTimeMillis() < cacheExpires) {
      if(log.isDebugEnabled()) {
        log.debug("Using cached Templates:" + filename);
      }
    } else {
      lastTemplates = getTemplates(solrConfig.getResourceLoader(), filename,cacheLifetimeSeconds);
    }
    
    Transformer result = null;
    
    try {
      result = lastTemplates.newTransformer();
    } catch(TransformerConfigurationException tce) {
      log.error(getClass().getName(), "getTransformer", tce);
      final IOException ioe = new IOException("newTransformer fails ( " + lastFilename + ")");
      ioe.initCause(tce);
      throw ioe;
    }
    
    return result;
  }
  
  /** Return a Templates object for the given filename */
  private Templates getTemplates(ResourceLoader loader, String filename,int cacheLifetimeSeconds) throws IOException {
    
    Templates result = null;
    lastFilename = null;
    try {
      if(log.isDebugEnabled()) {
        log.debug("compiling XSLT templates:" + filename);
      }
      final InputStream xsltStream = loader.openResource("xslt/" + filename);
      result = tFactory.newTemplates(new StreamSource(xsltStream));
    } catch (Exception e) {
      log.error(getClass().getName(), "newTemplates", e);
      final IOException ioe = new IOException("Unable to initialize Templates '" + filename + "'");
      ioe.initCause(e);
      throw ioe;
    }
    
    lastFilename = filename;
    lastTemplates = result;
    cacheExpires = System.currentTimeMillis() + (cacheLifetimeSeconds * 1000);
    
    return result;
  }
}
