# Doctrine

Prueba de concepto para la integración con **Doctrine**.

Instalación "doctrine/orm": "^2.5"

## application/configs/application.ini

````ini
; +----------+
; | Doctrine |
; +----------+
pluginpaths.Iron_Resource = APPLICATION_PATH "/../vendor/irontec/klear-library/Resource"

resources.Doctrine.doctrine = APPLICATION_PATH "/doctrine"
resources.Doctrine.driver   = "pdo_mysql"
resources.Doctrine.host     = "127.0.0.1"
resources.Doctrine.port     = "3306"
resources.Doctrine.dbname   = "table"
resources.Doctrine.user     = "root"
resources.Doctrine.password = "1234"
resources.Doctrine.charset  = "utf8"
````

## application/doctrine/cli-config.php

````php
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

require __DIR__ . "/../../vendor/autoload.php";

$isDevMode = true;
$config = Setup::createAnnotationMetadataConfiguration(
    array(__DIR__ . '/src'),
    $isDevMode
);

$conn = array(
    'driver'   => 'pdo_mysql',
    'host'     => '127.0.0.1',
    'port'     => '3306',
    'dbname'   => 'table',
    'user'     => 'root',
    'password' => '1234',
    'charset'  => 'utf8',
);

$entityManager = EntityManager::create($conn, $config);

return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($entityManager);
````

## application/Bootstrap.php

````php

    public function _initDoctrine()
    {

        $loader = $this->getPluginLoader();
        $loader->addPrefixPath('Iron_Resource_Doctrine', 'Doctrine');

    }

````

## IndexController.php

````php

    $bootstrap = $this->getFrontController()->getParam('bootstrap');
    $doctrine = $bootstrap->getPluginResource('Doctrine');

    $product = $doctrine->getConnet()->getRepository('Product');

    var_dump($product->findAll());

````