<?php

namespace Tools\Test\TestCase\Model\Behavior;

use Cake\Database\Query;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use TestApp\Model\Entity\BitmaskedComment;
//use TestApp\Model\Table\BitmaskedCommentsTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Core\Configure;
use Tools\Model\Behavior\BitmaskedBehavior;

//App::uses('AppModel', 'Model');

class BitmaskedBehaviorTest extends TestCase {

	public $fixtures = array(
		'plugin.tools.bitmasked_comments'
	);

	public $Comments;

	public function setUp() {
		parent::setUp();

		Configure::write('App.namespace', 'TestApp');

		$this->Comments = TableRegistry::get('BitmaskedComments');
		$this->Comments->addBehavior('Tools.Bitmasked', array('mappedField' => 'statuses'));
	}

	/**
	 * BitmaskedBehaviorTest::testEncodeBitmask()
	 *
	 * @return void
	 */
	public function testEncodeBitmask() {
		$res = $this->Comments->encodeBitmask(array(BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED));
		$expected = BitmaskedComment::STATUS_PUBLISHED | BitmaskedComment::STATUS_APPROVED;
		$this->assertSame($expected, $res);
	}

	/**
	 * BitmaskedBehaviorTest::testDecodeBitmask()
	 *
	 * @return void
	 */
	public function testDecodeBitmask() {
		$res = $this->Comments->decodeBitmask(BitmaskedComment::STATUS_PUBLISHED | BitmaskedComment::STATUS_APPROVED);
		$expected = array(BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED);
		$this->assertSame($expected, $res);
	}

	/**
	 * BitmaskedBehaviorTest::testFind()
	 *
	 * @return void
	 */
	public function testFind() {
		$res = $this->Comments->find('all')->toArray();
		$this->assertTrue(!empty($res) && is_array($res));

		$this->assertTrue(!empty($res[1]['statuses']) && is_array($res[1]['statuses']));
	}

	/**
	 * BitmaskedBehaviorTest::testSave()
	 *
	 * @return void
	 */
	public function testSave() {
		$data = array(
			'comment' => 'test save',
			'statuses' => array(),
		);

		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->validate($entity);
		$this->assertTrue($res);
		$this->assertSame('0', $entity->get('status'));

		$data = array(
			'comment' => 'test save',
			'statuses' => array(BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED),
		);
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->validate($entity);
		$this->assertTrue($res);

		$is = $entity->get('status');
		$this->assertSame(BitmaskedComment::STATUS_PUBLISHED | BitmaskedComment::STATUS_APPROVED, $is);

		// save + find
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$res = $this->Comments->find('first', array('conditions' => array('statuses' => $data['statuses'])));
		$this->assertTrue(!empty($res));
		$expected = BitmaskedComment::STATUS_APPROVED | BitmaskedComment::STATUS_PUBLISHED; // 6
		$this->assertEquals($expected, $res['status']);
		$expected = $data['statuses'];

		$this->assertEquals($expected, $res['statuses']);

		// model.field syntax
		$res = $this->Comments->find('first', array('conditions' => array('BitmaskedComments.statuses' => $data['statuses'])));
		$this->assertTrue((bool)$res->toArray());

		// explicit
		$activeApprovedAndPublished = BitmaskedComment::STATUS_ACTIVE | BitmaskedComment::STATUS_APPROVED | BitmaskedComment::STATUS_PUBLISHED;
		$data = array(
			'comment' => 'another post comment',
			'status' => $activeApprovedAndPublished,
		);
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);

		$res = $this->Comments->find('first', array('conditions' => array('status' => $activeApprovedAndPublished)));
		$this->assertTrue((bool)$res);
		$this->assertEquals($activeApprovedAndPublished, $res['status']);
		$expected = array(BitmaskedComment::STATUS_ACTIVE, BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED);

		$this->assertEquals($expected, $res['statuses']);
	}

	/**
	 * Assert that you can manually trigger "notEmpty" rule with null instead of 0 for "not null" db fields
	 */
	public function testSaveWithDefaultValue() {
		$data = array(
			'comment' => 'test save',
			'statuses' => array(),
		);
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->validate($entity);
		$this->assertTrue($res);
		$this->assertSame('0', $entity->get('status'));

		// Now let's set the default value
		$this->Comments->removeBehavior('Bitmasked');
		$this->Comments->addBehavior('Tools.Bitmasked', array('mappedField' => 'statuses', 'defaultValue' => ''));
		$data = array(
			'comment' => 'test save',
			'statuses' => array(),
		);
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->validate($entity);
		$this->assertFalse($res);

		$this->assertSame('', $entity->get('status'));
	}

	/**
	 * Assert that it also works with beforeSave event callback.
	 */
	public function testSaveOnBeforeSave() {
		$this->Comments->removeBehavior('Bitmasked');
		$this->Comments->addBehavior('Tools.Bitmasked', array('mappedField' => 'statuses', 'on' => 'beforeSave'));
		$data = array(
			'comment' => 'test save',
			'statuses' => array(BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_APPROVED),
		);
		$entity = $this->Comments->newEntity($data);
		$res = $this->Comments->save($entity);
		$this->assertTrue((bool)$res);
		$this->assertSame(BitmaskedComment::STATUS_PUBLISHED | BitmaskedComment::STATUS_APPROVED, $res['status']);
	}

	public function testIs() {
		$res = $this->Comments->isBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = array('BitmaskedComments.status' => 2);
		$this->assertEquals($expected, $res);
	}

	public function testIsNot() {
		$res = $this->Comments->isNotBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = array('NOT' => array('BitmaskedComments.status' => 2));
		$this->assertEquals($expected, $res);
	}

	public function testContains() {
		$this->skipIf(true, 'FIXME');

		$res = $this->Comments->containsBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = array('(BitmaskedComments.status & ? = ?)' => array(2, 2));
		$this->assertEquals($expected, $res);

		$conditions = $res;
		debug($conditions);
		$res = $this->Comments->find('all', array('conditions' => $conditions))->toArray();
		$this->assertTrue(!empty($res) && count($res) === 3);

		// multiple (AND)
		$res = $this->Comments->containsBit(array(BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_ACTIVE));

		$expected = array('(BitmaskedComments.status & ? = ?)' => array(3, 3));
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comments->find('all', array('conditions' => $conditions))->toArray();
		$this->assertTrue(!empty($res) && count($res) === 2);
	}

	public function testNotContains() {
		$this->skipIf(true, 'FIXME');

		$res = $this->Comments->containsNotBit(BitmaskedComment::STATUS_PUBLISHED);
		$expected = array('(BitmaskedComments.status & ? != ?)' => array(2, 2));
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comments->find('all', array('conditions' => $conditions))->toArray();
		$this->assertTrue(!empty($res) && count($res) === 4);

		// multiple (AND)
		$res = $this->Comments->containsNotBit(array(BitmaskedComment::STATUS_PUBLISHED, BitmaskedComment::STATUS_ACTIVE));

		$expected = array('(BitmaskedComments.status & ? != ?)' => array(3, 3));
		$this->assertEquals($expected, $res);

		$conditions = $res;
		$res = $this->Comments->find('all', array('conditions' => $conditions))->toArray();
		$this->assertTrue(!empty($res) && count($res) === 5);
	}

}
