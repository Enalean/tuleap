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

import org.apache.lucene.document.Document;
import org.apache.lucene.document.Field;
import org.apache.lucene.index.Term;
import org.apache.solr.common.SolrInputDocument;
import org.apache.solr.common.SolrInputField;
import org.apache.solr.schema.IndexSchema;
import org.apache.solr.schema.SchemaField;

/**
 * @version $Id: AddUpdateCommand.java 743163 2009-02-10 23:21:57Z yonik $
 */
public class AddUpdateCommand extends UpdateCommand {
   // optional id in "internal" indexed form... if it is needed and not supplied,
   // it will be obtained from the doc.
   public String indexedId;

   // The Lucene document to be indexed
   public Document doc;

   // Higher level SolrInputDocument, normally used to construct the Lucene Document
   // to index.
   public SolrInputDocument solrDoc;

   public boolean allowDups;
   public boolean overwritePending;
   public boolean overwriteCommitted;
   
   public Term updateTerm;
   public int commitWithin = -1;
   

   /** Reset state to reuse this object with a different document in the same request */
   public void clear() {
     doc = null;
     solrDoc = null;
     indexedId = null;
   }

   public SolrInputDocument getSolrInputDocument() {
     return solrDoc;
   }

   public Document getLuceneDocument(IndexSchema schema) {
     if (doc == null && solrDoc != null) {
       // TODO??  build the doc from the SolrDocument?
     }
     return doc;    
   }

   public String getIndexedId(IndexSchema schema) {
     if (indexedId == null) {
       SchemaField sf = schema.getUniqueKeyField();
       if (sf != null) {
         if (doc != null) {
           schema.getUniqueKeyField();
           Field storedId = doc.getField(sf.getName());
           indexedId = sf.getType().storedToIndexed(storedId);
         }
         if (solrDoc != null) {
           SolrInputField field = solrDoc.getField(sf.getName());
           if (field != null) {
             indexedId = sf.getType().toInternal( field.getFirstValue().toString() );
           }
         }
       }
     }
     return indexedId;
   }

   public String getPrintableId(IndexSchema schema) {
     SchemaField sf = schema.getUniqueKeyField();
     if (indexedId != null) {
       return schema.getUniqueKeyField().getType().indexedToReadable(indexedId);
     }

     if (doc != null) {
       return schema.printableUniqueKey(doc);
     }

     if (solrDoc != null) {
       SolrInputField field = solrDoc.getField(sf.getName());
       if (field != null) {
         return field.getFirstValue().toString();
       }
     }
     return "(null)";
   }

   public AddUpdateCommand() {
     super("add");
   }

   @Override
  public String toString() {
     StringBuilder sb = new StringBuilder(commandName);
     sb.append(':');
     if (indexedId !=null) sb.append("id=").append(indexedId);
     sb.append(",allowDups=").append(allowDups);
     sb.append(",overwritePending=").append(overwritePending);
     sb.append(",overwriteCommitted=").append(overwriteCommitted);
     return sb.toString();
   }
 }
