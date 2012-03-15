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

import junit.framework.TestCase;

import java.util.Random;
import java.util.Arrays;
import java.io.IOException;

import org.apache.lucene.util.OpenBitSet;
import org.apache.lucene.util.OpenBitSetIterator;
import org.apache.lucene.index.IndexReader;
import org.apache.lucene.index.FilterIndexReader;
import org.apache.lucene.index.MultiReader;
import org.apache.lucene.search.Filter;
import org.apache.lucene.search.DocIdSet;
import org.apache.lucene.search.DocIdSetIterator;

/**
 * @version $Id$
 */
public class TestDocSet extends TestCase {
  Random rand = new Random();
  float loadfactor;

  public OpenBitSet getRandomSet(int sz, int bitsToSet) {
    OpenBitSet bs = new OpenBitSet(sz);
    if (sz==0) return bs;
    for (int i=0; i<bitsToSet; i++) {
      bs.fastSet(rand.nextInt(sz));
    }
    return bs;
  }

  public DocSet getHashDocSet(OpenBitSet bs) {
    int[] docs = new int[(int)bs.cardinality()];
    OpenBitSetIterator iter = new OpenBitSetIterator(bs);
    for (int i=0; i<docs.length; i++) {
      docs[i] = iter.nextDoc();
    }
    return new HashDocSet(docs,0,docs.length);
  }

  public DocSet getIntDocSet(OpenBitSet bs) {
    int[] docs = new int[(int)bs.cardinality()];
    OpenBitSetIterator iter = new OpenBitSetIterator(bs);
    for (int i=0; i<docs.length; i++) {
      docs[i] = iter.nextDoc();
    }
    return new SortedIntDocSet(docs);
  }


  public DocSet getBitDocSet(OpenBitSet bs) {
    return new BitDocSet(bs);
  }

  public DocSet getDocSlice(OpenBitSet bs) {
    int len = (int)bs.cardinality();
    int[] arr = new int[len+5];
    arr[0]=10; arr[1]=20; arr[2]=30; arr[arr.length-1]=1; arr[arr.length-2]=2;
    int offset = 3;
    int end = offset + len;

    OpenBitSetIterator iter = new OpenBitSetIterator(bs);
    // put in opposite order... DocLists are not ordered.
    for (int i=end-1; i>=offset; i--) {
      arr[i] = iter.nextDoc();
    }

    return new DocSlice(offset, len, arr, null, len*2, 100.0f);
  }


  public DocSet getDocSet(OpenBitSet bs) {
    switch(rand.nextInt(10)) {
      case 0: return getHashDocSet(bs);

      case 1: return getBitDocSet(bs);
      case 2: return getBitDocSet(bs);
      case 3: return getBitDocSet(bs);

      case 4: return getIntDocSet(bs);
      case 5: return getIntDocSet(bs);
      case 6: return getIntDocSet(bs);
      case 7: return getIntDocSet(bs);
      case 8: return getIntDocSet(bs);

      case 9: return getDocSlice(bs);
    }
    return null;
  }

  public void checkEqual(OpenBitSet bs, DocSet set) {
    for (int i=0; i<bs.capacity(); i++) {
      assertEquals(bs.get(i), set.exists(i));
    }
    assertEquals(bs.cardinality(), set.size());
  }

  public void iter(DocSet d1, DocSet d2) {
    // HashDocSet and DocList doesn't iterate in order.
    if (d1 instanceof HashDocSet || d2 instanceof HashDocSet || d1 instanceof DocList || d2 instanceof DocList) return;

    DocIterator i1 = d1.iterator();
    DocIterator i2 = d2.iterator();

    assert(i1.hasNext() == i2.hasNext());

    for(;;) {
      boolean b1 = i1.hasNext();
      boolean b2 = i2.hasNext();
      assertEquals(b1,b2);
      if (!b1) break;
      assertEquals(i1.nextDoc(), i2.nextDoc());
    }
  }

  protected void doSingle(int maxSize) {
    int sz = rand.nextInt(maxSize+1);
    int sz2 = rand.nextInt(maxSize);
    OpenBitSet bs1 = getRandomSet(sz, rand.nextInt(sz+1));
    OpenBitSet bs2 = getRandomSet(sz, rand.nextInt(sz2+1));

    DocSet a1 = new BitDocSet(bs1);
    DocSet a2 = new BitDocSet(bs2);
    DocSet b1 = getDocSet(bs1);
    DocSet b2 = getDocSet(bs2);

    checkEqual(bs1,b1);
    checkEqual(bs2,b2);

    iter(a1,b1);
    iter(a2,b2);

    OpenBitSet a_and = (OpenBitSet) bs1.clone(); a_and.and(bs2);
    OpenBitSet a_or = (OpenBitSet) bs1.clone(); a_or.or(bs2);
    // OpenBitSet a_xor = (OpenBitSet)bs1.clone(); a_xor.xor(bs2);
    OpenBitSet a_andn = (OpenBitSet) bs1.clone(); a_andn.andNot(bs2);

    checkEqual(a_and, b1.intersection(b2));
    checkEqual(a_or, b1.union(b2));
    checkEqual(a_andn, b1.andNot(b2));

    assertEquals(a_and.cardinality(), b1.intersectionSize(b2));
    assertEquals(a_or.cardinality(), b1.unionSize(b2));
    assertEquals(a_andn.cardinality(), b1.andNotSize(b2));
  }


