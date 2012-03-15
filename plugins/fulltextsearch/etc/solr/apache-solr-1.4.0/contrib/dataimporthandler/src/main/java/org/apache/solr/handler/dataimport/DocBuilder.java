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

package org.apache.solr.handler.dataimport;

import org.apache.solr.common.SolrInputDocument;
import org.apache.solr.core.SolrCore;
import static org.apache.solr.handler.dataimport.SolrWriter.LAST_INDEX_KEY;
import org.apache.solr.schema.SchemaField;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.util.*;
import java.util.concurrent.atomic.AtomicBoolean;
import java.util.concurrent.atomic.AtomicLong;
import java.text.ParseException;

/**
 * <p> DocBuilder is responsible for creating Solr documents out of the given configuration. It also maintains
 * statistics information. It depends on the EntityProcessor implementations to fetch data. </p>
 * <p/>
 * <b>This API is experimental and subject to change</b>
 *
 * @version $Id: DocBuilder.java 823446 2009-10-09 08:05:37Z noble $
 * @since solr 1.3
 */
public class DocBuilder {

  private static final Logger LOG = LoggerFactory.getLogger(DocBuilder.class);

  private static final Date EPOCH = new Date(0);

  DataImporter dataImporter;

  private DataConfig.Document document;

  private DataConfig.Entity root;

  @SuppressWarnings("unchecked")
  private Map statusMessages = Collections.synchronizedMap(new LinkedHashMap());

  public Statistics importStatistics = new Statistics();

  SolrWriter writer;

  DataImporter.RequestParams requestParameters;

  boolean verboseDebug = false;

  private Map<String, Object> session = new HashMap<String, Object>();

  static final ThreadLocal<DocBuilder> INSTANCE = new ThreadLocal<DocBuilder>();
  Map<String, Object> functionsNamespace;
  private Properties persistedProperties;

  public DocBuilder(DataImporter dataImporter, SolrWriter writer, DataImporter.RequestParams reqParams) {
    INSTANCE.set(this);
    this.dataImporter = dataImporter;
    this.writer = writer;
    DataImporter.QUERY_COUNT.set(importStatistics.queryCount);
    requestParameters = reqParams;
    verboseDebug = requestParameters.debug && requestParameters.verbose;
    functionsNamespace = EvaluatorBag.getFunctionsNamespace(this.dataImporter.getConfig().functions, this);
    persistedProperties = writer.readIndexerProperties();
  }

  public VariableResolverImpl getVariableResolver() {
    try {
      VariableResolverImpl resolver = new VariableResolverImpl();
      Map<String, Object> indexerNamespace = new HashMap<String, Object>();
      if (persistedProperties.getProperty(LAST_INDEX_TIME) != null) {
        indexerNamespace.put(LAST_INDEX_TIME, persistedProperties.getProperty(LAST_INDEX_TIME));
      } else  {
        // set epoch
        indexerNamespace.put(LAST_INDEX_TIME, DataImporter.DATE_TIME_FORMAT.get().format(EPOCH));
      }
      indexerNamespace.put(INDEX_START_TIME, dataImporter.getIndexStartTime());
      indexerNamespace.put("request", requestParameters.requestParams);
      indexerNamespace.put("functions", functionsNamespace);
      for (DataConfig.Entity entity : dataImporter.getConfig().document.entities) {
        String key = entity.name + "." + SolrWriter.LAST_INDEX_KEY;
        String lastIndex = persistedProperties.getProperty(key);
        if (lastIndex != null) {
          indexerNamespace.put(key, lastIndex);
        } else  {
          indexerNamespace.put(key, DataImporter.DATE_TIME_FORMAT.get().format(EPOCH));
        }
      }
      resolver.addNamespace(DataConfig.IMPORTER_NS_SHORT, indexerNamespace);
      resolver.addNamespace(DataConfig.IMPORTER_NS, indexerNamespace);
      return resolver;
    } catch (Exception e) {
      DataImportHandlerException.wrapAndThrow(DataImportHandlerException.SEVERE, e);
      // unreachable statement
      return null;
    }
  }

