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

package org.apache.solr.search.function;

import org.apache.lucene.index.IndexReader;
import org.apache.solr.search.function.DocValues;
import org.apache.solr.search.function.ValueSource;
import org.apache.lucene.search.FieldCache;

import java.io.IOException;
import java.util.Map;

/**
 * Obtains the ordinal of the field value from the default Lucene {@link org.apache.lucene.search.FieldCache} using getStringIndex()
 * and reverses the order.
 * <br>
 * The native lucene index order is used to assign an ordinal value for each field value.
 * <br>Field values (terms) are lexicographically ordered by unicode value, and numbered starting at 1.
 * <br>
 * Example of reverse ordinal (rord):<br>
 *  If there were only three field values: "apple","banana","pear"
 * <br>then rord("apple")=3, rord("banana")=2, ord("pear")=1
 * <p>
 *  WARNING: ord() depends on the position in an index and can thus change when other documents are inserted or deleted,
 *  or if a MultiSearcher is used.
 * <br>
 *  WARNING: as of Solr 1.4, ord() and rord() can cause excess memory use since they must use a FieldCache entry
 * at the top level reader, while sorting and function queries now use entries at the segment level.  Hence sorting
 * or using a different function query, in addition to ord()/rord() will double memory use.
 * 
 * @version $Id: ReverseOrdFieldSource.java 826531 2009-10-18 21:41:54Z yonik $
 */

public class ReverseOrdFieldSource extends ValueSource {
  public String field;

  public ReverseOrdFieldSource(String field) {
    this.field = field;
  }

  public String description() {
    return "rord("+field+')';
  }

  public DocValues getValues(Map context, IndexReader reader) throws IOException {
    final FieldCache.StringIndex sindex = FieldCache.DEFAULT.getStringIndex(reader, field);

    final int arr[] = sindex.order;
    final int end = sindex.lookup.length;

    return new DocValues() {
      public float floatVal(int doc) {
        return (float)(end - arr[doc]);
      }

      public int intVal(int doc) {
        return (int)(end - arr[doc]);
      }

      public long longVal(int doc) {
        return (long)(end - arr[doc]);
      }

      public double doubleVal(int doc) {
        return (double)(end - arr[doc]);
      }

      public String strVal(int doc) {
        // the string value of the ordinal, not the string itself
        return Integer.toString((end - arr[doc]));
      }

      public String toString(int doc) {
        return description() + '=' + strVal(doc);
      }
    };
  }

  public boolean equals(Object o) {
    if (o.getClass() !=  ReverseOrdFieldSource.class) return false;
    ReverseOrdFieldSource other = (ReverseOrdFieldSource)o;
    return this.field.equals(other.field);
  }

  private static final int hcode = ReverseOrdFieldSource.class.hashCode();
  public int hashCode() {
    return hcode + field.hashCode();
  };

}