  public void doMany(int maxSz, int iter) {
    for (int i=0; i<iter; i++) {
      doSingle(maxSz);
    }
  }

  public void testRandomDocSets() {
    // Make the size big enough to go over certain limits (such as one set
    // being 8 times the size of another in the int set, or going over 2 times
    // 64 bits for the bit doc set.  Smaller sets can hit more boundary conditions though.

    doMany(130, 10000);
    // doMany(130, 1000000);
  }

  public DocSet getRandomDocSet(int n, int maxDoc) {
    OpenBitSet obs = new OpenBitSet(maxDoc);
    int[] a = new int[n];
    for (int i=0; i<n; i++) {
      for(;;) {
        int idx = rand.nextInt(maxDoc);
        if (obs.getAndSet(idx)) continue;
        a[i]=idx;
        break;
      }
    }

    if (n <= smallSetCuttoff) {
      if (smallSetType ==0) {
        Arrays.sort(a);
        return new SortedIntDocSet(a);
      } else if (smallSetType ==1) {
        Arrays.sort(a);
        return loadfactor!=0 ? new HashDocSet(a,0,n,1/loadfactor) : new HashDocSet(a,0,n);
      }
    }

    return new BitDocSet(obs, n);
  }

  public DocSet[] getRandomSets(int nSets, int minSetSize, int maxSetSize, int maxDoc) {
    DocSet[] sets = new DocSet[nSets];

    for (int i=0; i<nSets; i++) {
      int sz;
      sz = rand.nextInt(maxSetSize-minSetSize+1)+minSetSize;
      // different distribution
      // sz = (maxSetSize+1)/(rand.nextInt(maxSetSize)+1) + minSetSize;
      sets[i] = getRandomDocSet(sz,maxDoc);
    }

    return sets;
  }

  /**** needs code insertion into HashDocSet
  public void testCollisions() {
    loadfactor=.75f;
    rand=new Random(12345);  // make deterministic
    int maxSetsize=4000;
    int nSets=256;
    int iter=1;
    int[] maxDocs=new int[] {100000,500000,1000000,5000000,10000000};
    int ret=0;
    long start=System.currentTimeMillis();
    for (int maxDoc : maxDocs) {
      int cstart = HashDocSet.collisions;
      DocSet[] sets = getRandomHashSets(nSets,maxSetsize, maxDoc);
      for (DocSet s1 : sets) {
        for (DocSet s2 : sets) {
          if (s1!=s2) ret += s1.intersectionSize(s2);
        }
      }
      int cend = HashDocSet.collisions;
      System.out.println("maxDoc="+maxDoc+"\tcollisions="+(cend-cstart));      
    }
    long end=System.currentTimeMillis();
    System.out.println("testIntersectionSizePerformance="+(end-start)+" ms");
    if (ret==-1)System.out.println("wow!");
    System.out.println("collisions="+HashDocSet.collisions);

  }
  ***/

  public static int smallSetType = 0;  // 0==sortedint, 1==hash, 2==openbitset
  public static int smallSetCuttoff=3000;

  /***
  public void testIntersectionSizePerformance() {
    loadfactor=.75f; // for HashDocSet    
    rand=new Random(1);  // make deterministic

    int minBigSetSize=1,maxBigSetSize=30000;
    int minSmallSetSize=1,maxSmallSetSize=30000;
    int nSets=1024;
    int iter=1;
    int maxDoc=1000000;


    smallSetCuttoff = maxDoc>>6; // break even for SortedIntSet is /32... but /64 is better for performance
    // smallSetCuttoff = maxDoc;


    DocSet[] bigsets = getRandomSets(nSets, minBigSetSize, maxBigSetSize, maxDoc);
    DocSet[] smallsets = getRandomSets(nSets, minSmallSetSize, maxSmallSetSize, maxDoc);
    int ret=0;
    long start=System.currentTimeMillis();
    for (int i=0; i<iter; i++) {
      for (DocSet s1 : bigsets) {
        for (DocSet s2 : smallsets) {
          ret += s1.intersectionSize(s2);
        }
      }
    }
    long end=System.currentTimeMillis();
    System.out.println("intersectionSizePerformance="+(end-start)+" ms");
    System.out.println("ret="+ret);
  }
   ***/

  /****
  public void testExistsPerformance() {
    loadfactor=.75f;
    rand=new Random(12345);  // make deterministic
    int maxSetsize=4000;
    int nSets=512;
    int iter=1;
    int maxDoc=1000000;
    DocSet[] sets = getRandomHashSets(nSets,maxSetsize, maxDoc);
    int ret=0;
    long start=System.currentTimeMillis();
    for (int i=0; i<iter; i++) {
      for (DocSet s1 : sets) {
        for (int j=0; j<maxDoc; j++) {
          ret += s1.exists(j) ? 1 :0;
        }
      }
    }
    long end=System.currentTimeMillis();
    System.out.println("testExistsSizePerformance="+(end-start)+" ms");
    if (ret==-1)System.out.println("wow!");
  }
   ***/