  private void invokeEventListener(String className) {
    try {
      EventListener listener = (EventListener) loadClass(className, dataImporter.getCore()).newInstance();
      notifyListener(listener);
    } catch (Exception e) {
      DataImportHandlerException.wrapAndThrow(DataImportHandlerException.SEVERE, e, "Unable to load class : " + className);
    }
  }

  private void notifyListener(EventListener listener) {
    String currentProcess;
    if (dataImporter.getStatus() == DataImporter.Status.RUNNING_DELTA_DUMP) {
      currentProcess = Context.DELTA_DUMP;
    } else {
      currentProcess = Context.FULL_DUMP;
    }
    listener.onEvent(new ContextImpl(null, getVariableResolver(), null, currentProcess, session, null, this));
  }

  @SuppressWarnings("unchecked")
  public void execute() {
    dataImporter.store(DataImporter.STATUS_MSGS, statusMessages);
    document = dataImporter.getConfig().document;
    final AtomicLong startTime = new AtomicLong(System.currentTimeMillis());
    statusMessages.put(TIME_ELAPSED, new Object() {
      public String toString() {
        return getTimeElapsedSince(startTime.get());
      }
    });

    statusMessages.put(DataImporter.MSG.TOTAL_QUERIES_EXECUTED,
            importStatistics.queryCount);
    statusMessages.put(DataImporter.MSG.TOTAL_ROWS_EXECUTED,
            importStatistics.rowsCount);
    statusMessages.put(DataImporter.MSG.TOTAL_DOC_PROCESSED,
            importStatistics.docCount);
    statusMessages.put(DataImporter.MSG.TOTAL_DOCS_SKIPPED,
            importStatistics.skipDocCount);

    List<String> entities = requestParameters.entities;

    // Trigger onImportStart
    if (document.onImportStart != null) {
      invokeEventListener(document.onImportStart);
    }
    AtomicBoolean fullCleanDone = new AtomicBoolean(false);
    //we must not do a delete of *:* multiple times if there are multiple root entities to be run
    Properties lastIndexTimeProps = new Properties();
    lastIndexTimeProps.setProperty(LAST_INDEX_KEY,
            DataImporter.DATE_TIME_FORMAT.get().format(dataImporter.getIndexStartTime()));
    for (DataConfig.Entity e : document.entities) {
      if (entities != null && !entities.contains(e.name))
        continue;
      lastIndexTimeProps.setProperty(e.name + "." + LAST_INDEX_KEY,
              DataImporter.DATE_TIME_FORMAT.get().format(new Date()));
      root = e;
      String delQuery = e.allAttributes.get("preImportDeleteQuery");
      if (dataImporter.getStatus() == DataImporter.Status.RUNNING_DELTA_DUMP) {
        cleanByQuery(delQuery, fullCleanDone);
        doDelta();
        delQuery = e.allAttributes.get("postImportDeleteQuery");
        if (delQuery != null) {
          fullCleanDone.set(false);
          cleanByQuery(delQuery, fullCleanDone);
        }
      } else {
        cleanByQuery(delQuery, fullCleanDone);
        doFullDump();
        delQuery = e.allAttributes.get("postImportDeleteQuery");
        if (delQuery != null) {
          fullCleanDone.set(false);
          cleanByQuery(delQuery, fullCleanDone);
        }
      }
      statusMessages.remove(DataImporter.MSG.TOTAL_DOC_PROCESSED);
    }

    if (stop.get()) {
      // Dont commit if aborted using command=abort
      statusMessages.put("Aborted", DataImporter.DATE_TIME_FORMAT.get().format(new Date()));
      rollback();
    } else {
      // Do not commit unnecessarily if this is a delta-import and no documents were created or deleted
      if (!requestParameters.clean) {
        if (importStatistics.docCount.get() > 0 || importStatistics.deletedDocCount.get() > 0) {
          finish(lastIndexTimeProps);
        }
      } else {
        // Finished operation normally, commit now
        finish(lastIndexTimeProps);
      }
      if (document.onImportEnd != null) {
        invokeEventListener(document.onImportEnd);
      }
    }

    statusMessages.remove(TIME_ELAPSED);
    statusMessages.put(DataImporter.MSG.TOTAL_DOC_PROCESSED, ""+ importStatistics.docCount.get());
    if(importStatistics.failedDocCount.get() > 0)
      statusMessages.put(DataImporter.MSG.TOTAL_FAILED_DOCS, ""+ importStatistics.failedDocCount.get());

    statusMessages.put("Time taken ", getTimeElapsedSince(startTime.get()));
    LOG.info("Time taken = " + getTimeElapsedSince(startTime.get()));
  }

