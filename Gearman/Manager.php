<?php
class Iron_Gearman_Manager
{
    private static $_gearmanServers = null;
    private static $_stdLogger = null;
    private static $_options = null;


    public static function setOptions($options)
    {
        self::$_options = $options;

    }

    /**
     * Retrieves the current Gearman Servers
     *
     * @return array
     */
    public static function getServers()
    {
        if (self::$_gearmanServers === null) {

            self::$_gearmanServers = implode(",", self::$_options['servers']);

        }

        return self::$_gearmanServers;
    }

    /**
     * Creates a GearmanClient instance and sets the job servers
     *
     * @return GearmanClient
     */
    public static function getClient()
    {
        $gmclient= new \GearmanClient();
        $servers = self::getServers();
        $gmclient->addServers($servers);
        if (isset(self::$_options['client']) &&
            isset(self::$_options['client']['timeout'])) {
            $gmclient->setTimeout(self::$_options['client']['timeout']);
        }

        return $gmclient;
    }

    /**
     * Creates a GearmanWorker instance
     *
     * @return GearmanWorker
     */
    public static function getWorker()
    {
        $worker = new GearmanWorker();
        $servers = self::getServers();

        $worker->addServers($servers);

        return $worker;
    }

    /**
     * Given a worker name, it checks if it can be loaded. If it's possible,
     * it creates and returns a new instance.
     *
     * @param string $workerName
     * @param string $logFile
     * @return Model_Gearman_Worker
     */
    public static function runWorker($workerName, $logFile = null)
    {
        $workerName .= 'Worker';

        $front = Zend_Controller_Front::getInstance();

        if (is_null($front->getRequest()) || !$front->getRequest()->getModuleName())  {
            $moduleDirectory = APPLICATION_PATH;
        } else {
            $moduleName = $front->getRequest()->getModuleName();
            $moduleDirectory = $front->getModuleDirectory($moduleName);
        }

        $workerFile = $moduleDirectory. '/workers/' . $workerName . '.php';

        if (!file_exists($workerFile)) {
            throw new InvalidArgumentException(
                    "El Worker no existe: {$workerFile}"
            );
        }

        require $workerFile;

        if (!class_exists($workerName)) {
            throw new InvalidArgumentException(
                    "La clase {$workerName} no existe en el archivo: {$workerFile}"
            );
        }

        return new $workerName($logFile);
    }




}
