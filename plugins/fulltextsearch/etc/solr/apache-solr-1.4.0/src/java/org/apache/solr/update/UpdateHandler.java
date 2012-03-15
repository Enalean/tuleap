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

package org.apache.solr.update;


import org.apache.lucene.index.Term;
import org.apache.lucene.document.Document;
import org.apache.lucene.document.Field;
import org.apache.lucene.document.Fieldable;
import org.apache.lucene.search.HitCollector;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import java.util.Vector;
import java.io.IOException;

import org.apache.solr.search.SolrIndexSearcher;
import org.apache.solr.schema.IndexSchema;
import org.apache.solr.schema.SchemaField;
import org.apache.solr.schema.FieldType;
import org.apache.solr.common.SolrException;
import org.apache.solr.util.plugin.SolrCoreAware;
import org.apache.solr.core.*;

/**
 * <code>UpdateHandler</code> handles requests to change the index
 * (adds, deletes, commits, optimizes, etc).
 *
 * @version $Id: UpdateHandler.java 812035 2009-09-07 08:25:27Z noble $
 * @since solr 0.9
 */

public abstract class UpdateHandler implements SolrInfoMBean {
  protected final static Logger log = LoggerFactory.getLogger(UpdateHandler.class);

  protected final SolrCore core;
  protected final IndexSchema schema;

  protected final SchemaField idField;
  protected final FieldType idFieldType;
  protected final Term idTerm; // prototype term to avoid interning fieldname

  protected Vector<SolrEventListener> commitCallbacks = new Vector<SolrEventListener>();
  protected Vector<SolrEventListener> optimizeCallbacks = new Vector<SolrEventListener>();

  private void parseEventListeners() {
    for (PluginInfo pluginInfo : core.getSolrConfig().getPluginInfos(SolrEventListener.class.getName())) {
      String event = pluginInfo.attributes.get("event");
      SolrEventListener listener = core.createEventListener(pluginInfo.className);
      listener.init(pluginInfo.initArgs);
      if ("postCommit".equals(event)) {
        commitCallbacks.add(listener);
        log.info("added SolrEventListener for postCommit: " + listener);
      } else if ("postOptimize".equals(event)) {
        optimizeCallbacks.add(listener);
        log.info("added SolrEventListener for postOptimize: " + listener);
      }
    }
  }

  protected void callPostCommitCallbacks() {
    for (SolrEventListener listener : commitCallbacks) {
      listener.postCommit();
    }
  }

  protected void callPostOptimizeCallbacks() {
    for (SolrEventListener listener : optimizeCallbacks) {
      listener.postCommit();
    }
  }

  public UpdateHandler(SolrCore core)  {
    this.core=core;
    schema = core.getSchema();
    idField = schema.getUniqueKeyField();
    idFieldType = idField!=null ? idField.getType() : null;
    idTerm = idField!=null ? new Term(idField.getName(),"") : null;
    parseEventListeners();
  }

  protected SolrIndexWriter createMainIndexWriter(String name, boolean removeAllExisting) throws IOException {
    return new SolrIndexWriter(name,core.getNewIndexDir(), core.getDirectoryFactory(), removeAllExisting, schema, core.getSolrConfig().mainIndexConfig, core.getDeletionPolicy());
  }

  protected final Term idTerm(String readableId) {
    // to correctly create the Term, the string needs to be run
    // through the Analyzer for that field.
    return new Term(idField.getName(), idFieldType.toInternal(readableId));
  }

  protected final String getIndexedId(Document doc) {
    if (idField == null)
      throw new SolrException( SolrException.ErrorCode.BAD_REQUEST,"Operation requires schema to have a unique key field");

    // Right now, single valued fields that require value transformation from external to internal (indexed)
    // form have that transformation already performed and stored as the field value.
    Fieldable[] id = doc.getFieldables( idField.getName() );
    if (id == null || id.length < 1)
      throw new SolrException( SolrException.ErrorCode.BAD_REQUEST,"Document is missing uniqueKey field " + idField.getName());
    if( id.length > 1 )
      throw new SolrException( SolrException.ErrorCode.BAD_REQUEST,"Document specifies multiple unique ids! " + idField.getName());

    return idFieldType.storedToIndexed( id[0] );
  }

  protected final String getIndexedIdOptional(Document doc) {
    if (idField == null) return null;
    Field f = doc.getField(idField.getName());
    if (f == null) return null;
    return idFieldType.storedToIndexed(f);
  }


  public abstract int addDoc(AddUpdateCommand cmd) throws IOException;
  public abstract void delete(DeleteUpdateCommand cmd) throws IOException;
  public abstract void deleteByQuery(DeleteUpdateCommand cmd) throws IOException;
  public abstract int mergeIndexes(MergeIndexesCommand cmd) throws IOException;
  public abstract void commit(CommitUpdateCommand cmd) throws IOException;
  public abstract void rollback(RollbackUpdateCommand cmd) throws IOException;
  public abstract void close() throws IOException;


  static class DeleteHitCollector extends HitCollector {
    public int deleted=0;
    public final SolrIndexSearcher searcher;

    public DeleteHitCollector(SolrIndexSearcher searcher) {
      this.searcher = searcher;
    }

    public void collect(int doc, float score) {
      try {
        searcher.getReader().deleteDocument(doc);
        deleted++;
      } catch (IOException e) {
        // don't try to close the searcher on failure for now...
        // try { closeSearcher(); } catch (Exception ee) { SolrException.log(log,ee); }
        throw new SolrException( SolrException.ErrorCode.SERVER_ERROR,"Error deleting doc# "+doc,e,false);
      }
    }
  }


  /**
   * NOTE: this function is not thread safe.  However, it is safe to call within the
   * <code>inform( SolrCore core )</code> function for <code>SolrCoreAware</code> classes.
   * Outside <code>inform</code>, this could potentially throw a ConcurrentModificationException
   *
   * @see SolrCoreAware
   */
  public void registerCommitCallback( SolrEventListener listener )
  {
    commitCallbacks.add( listener );
  }

  /**
   * NOTE: this function is not thread safe.  However, it is safe to call within the
   * <code>inform( SolrCore core )</code> function for <code>SolrCoreAware</code> classes.
   * Outside <code>inform</code>, this could potentially throw a ConcurrentModificationException
   *
   * @see SolrCoreAware
   */
  public void registerOptimizeCallback( SolrEventListener listener )
  {
    optimizeCallbacks.add( listener );
  }
}