  @SuppressWarnings("unchecked")
  private void finish(Properties lastIndexTimeProps) {
    LOG.info("Import completed successfully");
    statusMessages.put("", "Indexing completed. Added/Updated: "
            + importStatistics.docCount + " documents. Deleted "
            + importStatistics.deletedDocCount + " documents.");
    if(requestParameters.commit) {
      writer.commit(requestParameters.optimize);
      addStatusMessage("Committed");
      if (requestParameters.optimize)
        addStatusMessage("Optimized");
    }
    writer.persist(lastIndexTimeProps);
  }

  void rollback() {
    writer.rollback();
    statusMessages.put("", "Indexing failed. Rolled back all changes.");
    addStatusMessage("Rolledback");
  }

  @SuppressWarnings("unchecked")
  private void doFullDump() {
    addStatusMessage("Full Dump Started");
    buildDocument(getVariableResolver(), null, null, root, true,
            null);
  }

  @SuppressWarnings("unchecked")
  private void doDelta() {
    addStatusMessage("Delta Dump started");
    VariableResolverImpl resolver = getVariableResolver();

    if (document.deleteQuery != null) {
      writer.deleteByQuery(document.deleteQuery);
    }

    addStatusMessage("Identifying Delta");
    LOG.info("Starting delta collection.");
    Set<Map<String, Object>> deletedKeys = new HashSet<Map<String, Object>>();
    Set<Map<String, Object>> allPks = collectDelta(root, resolver, deletedKeys);
    if (stop.get())
      return;
    addStatusMessage("Deltas Obtained");
    addStatusMessage("Building documents");
    if (!deletedKeys.isEmpty()) {
      allPks.removeAll(deletedKeys);      
      deleteAll(deletedKeys);
      // Make sure that documents are not re-created
    }
    deletedKeys = null;

    statusMessages.put("Total Changed Documents", allPks.size());
    VariableResolverImpl vri = getVariableResolver();
    Iterator<Map<String, Object>> pkIter = allPks.iterator();
    while (pkIter.hasNext()) {
      Map<String, Object> map = pkIter.next();
      vri.addNamespace(DataConfig.IMPORTER_NS_SHORT + ".delta", map);
      buildDocument(vri, null, map, root, true, null);
      pkIter.remove();
      // check for abort
      if (stop.get())
        break;
    }

    if (!stop.get()) {
      LOG.info("Delta Import completed successfully");
    }
  }

  private void deleteAll(Set<Map<String, Object>> deletedKeys) {
    LOG.info("Deleting stale documents ");
    Iterator<Map<String, Object>> iter = deletedKeys.iterator();
    while (iter.hasNext()) {
      Map<String, Object> map = iter.next();
      Object key = map.get(root.getPk()); 
      if(key == null) {
        LOG.warn("no key was available for deleteted pk query");
        continue;
      }
      writer.deleteDoc(key);
      importStatistics.deletedDocCount.incrementAndGet();      
      iter.remove();
    }
  }

  @SuppressWarnings("unchecked")
  public void addStatusMessage(String msg) {
    statusMessages.put(msg, DataImporter.DATE_TIME_FORMAT.get().format(new Date()));
  }

