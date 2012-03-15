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
import org.apache.lucene.document.Fieldable;
import org.apache.lucene.index.IndexReader;
import org.apache.solr.request.XMLWriter;
import org.apache.solr.request.TextResponseWriter;
import org.apache.solr.search.function.ValueSource;
import org.apache.solr.search.function.FieldCacheSource;
import org.apache.solr.search.function.DocValues;
import org.apache.solr.search.function.StringIndexDocValues;
import org.apache.solr.search.QParser;

import java.util.Map;
import java.io.IOException;
/**
 * @version $Id: StrField.java 817783 2009-09-22 19:23:07Z yonik $
 */
public class StrField extends CompressableField {
  protected void init(IndexSchema schema, Map<String,String> args) {
    super.init(schema, args);    
  }

  public SortField getSortField(SchemaField field,boolean reverse) {
    return getStringSort(field,reverse);
  }

  public void write(XMLWriter xmlWriter, String name, Fieldable f) throws IOException {
    xmlWriter.writeStr(name, f.stringValue());
  }

  public void write(TextResponseWriter writer, String name, Fieldable f) throws IOException {
    writer.writeStr(name, f.stringValue(), true);
  }

  public ValueSource getValueSource(SchemaField field, QParser parser) {
    return new StrFieldSource(field.getName());
  }
}


class StrFieldSource extends FieldCacheSource {

  public StrFieldSource(String field) {
    super(field);
  }

  public String description() {
    return "str(" + field + ')';
  }

  public DocValues getValues(Map context, IndexReader reader) throws IOException {
    return new StringIndexDocValues(this, reader, field) {
      protected String toTerm(String readableValue) {
        return readableValue;
      }

      public float floatVal(int doc) {
        return (float)intVal(doc);
      }

      public int intVal(int doc) {
        int ord=order[doc];
        return ord;
      }

      public long longVal(int doc) {
        return (long)intVal(doc);
      }

      public double doubleVal(int doc) {
        return (double)intVal(doc);
      }

      public String strVal(int doc) {
        int ord=order[doc];
        return lookup[ord];
      }

      public String toString(int doc) {
        return description() + '=' + strVal(doc);
      }
    };
  }

  public boolean equals(Object o) {
    return o instanceof StrFieldSource
            && super.equals(o);
  }

  private static int hcode = SortableFloatFieldSource.class.hashCode();
  public int hashCode() {
    return hcode + super.hashCode();
  };
}