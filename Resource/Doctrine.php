<?php

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

/**
 * Resource de Zend Framework 1, para integrar Doctrine.
 * "doctrine/orm": "^2.5"
 * @author ddniel16 <daniel@irontec.com>
 */

class Iron_Resource_Doctrine
    extends \Zend_Application_Resource_ResourceAbstract
{

    public function init()
    {
        return $this;
    }

    public function getConnet()
    {

        $options = $this->getOptions();

        $doctrineDir = $options['doctrine'];
        $models = $doctrineDir . '/src/*.php';

        foreach (glob($models) as $filename) {
            require $filename;
        }

        $isDevMode = (APPLICATION_ENV === 'production' ? true : false);
        $config = Setup::createAnnotationMetadataConfiguration(
            array($doctrineDir . '/src'),
            $isDevMode
        );

        $entityManager = \Doctrine\ORM\EntityManager::create($options, $config);

        return $entityManager;

    }

}