<?php
require_once "FedoraRelationships.php";

use \PHPUnit\Framework\TestCase;

class FedoraRelationshipsInternalTest extends TestCase {

  function setUp() : void {
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->api = new FedoraApi($connection);
    $cache = new SimpleCache();
    $repository = new FedoraRepository($this->api, $cache);
    $this->object = $repository->constructObject('test:awesome');
    $this->datastream = $this->object->constructDatastream('test');
    $this->datastream2 = $this->object->constructDatastream('test2');

    $this->datastream->relationships->add(ISLANDORA_RELS_EXT_URI, 'hasAwesomeness', 'jonathan:green');
    $this->datastream->relationships->add(FEDORA_MODEL_URI, 'hasModel', 'islandora:model');
    $this->datastream->relationships->add(ISLANDORA_RELS_EXT_URI, 'isPage', '22', TRUE);
    $this->datastream->relationships->add(FEDORA_RELS_EXT_URI, 'isMemberOfCollection', 'theawesomecollection:awesome');
    $this->datastream->relationships->add(FEDORA_MODEL_URI, 'hasModel', 'islandora:woot');

    $this->datastream2->relationships->add(ISLANDORA_RELS_INT_URI, 'isPage', '22', TRUE);
  }

  function testGetAll() {
    $relationships = $this->datastream->relationships->get();
    $this->assertEquals(5, count($relationships));
    $this->assertEquals('hasAwesomeness', $relationships[0]['predicate']['value']);
    $this->assertEquals('jonathan:green', $relationships[0]['object']['value']);
    $this->assertEquals('hasModel', $relationships[1]['predicate']['value']);
    $this->assertEquals('islandora:model', $relationships[1]['object']['value']);
    $this->assertEquals('isPage', $relationships[2]['predicate']['value']);
    $this->assertEquals('22', $relationships[2]['object']['value']);
    $this->assertTrue($relationships[2]['object']['literal']);
    $this->assertEquals('isMemberOfCollection', $relationships[3]['predicate']['value']);
    $this->assertEquals('theawesomecollection:awesome', $relationships[3]['object']['value']);
  }

  function testGetOne() {
    $rels = $this->datastream->relationships->get(FEDORA_MODEL_URI, 'hasModel');
    $this->assertEquals(2, count($rels));
    $this->assertEquals('hasModel', $rels[0]['predicate']['value']);
    $this->assertEquals('islandora:model', $rels[0]['object']['value']);
    $this->assertEquals('hasModel', $rels[1]['predicate']['value']);
    $this->assertEquals('islandora:woot', $rels[1]['object']['value']);
  }

  function testRemovePredicate() {
    $this->datastream->relationships->remove(FEDORA_MODEL_URI, 'hasModel');
    $rels = $this->datastream->relationships->get(FEDORA_MODEL_URI, 'hasModel');
    $this->assertEquals(0, count($rels));
  }

  function testRemoveSpecificPredicate() {
    $this->datastream->relationships->remove(FEDORA_MODEL_URI, 'hasModel', 'islandora:model');
    $rels = $this->datastream->relationships->get(FEDORA_MODEL_URI, 'hasModel');
    $this->assertEquals(1, count($rels));
    $this->assertEquals('hasModel', $rels[0]['predicate']['value']);
    $this->assertEquals('islandora:woot', $rels[0]['object']['value']);
  }

  function testRemoveObject() {
    $this->datastream->relationships->remove(NULL, NULL, 'islandora:model');
    $rels = $this->datastream->relationships->get(FEDORA_MODEL_URI, 'hasModel');
    $this->assertEquals(1, count($rels));
    $this->assertEquals('hasModel', $rels[0]['predicate']['value']);
    $this->assertEquals('islandora:woot', $rels[0]['object']['value']);
  }

  function testNullPredicateURI() {
    // RELS-EXT TEST
    $this->object->relationships->add(NULL, 'isViewableByUser', 'test user', TRUE);
    $rels_ext = $this->object->relationships->get(NULL, 'isViewableByUser');
    $this->assertEquals(1, count($rels_ext));
    $this->object->relationships->remove(NULL, 'isViewableByUser');
    $rels_ext = $this->object->relationships->get(NULL, 'isViewableByUser');
    $this->assertEquals(0, count($rels_ext));

    // RELS-INT TEST
    $this->datastream->relationships->add(NULL, 'isViewableByUser', 'test user', TRUE);
    $rels_int = $this->datastream->relationships->get(NULL, 'isViewableByUser');
    $this->assertEquals(1, count($rels_int));
    $this->datastream->relationships->remove(NULL, 'isViewableByUser');
    $rels_int = $this->datastream->relationships->get(NULL, 'isViewableByUser');
    $this->assertEquals(0, count($rels_int));
  }

  function testPurge() {
    $this->assertTrue($this->object->purgeDatastream('RELS-INT'));
  }

  function testMultipleWritesWhenPurging() {
    $this->markTestIncomplete('This is a current bug that needs to be addressed in ISLANDORA-2068.');
    $this->object->purgeDatastream('RELS-INT');
    $this->object->ingestDatastream($this->datastream);
    $this->object->ingestDatastream($this->datastream2);

    $this->datastream->relationships->remove(ISLANDORA_RELS_INT_URI, 'whargarbl');
    $this->datastream2->relationships->remove(ISLANDORA_RELS_INT_URI, 'omnomnom');

    $this->datastream->relationships->add(ISLANDORA_RELS_INT_URI, 'isWhargarbl', 'whargarbl', TRUE);
    $this->datastream2->relationships->add(ISLANDORA_RELS_INT_URI, 'isOmNom', 'omnom', TRUE);
    $this->assertEquals(1, count($this->datastream->relationships->get(ISLANDORA_RELS_INT_URI, 'isWhargarbl')));
    $this->assertEquals(1, count($this->datastream->relationships->get(ISLANDORA_RELS_INT_URI, 'isOmNom')));
  }

  function testMultipleWritesWithoutPurging() {
    $connection = new RepositoryConnection(FEDORAURL, FEDORAUSER, FEDORAPASS);
    $this->api = new FedoraApi($connection);
    $cache = new SimpleCache();
    $repository = new FedoraRepository($this->api, $cache);
    $object = $repository->constructObject('om:nom');
    $ds = $object->constructDatastream('om');
    $ds2 = $object->constructDatastream('nom');

    $ds->relationships->remove(ISLANDORA_RELS_INT_URI, 'whargarbl');
    $ds2->relationships->remove(ISLANDORA_RELS_INT_URI, 'omnomnom');

    $ds->relationships->add(ISLANDORA_RELS_INT_URI, 'isWhargarbl', 'whargarbl', TRUE);
    $ds2->relationships->add(ISLANDORA_RELS_INT_URI, 'isOmNom', 'omnom', TRUE);
    $this->assertEquals(1, count($ds->relationships->get(ISLANDORA_RELS_INT_URI, 'isWhargarbl')));
    $this->assertEquals(1, count($ds2->relationships->get(ISLANDORA_RELS_INT_URI, 'isOmNom')));
  }

}
