<?php

/*
  +------------------------------------------------------------------------+
  | Phalcon Framework                                                      |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2012 Phalcon Team (http://www.phalconphp.com)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file docs/LICENSE.txt.                        |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  |          Rack Lin <racklin@gmail.com>                                  |
  +------------------------------------------------------------------------+
*/

class DbTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @medium
	 */
	public function testDbMysql()
	{
		require 'unit-tests/config.db.php';

		if (!empty($configMysql)) {
			$connection = new Phalcon\Db\Adapter\Pdo\Mysql($configMysql);
			$this->_executeTests($connection);
		}
		else {
			$this->markTestSkipped("Skipped");
		}
	}

	/**
	 * @medium
	 */
	public function testDbPostgresql()
	{
		require 'unit-tests/config.db.php';

		if (!empty($configPostgresql)) {
			$connection = new Phalcon\Db\Adapter\Pdo\Postgresql($configPostgresql);
			$this->_executeTests($connection);
		}
		else {
			$this->markTestSkipped("Skipped");
		}
	}

	/**
	 * @medium
	 */
	public function testDbPostgresqlSchemas()
	{
		require 'unit-tests/config.db.php';
		if (empty($configPostgresql)) {
			$this->markTestSkipped("Skipped");
			return;
		}

		$configPostgresqlDefault = array_merge(array(), $configPostgresql);
		unset($configPostgresqlDefault['schema']);

		$configPostgresqlNonExists = array_merge(array(), $configPostgresql);
		$configPostgresqlNonExists['schema'] = 'nonexists';

		try {
			$connection = new Phalcon\Db\Adapter\Pdo\Postgresql($configPostgresql);
			$this->assertTrue(is_object($connection));
		} catch(Exception $e) {
			$this->assertTrue(false);
		}

		try {
			$connection = new Phalcon\Db\Adapter\Pdo\Postgresql($configPostgresqlDefault);
			$this->assertTrue(is_object($connection));
		} catch(Exception $e) {
			$this->assertTrue(false);
		}

		try {
			$connection = new Phalcon\Db\Adapter\Pdo\Postgresql($configPostgresqlNonExists);
			$this->assertFalse(is_object($connection));
		} catch(Exception $e) {
			$this->assertTrue(true);
		}

	}

	/**
	 * @medium
	 */
	public function testDbSqlite()
	{
		require 'unit-tests/config.db.php';

		if (!empty($configSqlite)) {
			$connection = new Phalcon\Db\Adapter\Pdo\Sqlite($configSqlite);
			$this->_executeTests($connection);
		}
		else {
			$this->markTestSkipped("Skipped");
		}

	}

	protected function _executeTests($connection)
	{

		$result = $connection->query("SELECT * FROM personas LIMIT 3");
		$this->assertTrue(is_object($result));
		$this->assertEquals(get_class($result), 'Phalcon\Db\Result\Pdo');

		for ($i = 0; $i < 3; $i++) {
			$row = $result->fetch();
			$this->assertEquals(count($row), 22);
		}

		$row = $result->fetch();
		$this->assertEquals($row, false);
		$this->assertEquals($result->numRows(), 3);

		$number = 0;
		$result = $connection->query("SELECT * FROM personas LIMIT 5");
		$this->assertTrue(is_object($result));

		while ($row = $result->fetch()) {
			$number++;
		}
		$this->assertEquals($number, 5);

		$result = $connection->query("SELECT * FROM personas LIMIT 5");
		$result->setFetchMode(Phalcon\Db::FETCH_NUM);
		$row = $result->fetch();
		$this->assertTrue(is_array($row));
		$this->assertEquals(count($row), 11);
		$this->assertTrue(isset($row[0]));
		$this->assertFalse(isset($row['cedula']));
		$this->assertFalse(isset($row->cedula));

		$result = $connection->query("SELECT * FROM personas LIMIT 5");
		$result->setFetchMode(Phalcon\Db::FETCH_ASSOC);
		$row = $result->fetch();
		$this->assertTrue(is_array($row));
		$this->assertEquals(count($row), 11);
		$this->assertFalse(isset($row[0]));
		$this->assertTrue(isset($row['cedula']));
		$this->assertFalse(isset($row->cedula));

		$result = $connection->query("SELECT * FROM personas LIMIT 5");
		$result->setFetchMode(Phalcon\Db::FETCH_OBJ);
		$row = $result->fetch();
		$this->assertTrue(is_object($row));
		$this->assertTrue(isset($row->cedula));

		$result = $connection->query("SELECT * FROM personas LIMIT 5");
		$result->setFetchMode(Phalcon\Db::FETCH_BOTH);
		$result->dataSeek(4);
		$row = $result->fetch();
		$row = $result->fetch();
		$this->assertEquals($row, false);

		$result = $connection->execute("DELETE FROM prueba");
		$this->assertTrue($result);

		$success = $connection->execute('INSERT INTO prueba(id, nombre, estado) VALUES ('.$connection->getDefaultIdValue().', ?, ?)', array("LOL 1", "A"));
		$this->assertTrue($success);

		$success = $connection->execute('UPDATE prueba SET nombre = ?, estado = ?', array("LOL 11", "R"));
		$this->assertTrue($success);

		$success = $connection->execute('DELETE FROM prueba WHERE estado = ?', array("R"));
		$this->assertTrue($success);

		$success = $connection->insert('prueba', array($connection->getDefaultIdValue(), "LOL 1", "A"));
		$this->assertTrue($success);

		$success = $connection->insert('prueba', array("LOL 2", "E"), array('nombre', 'estado'));
		$this->assertTrue($success);

		$success = $connection->insert('prueba', array("LOL 3", "I"), array('nombre', 'estado'));
		$this->assertTrue($success);

		$success = $connection->insert('prueba', array(new Phalcon\Db\RawValue('current_date'), "A"), array('nombre', 'estado'));
		$this->assertTrue($success);

		for ($i=0; $i<50; $i++) {
			$success = $connection->insert('prueba', array("LOL ".$i, "F"), array('nombre', 'estado'));
			$this->assertTrue($success);
		}

		$success = $connection->update('prueba', array("nombre", "estado"), array("LOL 1000", "X"), "estado='E'");
		$this->assertTrue($success);

		$success = $connection->update('prueba', array("nombre"), array("LOL 3000"), "estado='X'");
		$this->assertTrue($success);

		$success = $connection->update('prueba', array("nombre"), array(new Phalcon\Db\RawValue('current_date')), "estado='X'");
		$this->assertTrue($success);

		$connection->delete("prueba", "estado='X'");
		$this->assertTrue($success);

		$connection->delete("prueba");
		$this->assertTrue($success);
		$this->assertEquals($connection->affectedRows(), 53);

		$row = $connection->fetchOne("SELECT * FROM personas");
		$this->assertEquals(count($row), 22);

		$row = $connection->fetchOne("SELECT * FROM personas", Phalcon\Db::FETCH_NUM);
		$this->assertEquals(count($row), 11);

		$rows = $connection->fetchAll("SELECT * FROM personas LIMIT 10");
		$this->assertEquals(count($rows), 10);

		$rows = $connection->fetchAll("SELECT * FROM personas LIMIT 10", Phalcon\Db::FETCH_NUM);
		$this->assertEquals(count($rows), 10);
		$this->assertEquals(count($rows[0]), 11);

		//Auto-Increment/Serial Columns
		$sql = 'INSERT INTO subscriptores(id, email, created_at, status) VALUES ('.$connection->getDefaultIdValue().', ?, ?, ?)';
		$success = $connection->execute($sql, array('shirley@garbage.com', "2011-01-01 12:59:13", "P"));
		$this->assertTrue($success);

		//Check for auto-increment column
		$this->assertTrue($connection->lastInsertId('subscriptores_id_seq') > 0);

		// Create View
		$success = $connection->createView('phalcon_test_view', array('sql' => 'SELECT 1 AS one, 2 AS two, 3 AS three'));
		$this->assertTrue($success);

		//Check view exists
		$success = $connection->viewExists('phalcon_test_view');
		$this->assertTrue((bool) $success);

		//Gets the list of all views.
		$views = $connection->listViews();
		$this->assertTrue(is_array($views));
		$this->assertTrue(in_array('phalcon_test_view', $views));

		//Execute created view
		$row = $connection->fetchOne("SELECT * FROM phalcon_test_view");
		$this->assertEquals(count($row), 6);
		$this->assertTrue(array_key_exists('one', $row));
		$this->assertEquals($row['two'], 2);

		//Drop view
		$success = $connection->dropView('phalcon_test_view');
		$this->assertTrue($success);

		//Transactions without savepoints.
		$connection->setNestedTransactionsWithSavepoints(false);

		$success = $connection->begin(); // level 1 - real
		$this->assertTrue($success);

		$success = $connection->begin(); // level 2 - virtual
		$this->assertFalse($success);

		$success = $connection->begin(); // level 3 - virtual
		$this->assertFalse($success);

		$success = $connection->rollback(); // level 2 - virtual
		$this->assertFalse($success);

		$success = $connection->commit(); // level 1 - virtual
		$this->assertFalse($success);

		$success = $connection->commit(); // commit - real
		$this->assertTrue($success);

		$success = $connection->begin(); // level 1 - real
		$this->assertTrue($success);

		$success = $connection->begin(); // level 2 - virtual
		$this->assertFalse($success);

		$success = $connection->commit(); // level 1 - virtual
		$this->assertFalse($success);

		$success = $connection->rollback(); // rollback - real
		$this->assertTrue($success);

		//Transactions with savepoints.
		$connection->setNestedTransactionsWithSavepoints(true);

		$success = $connection->begin(); // level 1 - begin transaction
		$this->assertTrue($success);

		$success = $connection->begin(); // level 2 - uses savepoint_1
		$this->assertTrue($success);

		$success = $connection->begin(); // level 3 - uses savepoint_2
		$this->assertTrue($success);

		$success = $connection->rollback(); // level 2 - uses rollback savepoint_2
		$this->assertTrue($success);

		$success = $connection->commit(); // level 1  - uses release savepoint_1
		$this->assertTrue($success);

		$success = $connection->commit(); // commit - real commit
		$this->assertTrue($success);

		$success = $connection->begin(); // level 1 - real begin transaction
		$this->assertTrue($success);

		$success = $connection->begin(); // level 2 - uses savepoint_1
		$this->assertTrue($success);

		$success = $connection->commit(); // level 1 - uses release savepoint_1
		$this->assertTrue($success);

		$success = $connection->rollback(); // rollback - real rollback
		$this->assertTrue($success);
	}

}