  @SuppressWarnings("unchecked")
  private void buildDocument(VariableResolverImpl vr, DocWrapper doc,
                             Map<String, Object> pk, DataConfig.Entity entity, boolean isRoot,
                             ContextImpl parentCtx) {

    EntityProcessorWrapper entityProcessor = getEntityProcessor(entity);

    ContextImpl ctx = new ContextImpl(entity, vr, null,
            pk == null ? Context.FULL_DUMP : Context.DELTA_DUMP,
            session, parentCtx, this);
    entityProcessor.init(ctx);

    if (requestParameters.start > 0) {
      writer.log(SolrWriter.DISABLE_LOGGING, null, null);
    }

    if (verboseDebug) {
      writer.log(SolrWriter.START_ENTITY, entity.name, null);
    }

    int seenDocCount = 0;

    try {
      while (true) {
        if (stop.get())
          return;
        if(importStatistics.docCount.get() > (requestParameters.start + requestParameters.rows)) break;
        try {
          seenDocCount++;

          if (seenDocCount > requestParameters.start) {
            writer.log(SolrWriter.ENABLE_LOGGING, null, null);
          }

          if (verboseDebug && entity.isDocRoot) {
            writer.log(SolrWriter.START_DOC, entity.name, null);
          }
          if (doc == null && entity.isDocRoot) {
            doc = new DocWrapper();
            ctx.setDoc(doc);
            DataConfig.Entity e = entity;
            while (e.parentEntity != null) {
              addFields(e.parentEntity, doc, (Map<String, Object>) vr
                      .resolve(e.parentEntity.name), vr);
              e = e.parentEntity;
            }
          }

          Map<String, Object> arow = entityProcessor.nextRow();
          if (arow == null) {
            break;
          }

          // Support for start parameter in debug mode
          if (entity.isDocRoot) {
            if (seenDocCount <= requestParameters.start)
              continue;
            if (seenDocCount > requestParameters.start + requestParameters.rows) {
              LOG.info("Indexing stopped at docCount = " + importStatistics.docCount);
              break;
            }
          }

          if (verboseDebug) {
            writer.log(SolrWriter.ENTITY_OUT, entity.name, arow);
          }
          importStatistics.rowsCount.incrementAndGet();
          if (doc != null) {
            handleSpecialCommands(arow, doc);
            addFields(entity, doc, arow, vr);
          }
          if (entity.entities != null) {
            vr.addNamespace(entity.name, arow);
            for (DataConfig.Entity child : entity.entities) {
              buildDocument(vr, doc, null, child, false, ctx);
            }
            vr.removeNamespace(entity.name);
          }

          if (entity.isDocRoot) {
            if (stop.get())
              return;
            if (!doc.isEmpty()) {
              boolean result = writer.upload(doc);
              doc = null;
              if (result){
                importStatistics.docCount.incrementAndGet();
              } else {
                importStatistics.failedDocCount.incrementAndGet(); 
              }
            }
          }

        } catch (DataImportHandlerException e) {
          if (verboseDebug) {
            writer.log(SolrWriter.ENTITY_EXCEPTION, entity.name, e);
          }
          if(e.getErrCode() == DataImportHandlerException.SKIP_ROW){
            continue;
          }
          if (isRoot) {
            if (e.getErrCode() == DataImportHandlerException.SKIP) {
              importStatistics.skipDocCount.getAndIncrement();
            } else {
              LOG.error("Exception while processing: "
                      + entity.name + " document : " + doc, e);
            }
            if (e.getErrCode() == DataImportHandlerException.SEVERE)
              throw e;
          } else
            throw e;
        } catch (Throwable t) {
          if (verboseDebug) {
            writer.log(SolrWriter.ENTITY_EXCEPTION, entity.name, t);
          }
          throw new DataImportHandlerException(DataImportHandlerException.SEVERE, t);
        } finally {
          if (verboseDebug) {
            writer.log(SolrWriter.ROW_END, entity.name, null);
            if (entity.isDocRoot)
              writer.log(SolrWriter.END_DOC, null, null);
          }
        }
      }
    } finally {
      if (verboseDebug) {
        writer.log(SolrWriter.END_ENTITY, null, null);
      }
      entityProcessor.destroy();
    }
  }

