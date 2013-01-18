<?php

namespace Paasmaker\Tests;

class PmInterfaceTest extends \PHPUnit_Framework_TestCase
{
	protected function tearDown()
	{
		// Clear environment variables, if set by the test.
		putenv("PM_METADATA");
		putenv("PM_SERVICES");

		parent::tearDown();
	}

	protected function _getFixturePath($file)
	{
		return __DIR__ . '/Fixtures/' . $file;
	}

	public function testSimple()
	{
		// Give it no configuration paths.
		try
		{
			$interface = new \Paasmaker\PmInterface(array());

			$this->assertTrue(FALSE, "Should have thrown exception.");
		}
		catch(\Paasmaker\PmInterfaceException $ex)
		{
			$this->assertTrue(TRUE, "Threw exception correctly.");
		}

		// Give it a configuration path that does not exist.
		try
		{
			$interface = new \Paasmaker\PmInterface(array($this->_getFixturePath('noexist.yml')), TRUE);

			$this->assertTrue(FALSE, "Should have thrown exception.");
		}
		catch(\Paasmaker\PmInterfaceException $ex)
		{
			$this->assertTrue(TRUE, "Threw exception correctly.");
		}

		// Now give it two paths, one that does exist.
		$interface = new \Paasmaker\PmInterface(
			array(
				$this->_getFixturePath('noexist.yml'),
				$this->_getFixturePath('test.yml')
			),
			TRUE
		);

		$this->assertEquals($interface->getPort(), 9002);
	}

	public function testJsonConfig()
	{
		$interface = new \Paasmaker\PmInterface(array($this->_getFixturePath('test.json')));
		$this->_confirmTestConfiguration($interface);
	}

	public function testYamlConfig()
	{
		$interface = new \Paasmaker\PmInterface(array($this->_getFixturePath('test.yml')), TRUE);
		$this->_confirmTestConfiguration($interface);
	}

	public function testInvalidConfig()
	{
		try
		{
			$interface = new \Paasmaker\PmInterface(array($this->_getFixturePath('invalid.yml')), TRUE);
			$this->assertTrue(FALSE, "Should have thrown exception.");
		}
		catch(\Paasmaker\PmInterfaceException $ex)
		{
			$this->assertTrue(TRUE, "Threw exception correctly.");
		}

		try
		{
			$interface = new \Paasmaker\PmInterface(array($this->_getFixturePath('invalid2.yml')), TRUE);
			$this->assertTrue(FALSE, "Should have thrown exception.");
		}
		catch(\Paasmaker\PmInterfaceException $ex)
		{
			$this->assertTrue(TRUE, "Threw exception correctly.");
		}
	}

	public function testTags()
	{
		$interface = new \Paasmaker\PmInterface(array($this->_getFixturePath('tags.yml')), TRUE);

		$workspaceTags = $interface->getWorkspaceTags();
		$nodeTags = $interface->getNodeTags();

		$this->assertTrue(isset($workspaceTags['tag']));
		$this->assertTrue(isset($nodeTags['tag']));
	}

	public function testPaasmakerConfig()
	{
		// Generate and insert the configuration into the environment.
		$servicesRaw = array(
			'variables' => array(
				'one' => 'two'
			)
		);

		$metadataRaw = array(
			'application' => array(
				'name' => 'test',
				'version' => 1,
				'workspace' => 'Test',
				'workspace_stub' => 'test',
			),
			'node' => array(
				'one' => 'two',
			),
			'workspace' => array(
				'three' => 'four',
			)
		);

		putenv('PM_SERVICES=' . json_encode($servicesRaw));
		putenv('PM_METADATA=' . json_encode($metadataRaw));
		putenv('PM_PORT=42600');

		$interface = new \Paasmaker\PmInterface(array());

		$this->assertTrue($interface->isOnPaasmaker());
		$this->assertEquals($interface->getApplicationName(), "test");
		$this->assertEquals($interface->getApplicationVersion(), 1);
		$this->assertEquals($interface->getWorkspaceName(), "Test");
		$this->assertEquals($interface->getWorkspaceStub(), "test");

		$workspaceTags = $interface->getWorkspaceTags();
		$nodeTags = $interface->getNodeTags();

		$this->assertTrue(isset($workspaceTags['three']));
		$this->assertTrue(isset($nodeTags['one']));

		$service = $interface->getService('variables');
		$this->assertTrue(isset($service['one']));

		try
		{
			$interface->getService('no-service');
			$this->assertTrue(FALSE, "Should have thrown exception.");
		}
		catch(\Paasmaker\PmInterfaceException $ex)
		{
			$this->assertTrue(TRUE, "Threw exception correctly.");
		}

		$this->assertEquals($interface->getPort(), 42600);
	}

	protected function _confirmTestConfiguration($interface)
	{
		$this->assertFalse($interface->isOnPaasmaker());
		$this->assertEquals($interface->getApplicationName(), "test");
		$this->assertEquals($interface->getApplicationVersion(), 1);
		$this->assertEquals($interface->getWorkspaceName(), "Test");
		$this->assertEquals($interface->getWorkspaceStub(), "test");
		$this->assertEquals(count($interface->getWorkspaceTags()), 0);
		$this->assertEquals(count($interface->getNodeTags()), 0);
		$this->assertEquals(count($interface->getAllServices()), 1);

		$service = $interface->getService('parameters');
		$this->assertTrue(isset($service['foo']));

		try
		{
			$interface->getService('no-service');
			$this->assertTrue(FALSE, "Should have thrown exception.");
		}
		catch(\Paasmaker\PmInterfaceException $ex)
		{
			$this->assertTrue(TRUE, "Threw exception correctly.");
		}
	}
}