   /**** needs code insertion into HashDocSet
   public void testExistsCollisions() {
    loadfactor=.75f;
    rand=new Random(12345);  // make deterministic
    int maxSetsize=4000;
    int nSets=512;
    int[] maxDocs=new int[] {100000,500000,1000000,5000000,10000000};
    int ret=0;

    for (int maxDoc : maxDocs) {
      int mask = (BitUtil.nextHighestPowerOfTwo(maxDoc)>>1)-1;
      DocSet[] sets = getRandomHashSets(nSets,maxSetsize, maxDoc);
      int cstart = HashDocSet.collisions;      
      for (DocSet s1 : sets) {
        for (int j=0; j<maxDocs[0]; j++) {
          int idx = rand.nextInt()&mask;
          ret += s1.exists(idx) ? 1 :0;
        }
      }
      int cend = HashDocSet.collisions;
      System.out.println("maxDoc="+maxDoc+"\tcollisions="+(cend-cstart));
    }
    if (ret==-1)System.out.println("wow!");
    System.out.println("collisions="+HashDocSet.collisions);
  }
  ***/

  public IndexReader dummyIndexReader(final int maxDoc) {

    IndexReader r = new FilterIndexReader(null) {
      @Override
      public int maxDoc() {
        return maxDoc;
      }

      @Override
      public boolean hasDeletions() {
        return false;
      }

      @Override
      public IndexReader[] getSequentialSubReaders() {
        return null;
      }
    };
    return r;
  }

  public IndexReader dummyMultiReader(int nSeg, int maxDoc) {
    if (nSeg==1 && rand.nextBoolean()) return dummyIndexReader(rand.nextInt(maxDoc));

    IndexReader[] subs = new IndexReader[rand.nextInt(nSeg)+1];
    for (int i=0; i<subs.length; i++) {
      subs[i] = dummyIndexReader(rand.nextInt(maxDoc));
    }

    MultiReader mr = new MultiReader(subs);
    return mr;
  }

  public void doTestIteratorEqual(DocIdSet a, DocIdSet b) throws IOException {
    DocIdSetIterator ia = a.iterator();
    DocIdSetIterator ib = b.iterator();

    // test for next() equivalence
    for(;;) {
      int da = ia.nextDoc();
      int db = ib.nextDoc();
      assertEquals(da, db);
      assertEquals(ia.docID(), ib.docID());
      if (da==DocIdSetIterator.NO_MORE_DOCS) break;
    }

    for (int i=0; i<10; i++) {
      // test random skipTo() and next()
      ia = a.iterator();
      ib = b.iterator();
      int doc = -1;
      for (;;) {
        int da,db;
        if (rand.nextBoolean()) {
          da = ia.nextDoc();
          db = ib.nextDoc();
        } else {
          int target = doc + rand.nextInt(10) + 1;  // keep in mind future edge cases like probing (increase if necessary)
          da = ia.advance(target);
          db = ib.advance(target);
        }

        assertEquals(da, db);
        assertEquals(ia.docID(), ib.docID());
        if (da==DocIdSetIterator.NO_MORE_DOCS) break;
        doc = da;
      }
    }
  }

  public void doFilterTest(SolrIndexReader reader) throws IOException {
    OpenBitSet bs = getRandomSet(reader.maxDoc(), rand.nextInt(reader.maxDoc()+1));
    DocSet a = new BitDocSet(bs);
    DocSet b = getIntDocSet(bs);

    Filter fa = a.getTopFilter();
    Filter fb = b.getTopFilter();

    // test top-level
    DocIdSet da = fa.getDocIdSet(reader);
    DocIdSet db = fb.getDocIdSet(reader);
    doTestIteratorEqual(da, db);

    // first test in-sequence sub readers
    for (SolrIndexReader sir : reader.getLeafReaders()) {
      da = fa.getDocIdSet(sir);
      db = fb.getDocIdSet(sir);
      doTestIteratorEqual(da, db);
    }  

    int nReaders = reader.getLeafReaders().length;
    // now test out-of-sequence sub readers
    for (int i=0; i<nReaders; i++) {
      SolrIndexReader sir = reader.getLeafReaders()[rand.nextInt(nReaders)];
      da = fa.getDocIdSet(sir);
      db = fb.getDocIdSet(sir);
      doTestIteratorEqual(da, db);
    }
  }

  public void testFilter() throws IOException {
    // keeping these numbers smaller help hit more edge cases
    int maxSeg=4;
    int maxDoc=5;    // increase if future changes add more edge cases (like probing a certain distance in the bin search)

    for (int i=0; i<5000; i++) {
      IndexReader r = dummyMultiReader(maxSeg, maxDoc);
      SolrIndexReader sir = new SolrIndexReader(r, null, 0);
      doFilterTest(sir);
    }
  }
}
