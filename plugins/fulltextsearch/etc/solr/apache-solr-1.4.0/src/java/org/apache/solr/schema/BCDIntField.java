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

package org.apache.solr.schema;

import org.apache.lucene.search.SortField;
import org.apache.solr.search.function.ValueSource;
import org.apache.lucene.document.Fieldable;
import org.apache.solr.util.BCDUtils;
import org.apache.solr.request.XMLWriter;
import org.apache.solr.request.TextResponseWriter;

import java.util.Map;
import java.io.IOException;
/**
 * @version $Id: BCDIntField.java 555343 2007-07-11 17:46:25Z hossman $
 */
public class BCDIntField extends FieldType {
  protected void init(IndexSchema schema, Map<String,String> args) {
  }

  public SortField getSortField(SchemaField field,boolean reverse) {
    return getStringSort(field,reverse);
  }

  public ValueSource getValueSource(SchemaField field) {
    throw new UnsupportedOperationException("ValueSource not implemented");
  }

  public String toInternal(String val) {
    // TODO? make sure each character is a digit?
    return BCDUtils.base10toBase10kSortableInt(val);
  }

  public String toExternal(Fieldable f) {
    return indexedToReadable(f.stringValue());
  }
  
  // Note, this can't return type 'Integer' because BCDStrField and BCDLong extend it
  @Override
  public Object toObject(Fieldable f) {
    return Integer.valueOf( toExternal(f) );
  }

  public String indexedToReadable(String indexedForm) {
    return BCDUtils.base10kSortableIntToBase10(indexedForm);
  }

  public void write(XMLWriter xmlWriter, String name, Fieldable f) throws IOException {
    xmlWriter.writeInt(name,toExternal(f));
  }

  public void write(TextResponseWriter writer, String name, Fieldable f) throws IOException {
    writer.writeInt(name,toExternal(f));
  }
}





