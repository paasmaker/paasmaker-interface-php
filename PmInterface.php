<?php

namespace Paasmaker;

/**
 * An exception thrown if something goes wrong inside the interface.
 */
class PmInterfaceException extends Exception
{

}

/**
 * An interface between Paasmaker and a PHP application,
 * used to easily parse out the Paasmaker metadata and services,
 * and also provide the ability to read configuration files
 * to supply the values for development.
 */
class PmInterface
{
    /**
     * Create a new interface object. Supply an array
     * of override configuration file paths.
     *
     * @param array $overridePaths An array of override
     * paths to search for configuration files.
     * @param bool $yamlSupport If TRUE, allow loading
     * configuration via YAML files. This requires the
     * Symfony YAML component to be able to be loaded
     * via PSR-0 autoloading.
     * @throws InterfaceException when unable to load a
     * configuration file.
     */
    public function __construct(array $overridePaths, $yamlSupport = FALSE)
    {
        $this->_yamlSupport = $yamlSupport;
        $this->_overridePaths = $overridePaths;
        $this->_isOnPaasmaker = FALSE;
        $this->_variables = Array();
        $this->_services = Array();

        $this->_parseMetadata();
    }

    private function _parseMetadata()
    {
        $metadata = getenv("PM_METADATA");
        $services = getenv("PM_SERVICES");

        if($metadata !== FALSE && $services !== FALSE)
        {
            // We're running on Paasmaker.
            $this->_isOnPaasmaker = TRUE;
            $this->_variables = json_decode($metadata, TRUE);
            $this->_services = json_decode($services, TRUE);
        }
        else
        {
            // We're running locally or in development.
            // Read from the configuration file.
            $this->_loadConfigurationFile();
        }
    }

    private function _loadConfigurationFile()
    {
        foreach($this->_overridePaths as $path)
        {
            if(file_exists($path))
            {
                $contents = file_get_contents($path);

                if(substr($path, -4) == "json")
                {
                    // JSON format.
                    $parsed = json_decode($contents, TRUE);

                    if($parsed === FALSE)
                    {
                        throw new InterfaceException("Unable to parse json file " . $path);
                    }
                }
                else if($this->_yamlSupport && substr($path, -3) == "yml")
                {
                    // YAML format.
                    $yaml = new Symfony\Component\Yaml\Parser();
                    $parsed = $yaml->parse($contents);
                }

                $this->_storeConfiguration($parsed);
            }
        }

        throw new InterfaceException("Unable to find an override configuration to load.");
    }

    private function _storeConfiguration($filename, $data)
    {
        // Check for some sections and set blank ones if not found.
        if(FALSE === array_key_exists('services', $data))
        {
            $data['services'] = array();
        }
        if(FALSE === array_key_exists('workspace', $data))
        {
            $data['workspace'] = array();
        }
        if(FALSE === array_key_exists('node', $data))
        {
            $data['node'] = array();
        }

        // Check for required sections.
        if(FALSE === array_key_exists('application', $data))
        {
            throw new InterfaceException("You must supply an application section in your configuration.");
        }

        // Check for required keys.
        $requiredKeys = array('name', 'version', 'workspace', 'workspace_stub');
        foreach($requiredKeys as $key)
        {
            if(FALSE === array_key_exists($key, $data['application']))
            {
                throw new InterfaceException("Missing required key " . $key . " in application configuration.");
            }
        }

        // Store it all away.
        $this->_services = $data['services'];
        $this->_variables = $data;
    }

    /**
     * Fetch a named service from the configuration.
     *
     * @param string name The name of the service to fetch.
     * @return array The credentials for the named service.
     * @throws InterfaceException when the named service
     * does not exist.
     */
    public function getService($name)
    {
        if(array_key_exists($name, $this->_services))
        {
            return $this->_services[$name];
        }
        else
        {
            throw new InterfaceException("No such service " . $name);
        }
    }

    /**
     * Get all services available to this application.
     *
     * @return array An array of all services, keyed
     * by their name.
     */
    public function getAllServices()
    {
        return $this->_services;
    }

    /**
     * Helper function to unpack services into a format that
     * can be used by Symfony's configuration files.
     *
     * Once unpacked, you can then refer to values in your YAML
     * configuration files as so:
     *
     * user: %pm.<service>.<key>%
     *
     * For example, a MySQL service might look like this:
     * services:
     *    pdo:
     *      class: PDO
     *      arguments:
     *        dsn:      "mysql:dbname=%pm.mysql.database%;host=%pm.mysql.hostname%;port=%pm.mysql.port"
     *        user:     %pm.mysql.username%
     *        password: %pm.mysql.password%
     *
     * This works for any other services values.
     *
     * @return void
     */
    public function symfonyUnpack()
    {
        foreach($this->_services as $service => $credentials)
        {
            foreach($credentials as $key => $value)
            {
                $config = sprintf("SYMFONY__PM__%s__%s", strtoupper($service), strtoupper($key));
                $_SERVER[$config] = $value;
            }
        }
    }

    /**
     * Determine if the application is running on Paasmaker
     * or not.
     *
     * @return bool TRUE if the application is on Paasmaker,
     * or FALSE otherwise.
     */
    public function isOnPaasmaker()
    {
        return $this->_isOnPaasmaker;
    }

    /**
     * Get the application name.
     *
     * @return string The application name.
     */
    public function getApplicationName()
    {
        return $this->_variables['application']['name'];
    }

    /**
     * Get the application version.
     *
     * @return int The application version.
     */
    public function getApplicationVersion()
    {
        return $this->_variables['application']['version'];
    }

    /**
     * Get the workspace name.
     *
     * @return string The workspace pretty name.
     */
    public function getWorkspaceName()
    {
        return $this->_variables['application']['workspace'];
    }

    /**
     * Get the workspace stub.
     *
     * This is a URL friendly version of the workspace name,
     * supplied by the user who set up the workspace.
     *
     * @return string The workspace stub.
     */
    public function getWorkspaceStub()
    {
        return $this->_variables['application']['workspace_stub'];
    }

    /**
     * Get the node tags.
     *
     * @return array The node's tags.
     */
    public function getNodeTags()
    {
        return $this->_variables['node'];
    }

    /**
     * Get the workspace's tags.
     *
     * @return array The workspaces's tags.
     */
    public function getWorkspaceTags()
    {
        return $this->_variables['workspace'];
    }
}