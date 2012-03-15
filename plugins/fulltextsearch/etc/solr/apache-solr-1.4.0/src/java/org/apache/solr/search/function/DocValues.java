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

import org.apache.lucene.search.*;
import org.apache.lucene.index.IndexReader;
import org.apache.solr.util.NumberUtils;

import java.io.IOException;

/**
 * Represents field values as different types.
 * Normally created via a {@link ValueSource} for a particular field and reader.
 *
 * @version $Id: DocValues.java 757570 2009-03-23 22:08:44Z yonik $
 */

// DocValues is distinct from ValueSource because
// there needs to be an object created at query evaluation time that
// is not referenced by the query itself because:
// - Query objects should be MT safe
// - For caching, Query objects are often used as keys... you don't
//   want the Query carrying around big objects
public abstract class DocValues {

  public byte byteVal(int doc) { throw new UnsupportedOperationException(); }
  public short shortVal(int doc) { throw new UnsupportedOperationException(); }

  public float floatVal(int doc) { throw new UnsupportedOperationException(); }
  public int intVal(int doc) { throw new UnsupportedOperationException(); }
  public long longVal(int doc) { throw new UnsupportedOperationException(); }
  public double doubleVal(int doc) { throw new UnsupportedOperationException(); }
  public String strVal(int doc) { throw new UnsupportedOperationException(); }
  public abstract String toString(int doc);


  public Explanation explain(int doc) {
    return new Explanation(floatVal(doc), toString(doc));
  }

  public ValueSourceScorer getScorer(IndexReader reader) {
    return new ValueSourceScorer(reader, this);
  }

  // A RangeValueSource can't easily be a ValueSource that takes another ValueSource
  // because it needs different behavior depending on the type of fields.  There is also
  // a setup cost - parsing and normalizing params, and doing a binary search on the StringIndex.
  
  public ValueSourceScorer getRangeScorer(IndexReader reader, String lowerVal, String upperVal, boolean includeLower, boolean includeUpper) {
    float lower;
    float upper;

    if (lowerVal == null) {
      lower = Float.NEGATIVE_INFINITY;
    } else {
      lower = Float.parseFloat(lowerVal);
    }
    if (upperVal == null) {
      upper = Float.POSITIVE_INFINITY;
    } else {
      upper = Float.parseFloat(upperVal);
    }

    final float l = lower;
    final float u = upper;

    if (includeLower && includeUpper) {
      return new ValueSourceScorer(reader, this) {
        @Override
        public boolean matchesValue(int doc) {
          float docVal = floatVal(doc);
          return docVal >= l && docVal <= u;
        }
      };
    }
    else if (includeLower && !includeUpper) {
       return new ValueSourceScorer(reader, this) {
        @Override
        public boolean matchesValue(int doc) {
          float docVal = floatVal(doc);
          return docVal >= l && docVal < u;
        }
      };
    }
    else if (!includeLower && includeUpper) {
       return new ValueSourceScorer(reader, this) {
        @Override
        public boolean matchesValue(int doc) {
          float docVal = floatVal(doc);
          return docVal > l && docVal <= u;
        }
      };
    }
    else {
       return new ValueSourceScorer(reader, this) {
        @Override
        public boolean matchesValue(int doc) {
          float docVal = floatVal(doc);
          return docVal > l && docVal < u;
        }
      };
    }
  }
}



