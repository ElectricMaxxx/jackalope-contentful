<?php

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Connection;

/**
 * Implementation loader for jackalope-doctrine-dbal
 */
class ImplementationLoader extends \PHPCR\Test\AbstractLoader
{
    private static $instance = null;

    public static function getInstance()
    {
        if (null === self::$instance) {
            global $dbConn;
            $fixturePath = realpath(__DIR__ . '/fixtures/doctrine');
            self::$instance = new ImplementationLoader($dbConn, $fixturePath);
        }

        return self::$instance;
    }

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $fixturePath;

    protected function __construct(Connection $connection, $fixturePath)
    {
        parent::__construct('Jackalope\RepositoryFactoryDoctrineDBAL', $GLOBALS['phpcr.workspace']);

        $this->connection   = $connection;
        $this->fixturePath  = $fixturePath;

        $this->unsupportedChapters = array(
            'Connecting',
            'Reading',
            'Query',
            'Export',
            'NodeTypeDiscovery',
            'PermissionsAndCapabilities',
            'Writing',
            'Import',
            'Observation',
            'WorkspaceManagement',
            'AccessControlManagement',
            'ShareableNodes',
            'Versioning',
            'AccessControlManagement',
            'Locking',
            'LifecycleManagement',
            'NodeTypeManagement',
            'RetentionAndHold',
            'Transactions',
            'SameNameSiblings',
            'OrderableChildNodes',
        );

        $this->unsupportedCases = array();

        $this->unsupportedTests = array();

        if ($connection->getDatabasePlatform() instanceof Doctrine\DBAL\Platforms\SqlitePlatform) {
            $this->unsupportedTests[] = 'Query\\QuerySql2OperationsTest::testQueryRightJoin';
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getRepositoryFactoryParameters()
    {
        if (empty($GLOBALS['data_caches'])) {
            $caches = null;
        } else {
            $caches = array();
            foreach (explode(',', $GLOBALS['data_caches']) as $key) {
                $caches[$key] = new ArrayCache();
            }
        }

        return array(
            'jackalope.doctrine_dbal_connection' => $this->connection,
            'jackalope.data_caches' => $caches,
            \Jackalope\Session::OPTION_AUTO_LASTMODIFIED => false,
            'jackalope.logger' => new \Jackalope\Transport\Logging\Psr3Logger(new \Psr\Log\NullLogger()),
        );
    }

    public function getSessionWithLastModified()
    {
        /** @var $session \Jackalope\Session */
        $session = $this->getSession();
        $session->setSessionOption(\Jackalope\Session::OPTION_AUTO_LASTMODIFIED, true);

        return $session;
    }

    public function getCredentials()
    {
        return new \PHPCR\SimpleCredentials($GLOBALS['phpcr.user'], $GLOBALS['phpcr.pass']);
    }

    public function getInvalidCredentials()
    {
        return new \PHPCR\SimpleCredentials('nonexistinguser', '');
    }

    public function getRestrictedCredentials()
    {
        return new \PHPCR\SimpleCredentials('anonymous', 'abc');
    }

    /**
     * Doctrine dbal supports anonymous login
     *
     * @return bool true
     */
    public function prepareAnonymousLogin()
    {
        return true;
    }

    public function getUserId()
    {
        return $GLOBALS['phpcr.user'];
    }

    public function getRepository()
    {
        $transport = new \Jackalope\Transport\DoctrineDBAL\Client(new \Jackalope\Factory, $this->connection);
        foreach (array($GLOBALS['phpcr.workspace'], $this->otherWorkspacename) as $workspace) {
            try {
                $transport->createWorkspace($workspace);
            } catch (\PHPCR\RepositoryException $e) {
                if ($e->getMessage() != "Workspace '$workspace' already exists") {
                    // if the message is not that the workspace already exists, something went really wrong
                    throw $e;
                }
            }
        }

        return new \Jackalope\Repository(null, $transport, $this->getRepositoryFactoryParameters());
    }

    public function getFixtureLoader()
    {
        $testerClass = '\\Jackalope\\Test\\Tester\\' . ucfirst(strtolower($this->connection->getWrappedConnection()->getAttribute(PDO::ATTR_DRIVER_NAME)));
        if (!class_exists($testerClass)) {
            // load Generic Tester if no database specific Tester class found
            $testerClass = '\\Jackalope\\Test\\Tester\\Generic';
        }

        return new $testerClass(
            new \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($this->connection->getWrappedConnection(), "tests"),
            $this->fixturePath
        );
    }

}
