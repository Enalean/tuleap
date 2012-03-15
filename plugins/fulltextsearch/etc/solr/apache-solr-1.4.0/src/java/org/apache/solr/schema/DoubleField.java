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

import org.apache.lucene.document.Fieldable;
import org.apache.lucene.search.SortField;
import org.apache.solr.request.TextResponseWriter;
import org.apache.solr.request.XMLWriter;
import org.apache.solr.search.function.DoubleFieldSource;
import org.apache.solr.search.function.ValueSource;

import java.io.IOException;
import java.util.Map;

/**
 * @version $Id: DoubleField.java 800966 2009-08-04 20:51:12Z yonik $
 */
public class DoubleField extends FieldType {
  protected void init(IndexSchema schema, Map<String, String> args) {
    restrictProps(SORT_MISSING_FIRST | SORT_MISSING_LAST);
  }

  /////////////////////////////////////////////////////////////
  public SortField getSortField(SchemaField field, boolean reverse) {
    return new SortField(field.name, SortField.DOUBLE, reverse);
  }

  public ValueSource getValueSource(SchemaField field) {
    // fieldCache doesn't support double
    return new DoubleFieldSource(field.name);
  }

  public void write(XMLWriter xmlWriter, String name, Fieldable f) throws IOException {
    xmlWriter.writeDouble(name, f.stringValue());
  }

  public void write(TextResponseWriter writer, String name, Fieldable f) throws IOException {
    String s = f.stringValue();

    // these values may be from a legacy lucene index, which may
    // not be properly formatted in some output formats, or may
    // incorrectly have a zero length.

    if (s.length()==0) {
      // zero length value means someone mistakenly indexed the value
      // instead of simply leaving it out.  Write a null value instead of a numeric.
      writer.writeNull(name);
      return;
    }

    try {
      double val = Double.parseDouble(s);
      writer.writeDouble(name, val);
    } catch (NumberFormatException e){
      // can't parse - write out the contents as a string so nothing is lost and
      // clients don't get a parse error.
      writer.writeStr(name, s, true);
    }
  }


  @Override
  public Double toObject(Fieldable f) {
    return Double.valueOf(toExternal(f));
  }
}