  static class DocWrapper extends SolrInputDocument {
    //final SolrInputDocument solrDocument = new SolrInputDocument();
    Map<String ,Object> session;

    public void setSessionAttribute(String key, Object val){
      if(session == null) session = new HashMap<String, Object>();
      session.put(key, val);
    }

    public Object getSessionAttribute(String key) {
      return session == null ? null : session.get(key);
    }
  }

  private void handleSpecialCommands(Map<String, Object> arow, DocWrapper doc) {
    Object value = arow.get("$deleteDocById");
    if (value != null) {
      if (value instanceof Collection) {
        Collection collection = (Collection) value;
        for (Object o : collection) {
          writer.deleteDoc(o.toString());
        }
      } else {
        writer.deleteDoc(value);
      }
    }
    value = arow.get("$deleteDocByQuery");
    if (value != null) {
      if (value instanceof Collection) {
        Collection collection = (Collection) value;
        for (Object o : collection) {
          writer.deleteByQuery(o.toString());
        }
      } else {
        writer.deleteByQuery(value.toString());
      }
    }
    value = arow.get("$docBoost");
    if (value != null) {
      float value1 = 1.0f;
      if (value instanceof Number) {
        value1 = ((Number) value).floatValue();
      } else {
        value1 = Float.parseFloat(value.toString());
      }
      doc.setDocumentBoost(value1);
    }

    value = arow.get("$skipDoc");
    if (value != null) {
      if (Boolean.parseBoolean(value.toString())) {
        throw new DataImportHandlerException(DataImportHandlerException.SKIP,
                "Document skipped :" + arow);
      }
    }

    value = arow.get("$skipRow");
    if (value != null) {
      if (Boolean.parseBoolean(value.toString())) {
        throw new DataImportHandlerException(DataImportHandlerException.SKIP_ROW);
      }
    }
  }

  @SuppressWarnings("unchecked")
  private void addFields(DataConfig.Entity entity, DocWrapper doc,
                         Map<String, Object> arow, VariableResolver vr) {
    for (Map.Entry<String, Object> entry : arow.entrySet()) {
      String key = entry.getKey();
      Object value = entry.getValue();
      if (value == null)  continue;
      if (key.startsWith("$")) continue;
      List<DataConfig.Field> field = entity.colNameVsField.get(key);
      if (field == null && dataImporter.getSchema() != null) {
        // This can be a dynamic field or a field which does not have an entry in data-config ( an implicit field)
        SchemaField sf = dataImporter.getSchema().getFieldOrNull(key);
        if (sf == null) {
          sf = dataImporter.getConfig().lowerNameVsSchemaField.get(key.toLowerCase());
        }
        if (sf != null) {
          addFieldToDoc(entry.getValue(), sf.getName(), 1.0f, sf.multiValued(), doc);
        }
        //else do nothing. if we add it it may fail
      } else {
        if (field != null) {
          for (DataConfig.Field f : field) {
            String name = f.getName();
            if(f.dynamicName){
              name =  vr.replaceTokens(name);
            }
            if (f.toWrite) addFieldToDoc(entry.getValue(), name, f.boost, f.multiValued, doc);
          }
        }
      }
    }
  }

  private void addFieldToDoc(Object value, String name, float boost, boolean multiValued, DocWrapper doc) {
    if (value instanceof Collection) {
      Collection collection = (Collection) value;
      if (multiValued) {
        for (Object o : collection) {
          if (o != null)
            doc.addField(name, o, boost);
        }
      } else {
        if (doc.getField(name) == null)
          for (Object o : collection) {
            if (o != null)  {
              doc.addField(name, o, boost);
              break;
            }
          }
      }
    } else if (multiValued) {
      if (value != null)  {
        doc.addField(name, value, boost);
      }
    } else {
      if (doc.getField(name) == null && value != null)
        doc.addField(name, value, boost);
    }
  }

