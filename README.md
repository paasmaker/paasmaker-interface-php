Paasmaker Interface Library
===========================

Installation and Use
--------------------

If you're using composer, you can just add "paasmaker/interface" to your
project dependencies, and then update as normal.

If you're not using composer, you can just grab the single file - PmInterface.php
- and drop that into your project. If you add it to your autoloader, you're all
good to go.

By default, it will not attempt to parse Yaml files. You will need the Symfony
YAML component to parse YAML files - if you're working with Symfony you'll already
have it and can use YAML overrides. Otherwise, it will fall back to the always supported
JSON format.

Once you're set up, you can do the following in your startup code:

	$interface = \Paasmaker\PmInterface(array('../my-project.json'));
	// Or to allow loading YAML files:
	$interface = \Paasmaker\PmInterface(array('../my-project.yml'), TRUE);

	// This returns true if running on Paasmaker.
	$interface.isOnPaasmaker();

	// Throws \Paasmaker\PmInterfaceException if no such service exists.
	$service = $interface->getService('named-service');

	// $service now contains an array of parameters. Typically this will
	// have the keys 'hostname', 'username', 'password', etc. Use this to
	// connect to the relevant services.

	// Get other application metadata.
	$application = $interface->getApplicationName();

Symfony2
--------

To make it even easier to use with Symfony2, the interface can unpack the services
into variables that you can use directly in the YAML configuration files. To make this
happen, follow these steps:

Add the interface to your composer.json file for your project, and then install.

	# composer.phar require paasmaker/interface
	# composer.phar install

Now edit app/AppKernel.php, and in the construct method, add the following lines:

	$interface = \Paasmaker\PmInterface(array('../my-project.yml'), TRUE);
	$interface->symfonyUnpack();

Then, in your YML files, you can refer to the services values by inserting
values in the format "%pm.<service name>.<service value>". For example,
your database setup might look as follows:

	TODO: Complete this.

Testing
-------

To run the unit tests, install the dev dependencies and then
invoke PHPUnit.

	# composer.phar install --dev
	# vendor/bin/phpunit
