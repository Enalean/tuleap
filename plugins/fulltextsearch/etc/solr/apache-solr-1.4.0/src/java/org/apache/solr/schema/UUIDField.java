package org.apache.solr.schema;

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

import java.io.IOException;
import java.util.Map;
import java.util.UUID;

import org.apache.lucene.document.Fieldable;
import org.apache.lucene.search.SortField;
import org.apache.solr.common.SolrException;
import org.apache.solr.request.TextResponseWriter;
import org.apache.solr.request.XMLWriter;

/**
 * This FieldType accepts UUID string values, as well as the special value 
 * of "NEW" which triggers generation of a new random UUID.
 *
 * @see UUID#toString
 * @see UUID#randomUUID
 * @version $Id: UUIDField.java 712014 2008-11-06 23:41:39Z yonik $
 */
public class UUIDField extends FieldType {
  private static final String NEW = "NEW";
  private static final char DASH='-';

  @Override
  protected void init(IndexSchema schema, Map<String, String> args) {
    super.init(schema, args);

    // Tokenizing makes no sense
    restrictProps(TOKENIZED);
  }

  @Override
  public SortField getSortField(SchemaField field, boolean reverse) {
    return getStringSort(field, reverse);
  }

  @Override
  public void write(XMLWriter xmlWriter, String name, Fieldable f)
      throws IOException {
    xmlWriter.writeStr(name, f.stringValue());
  }

  @Override
  public void write(TextResponseWriter writer, String name, Fieldable f)
      throws IOException {
    writer.writeStr(name, f.stringValue(), false);
  }

  /**
   * Generates a UUID if val is either null, empty or "NEW".
   * 
   * Otherwise it behaves much like a StrField but checks that the value given
   * is indeed a valid UUID.
   * 
   * @param val The value of the field
   * @see org.apache.solr.schema.FieldType#toInternal(java.lang.String)
   */
  @Override
  public String toInternal(String val) {
    if (val == null || 0==val.length() || NEW.equals(val)) {
      return UUID.randomUUID().toString().toLowerCase();
    } else {
      // we do some basic validation if 'val' looks like an UUID
      if (val.length() != 36 || val.charAt(8) != DASH || val.charAt(13) != DASH
          || val.charAt(18) != DASH || val.charAt(23) != DASH) {
        throw new SolrException(SolrException.ErrorCode.BAD_REQUEST,
            "Invalid UUID String: '" + val + "'");
      }

      return val.toLowerCase();
    }
  }

  public String toInternal(UUID uuid) {
    return uuid.toString().toLowerCase();
  }

  @Override
  public UUID toObject(Fieldable f) {
    return UUID.fromString(f.stringValue());
  }
}