  private EntityProcessorWrapper getEntityProcessor(DataConfig.Entity entity) {
    if (entity.processor != null)
      return entity.processor;
    EntityProcessor entityProcessor;
    if (entity.proc == null) {
      entityProcessor = new SqlEntityProcessor();
    } else {
      try {
        entityProcessor = (EntityProcessor) loadClass(entity.proc, dataImporter.getCore())
                .newInstance();
      } catch (Exception e) {
        throw new DataImportHandlerException(DataImportHandlerException.SEVERE,
                "Unable to load EntityProcessor implementation for entity:"
                        + entity.name, e);
      }
    }
    return entity.processor = new EntityProcessorWrapper(entityProcessor, this);
  }

  /**
   * <p> Collects unique keys of all Solr documents for whom one or more source tables have been changed since the last
   * indexed time. </p> <p> Note: In our definition, unique key of Solr document is the primary key of the top level
   * entity (unless skipped using docRoot=false) in the Solr document in data-config.xml </p>
   *
   * @return an iterator to the list of keys for which Solr documents should be updated.
   */
  @SuppressWarnings("unchecked")
  public Set<Map<String, Object>> collectDelta(DataConfig.Entity entity, VariableResolverImpl resolver,
                                               Set<Map<String, Object>> deletedRows) {
    //someone called abort
    if (stop.get())
      return new HashSet();

    EntityProcessor entityProcessor = getEntityProcessor(entity);
    ContextImpl context1 = new ContextImpl(entity, resolver, null, Context.FIND_DELTA, session, null, this);
    entityProcessor.init(context1);

    Set<Map<String, Object>> myModifiedPks = new HashSet<Map<String, Object>>();

    if (entity.entities != null) {

      for (DataConfig.Entity entity1 : entity.entities) {
        //this ensures that we start from the leaf nodes
        myModifiedPks.addAll(collectDelta(entity1, resolver, deletedRows));
        //someone called abort
        if (stop.get())
          return new HashSet();
      }

    }
    // identifying the modified rows for this entity

    Set<Map<String, Object>> deltaSet = new HashSet<Map<String, Object>>();
    LOG.info("Running ModifiedRowKey() for Entity: " + entity.name);
    //get the modified rows in this entity
    while (true) {
      Map<String, Object> row = entityProcessor.nextModifiedRowKey();

      if (row == null)
        break;

      deltaSet.add(row);
      importStatistics.rowsCount.incrementAndGet();
      // check for abort
      if (stop.get())
        return new HashSet();
    }
    //get the deleted rows for this entity
    Set<Map<String, Object>> deletedSet = new HashSet<Map<String, Object>>();
    Set<Map<String, Object>> deltaRemoveSet = new HashSet<Map<String, Object>>();
    while (true) {
      Map<String, Object> row = entityProcessor.nextDeletedRowKey();
      if (row == null)
        break;

      //Check to see if this delete is in the current delta set
      for (Map<String, Object> modifiedRow : deltaSet) {
        if (modifiedRow.get(entity.getPk()).equals(row.get(entity.getPk()))) {
          deltaRemoveSet.add(modifiedRow);
        }
      }

      deletedSet.add(row);
      importStatistics.rowsCount.incrementAndGet();
      // check for abort
      if (stop.get())
        return new HashSet();
    }

    //asymmetric Set difference
    deltaSet.removeAll(deltaRemoveSet);

    LOG.info("Completed ModifiedRowKey for Entity: " + entity.name + " rows obtained : " + deltaSet.size());
    LOG.info("Completed DeletedRowKey for Entity: " + entity.name + " rows obtained : " + deletedSet.size());

    myModifiedPks.addAll(deltaSet);
    Set<Map<String, Object>> parentKeyList = new HashSet<Map<String, Object>>();
    //all that we have captured is useless (in a sub-entity) if no rows in the parent is modified because of these
    //propogate up the changes in the chain
    if (entity.parentEntity != null) {
      // identifying deleted rows with deltas

      for (Map<String, Object> row : myModifiedPks) {
        getModifiedParentRows(resolver.addNamespace(entity.name, row), entity.name, entityProcessor, parentKeyList);
        // check for abort
        if (stop.get())
          return new HashSet();
      }
      // running the same for deletedrows
      for (Map<String, Object> row : deletedSet) {
        getModifiedParentRows(resolver.addNamespace(entity.name, row), entity.name, entityProcessor, parentKeyList);
        // check for abort
        if (stop.get())
          return new HashSet();
      }
    }
    LOG.info("Completed parentDeltaQuery for Entity: " + entity.name);
    if (entity.isDocRoot)
      deletedRows.addAll(deletedSet);

    return entity.isDocRoot ? myModifiedPks : new HashSet<Map<String, Object>>(
            parentKeyList);
  }

