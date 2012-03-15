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

package org.apache.solr.util;

import junit.framework.TestCase;

import java.util.Random;
import java.util.BitSet;

import org.apache.lucene.util.OpenBitSetIterator;
import org.apache.lucene.search.DocIdSetIterator;

/**
 * @deprecated
 * @version $Id$
 */
public class TestOpenBitSet extends TestCase {
  static Random rand = new Random();

  void doGet(BitSet a, OpenBitSet b) {
    int max = a.size();
    for (int i=0; i<max; i++) {
      if (a.get(i) != b.get(i)) {
        fail("mismatch: BitSet=["+i+"]="+a.get(i));
      }
    }
  }

  void doNextSetBit(BitSet a, OpenBitSet b) {
    int aa=-1,bb=-1;
    do {
      aa = a.nextSetBit(aa+1);
      bb = b.nextSetBit(bb+1);
      assertEquals(aa,bb);
    } while (aa>=0);
  }

  // test interleaving different BitSetIterator.next()
  void doIterate(BitSet a, OpenBitSet b) {
    int aa=-1,bb=-1;
    OpenBitSetIterator iterator = new OpenBitSetIterator(b);
    do {
      aa = a.nextSetBit(aa+1);
      if (rand.nextBoolean()) {
        iterator.next();
        bb = iterator.doc();
      } else {
        iterator.skipTo(bb+1);
        bb = iterator.doc();
      }
      assertEquals(aa == -1 ? DocIdSetIterator.NO_MORE_DOCS : aa, bb);
    } while (aa>=0);
  }


  void doRandomSets(int maxSize, int iter) {
    BitSet a0=null;
    OpenBitSet b0=null;

    for (int i=0; i<iter; i++) {
      int sz = rand.nextInt(maxSize);
      BitSet a = new BitSet(sz);
      OpenBitSet b = new OpenBitSet(sz);

      // test the various ways of setting bits
      if (sz>0) {
        int nOper = rand.nextInt(sz);
        for (int j=0; j<nOper; j++) {
          int idx;         

          idx = rand.nextInt(sz);
          a.set(idx);
          b.fastSet(idx);
          idx = rand.nextInt(sz);
          a.clear(idx);
          b.fastClear(idx);
          idx = rand.nextInt(sz);
          a.flip(idx);
          b.fastFlip(idx);

          boolean val = b.flipAndGet(idx);
          boolean val2 = b.flipAndGet(idx);
          assertTrue(val != val2);

          val = b.getAndSet(idx);
          assertTrue(val2 == val);
          assertTrue(b.get(idx));
          
          if (!val) b.fastClear(idx);
          assertTrue(b.get(idx) == val);
        }
      }

      // test that the various ways of accessing the bits are equivalent
      doGet(a,b);

      // test ranges, including possible extension
      int fromIndex, toIndex;
      fromIndex = rand.nextInt(sz+80);
      toIndex = fromIndex + rand.nextInt((sz>>1)+1);
      BitSet aa = (BitSet)a.clone(); aa.flip(fromIndex,toIndex);
      OpenBitSet bb = (OpenBitSet)b.clone(); bb.flip(fromIndex,toIndex);

      doIterate(aa,bb);   // a problem here is from flip or doIterate

      fromIndex = rand.nextInt(sz+80);
      toIndex = fromIndex + rand.nextInt((sz>>1)+1);
      aa = (BitSet)a.clone(); aa.clear(fromIndex,toIndex);
      bb = (OpenBitSet)b.clone(); bb.clear(fromIndex,toIndex);

      doNextSetBit(aa,bb);  // a problem here is from clear() or nextSetBit

      fromIndex = rand.nextInt(sz+80);
      toIndex = fromIndex + rand.nextInt((sz>>1)+1);
      aa = (BitSet)a.clone(); aa.set(fromIndex,toIndex);
      bb = (OpenBitSet)b.clone(); bb.set(fromIndex,toIndex);

      doNextSetBit(aa,bb);  // a problem here is from set() or nextSetBit     


      if (a0 != null) {
        assertEquals( a.equals(a0), b.equals(b0));

        assertEquals(a.cardinality(), b.cardinality());

        BitSet a_and = (BitSet)a.clone(); a_and.and(a0);
        BitSet a_or = (BitSet)a.clone(); a_or.or(a0);
        BitSet a_xor = (BitSet)a.clone(); a_xor.xor(a0);
        BitSet a_andn = (BitSet)a.clone(); a_andn.andNot(a0);

        OpenBitSet b_and = (OpenBitSet)b.clone(); assertEquals(b,b_and); b_and.and(b0);
        OpenBitSet b_or = (OpenBitSet)b.clone(); b_or.or(b0);
        OpenBitSet b_xor = (OpenBitSet)b.clone(); b_xor.xor(b0);
        OpenBitSet b_andn = (OpenBitSet)b.clone(); b_andn.andNot(b0);

        doIterate(a_and,b_and);
        doIterate(a_or,b_or);
        doIterate(a_xor,b_xor);
        doIterate(a_andn,b_andn);

        assertEquals(a_and.cardinality(), b_and.cardinality());
        assertEquals(a_or.cardinality(), b_or.cardinality());
        assertEquals(a_xor.cardinality(), b_xor.cardinality());
        assertEquals(a_andn.cardinality(), b_andn.cardinality());

        // test non-mutating popcounts
        assertEquals(b_and.cardinality(), OpenBitSet.intersectionCount(b,b0));
        assertEquals(b_or.cardinality(), OpenBitSet.unionCount(b,b0));
        assertEquals(b_xor.cardinality(), OpenBitSet.xorCount(b,b0));
        assertEquals(b_andn.cardinality(), OpenBitSet.andNotCount(b,b0));
      }

      a0=a;
      b0=b;
    }
  }

  // large enough to flush obvious bugs, small enough to run in <.5 sec as part of a
  // larger testsuite.
  public void testSmall() {
    doRandomSets(1200,1000);
  }

  public void testBig() {
    // uncomment to run a bigger test (~2 minutes).
    // doRandomSets(2000,200000);
  }

  public void testEquals() {
    OpenBitSet b1 = new OpenBitSet(1111);
    OpenBitSet b2 = new OpenBitSet(2222);
    assertTrue(b1.equals(b2));
    assertTrue(b2.equals(b1));
    b1.set(10);
    assertFalse(b1.equals(b2));
    assertFalse(b2.equals(b1));
    b2.set(10);
    assertTrue(b1.equals(b2));
    assertTrue(b2.equals(b1));
    b2.set(2221);
    assertFalse(b1.equals(b2));
    assertFalse(b2.equals(b1));
    b1.set(2221);
    assertTrue(b1.equals(b2));
    assertTrue(b2.equals(b1));

    // try different type of object
    assertFalse(b1.equals(1));
  }

}



