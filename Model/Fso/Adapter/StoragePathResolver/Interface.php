<?php
/***
 * File system object
*/
interface Iron_Model_Fso_Adapter_StoragePathResolver_Interface
{
    /**
     * @var instanceof ModelAbstract
     */
    public function setModel($model);

    public function setPrimaryKey($pk);

    /**
     * @var string fso.fileSpecs.basePath usually
     */
    public function setModelSpecs(array $modelSpecs) ; 

    /**
     * @param array $modifiers
     */
    public function setModifiers(array $modifiers);

    public function getPath();
}