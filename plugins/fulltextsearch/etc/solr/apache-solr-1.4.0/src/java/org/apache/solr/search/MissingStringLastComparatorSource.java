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

package org.apache.solr.search;

import org.apache.lucene.search.*;
import org.apache.lucene.index.IndexReader;

import java.io.IOException;


public class MissingStringLastComparatorSource extends FieldComparatorSource {
  public static final String bigString="\uffff\uffff\uffff\uffff\uffff\uffff\uffff\uffffNULL_VAL";

  private final String missingValueProxy;

  public MissingStringLastComparatorSource() {
    this(bigString);
  }

  /** Creates a {@link FieldComparatorSource} that uses <tt>missingValueProxy</tt> as the value to return from ScoreDocComparator.sortValue()
   * which is only used my multisearchers to determine how to collate results from their searchers.
   *
   * @param missingValueProxy   The value returned when sortValue() is called for a document missing the sort field.
   * This value is *not* normally used for sorting, but used to create
   */
  public MissingStringLastComparatorSource(String missingValueProxy) {
    this.missingValueProxy=missingValueProxy;
  }

  public FieldComparator newComparator(String fieldname, int numHits, int sortPos, boolean reversed) throws IOException {
    return new MissingLastOrdComparator(numHits, fieldname, sortPos, reversed, true, missingValueProxy);
  }

}

// Copied from Lucene and modified since the Lucene version couldn't
// be extended or have it's values accessed.

// NOTE: there were a number of other interesting String
// comparators explored, but this one seemed to perform
// best all around.  See LUCENE-1483 for details.
class MissingLastOrdComparator extends FieldComparator {

    private final int[] ords;
    private final String[] values;
    private final int[] readerGen;

    private int currentReaderGen = -1;
    private String[] lookup;
    private int[] order;
    private final String field;

    private int bottomSlot = -1;
    private int bottomOrd;
    private String bottomValue;
    private final boolean reversed;
    private final int sortPos;

    private final int nullCmp;
    private final Comparable nullVal;

    public MissingLastOrdComparator(int numHits, String field, int sortPos, boolean reversed, boolean sortMissingLast, Comparable nullVal) {
      ords = new int[numHits];
      values = new String[numHits];
      readerGen = new int[numHits];
      this.sortPos = sortPos;
      this.reversed = reversed;
      this.field = field;
      this.nullCmp = sortMissingLast ? 1 : -1;
      this.nullVal = nullVal;
    }

  public int compare(int slot1, int slot2) {
      int ord1 = ords[slot1];
      int ord2 = ords[slot2];
      int cmp = ord1-ord2;
      if (ord1==0 || ord2==0) {
        if (cmp==0) return 0;
        return ord1==0 ? nullCmp : -nullCmp;
      }

      if (readerGen[slot1] == readerGen[slot2]) {
        if (cmp != 0) {
          return cmp;
        }
      }

      final String val1 = values[slot1];
      final String val2 = values[slot2];
      if (val1 == null) {
        if (val2 == null) {
          return 0;
        }
        return nullCmp;
      } else if (val2 == null) {
        return -nullCmp;
      }
      return val1.compareTo(val2);
    }

    public int compareBottom(int doc) {
      assert bottomSlot != -1;
      int order = this.order[doc];
      final int cmp = bottomOrd - order;
      if (bottomOrd==0 || order==0) {
        if (cmp==0) return 0;
        return bottomOrd==0 ? nullCmp : -nullCmp;        
      }

      if (cmp != 0) {
        return cmp;
      }

      final String val2 = lookup[order];
      if (bottomValue == null) {
        if (val2 == null) {
          return 0;
        }
        // bottom wins
        return nullCmp;
      } else if (val2 == null) {
        // doc wins
        return -nullCmp;
      }
      return bottomValue.compareTo(val2);
    }

    private void convert(int slot) {
      readerGen[slot] = currentReaderGen;
      int index = 0;
      String value = values[slot];
      if (value == null) {
        ords[slot] = 0;
        return;
      }

      if (sortPos == 0 && bottomSlot != -1 && bottomSlot != slot) {
        // Since we are the primary sort, the entries in the
        // queue are bounded by bottomOrd:
        assert bottomOrd < lookup.length;
        if (reversed) {
          index = binarySearch(lookup, value, bottomOrd, lookup.length-1);
        } else {
          index = binarySearch(lookup, value, 0, bottomOrd);
        }
      } else {
        // Full binary search
        index = binarySearch(lookup, value);
      }

      if (index < 0) {
        index = -index - 2;
      }
      ords[slot] = index;
    }

    public void copy(int slot, int doc) {
      final int ord = order[doc];
      ords[slot] = ord;
      assert ord >= 0;
      values[slot] = lookup[ord];
      readerGen[slot] = currentReaderGen;
    }

    public void setNextReader(IndexReader reader, int docBase) throws IOException {
      FieldCache.StringIndex currentReaderValues = FieldCache.DEFAULT.getStringIndex(reader, field);
      currentReaderGen++;
      order = currentReaderValues.order;
      lookup = currentReaderValues.lookup;
      assert lookup.length > 0;
      if (bottomSlot != -1) {
        convert(bottomSlot);
        bottomOrd = ords[bottomSlot];
      }
    }

    public void setBottom(final int bottom) {
      bottomSlot = bottom;
      if (readerGen[bottom] != currentReaderGen) {
        convert(bottomSlot);
      }
      bottomOrd = ords[bottom];
      assert bottomOrd >= 0;
      assert bottomOrd < lookup.length;
      bottomValue = values[bottom];
    }

    public int sortType() {
      return SortField.STRING;
    }

    public Comparable value(int slot) {
      Comparable v = values[slot];
      return v==null ? nullVal : v;
    }

    public String[] getValues() {
      return values;
    }

    public int getBottomSlot() {
      return bottomSlot;
    }

    public String getField() {
      return field;
    }
  }