  private void getModifiedParentRows(VariableResolverImpl resolver,
                                     String entity, EntityProcessor entityProcessor,
                                     Set<Map<String, Object>> parentKeyList) {
    try {
      while (true) {
        Map<String, Object> parentRow = entityProcessor
                .nextModifiedParentRowKey();
        if (parentRow == null)
          break;

        parentKeyList.add(parentRow);
        importStatistics.rowsCount.incrementAndGet();
        // check for abort
        if (stop.get())
          return;
      }

    } finally {
      resolver.removeNamespace(entity);
    }
  }

  public void abort() {
    stop.set(true);
  }

  private AtomicBoolean stop = new AtomicBoolean(false);

  public static final String TIME_ELAPSED = "Time Elapsed";

  static String getTimeElapsedSince(long l) {
    l = System.currentTimeMillis() - l;
    return (l / (60000 * 60)) % 60 + ":" + (l / 60000) % 60 + ":" + (l / 1000)
            % 60 + "." + l % 1000;
  }

  @SuppressWarnings("unchecked")
  static Class loadClass(String name, SolrCore core) throws ClassNotFoundException {
    try {
      return core != null ?
              core.getResourceLoader().findClass(name) :
              Class.forName(name);
    } catch (Exception e) {
      try {
        String n = DocBuilder.class.getPackage().getName() + "." + name;
        return core != null ?
                core.getResourceLoader().findClass(n) :
                Class.forName(n);
      } catch (Exception e1) {
        throw new ClassNotFoundException("Unable to load " + name + " or " + DocBuilder.class.getPackage().getName() + "." + name, e);
      }
    }
  }

  public static class Statistics {
    public AtomicLong docCount = new AtomicLong();

    public AtomicLong deletedDocCount = new AtomicLong();

    public AtomicLong failedDocCount = new AtomicLong();

    public AtomicLong rowsCount = new AtomicLong();

    public AtomicLong queryCount = new AtomicLong();

    public AtomicLong skipDocCount = new AtomicLong();

    public Statistics add(Statistics stats) {
      this.docCount.addAndGet(stats.docCount.get());
      this.deletedDocCount.addAndGet(stats.deletedDocCount.get());
      this.rowsCount.addAndGet(stats.rowsCount.get());
      this.queryCount.addAndGet(stats.queryCount.get());

      return this;
    }

    public Map<String, Object> getStatsSnapshot() {
      Map<String, Object> result = new HashMap<String, Object>();
      result.put("docCount", docCount.get());
      result.put("deletedDocCount", deletedDocCount.get());
      result.put("rowCount", rowsCount.get());
      result.put("queryCount", rowsCount.get());
      result.put("skipDocCount", skipDocCount.get());
      return result;
    }

  }

  private void cleanByQuery(String delQuery, AtomicBoolean completeCleanDone) {
    delQuery = getVariableResolver().replaceTokens(delQuery);
    if (requestParameters.clean) {
      if (delQuery == null && !completeCleanDone.get()) {
        writer.doDeleteAll();
        completeCleanDone.set(true);
      } else if (delQuery != null) {
        writer.deleteByQuery(delQuery);
      }
    }
  }

  public static final String LAST_INDEX_TIME = "last_index_time";
  public static final String INDEX_START_TIME = "index_start_time";
}
