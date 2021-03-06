<?php

namespace Core\Entity;

use Core\Component\Database\DataMapper;
use Core\Component\Database\Metadata;

class Entity
{
    protected static $mapper;

	public function __get($key)
	{
		$method = 'get'.ucfirst($key);
		if(method_exists($this, $method)){
			$this->$key = $this->$method();
        }

        return $this->$key;
    }

    public function setId($id)
    {
        if (property_exists(get_called_class(), 'id') && !$this->id) {
            $this->id = $id;
        }
    }

    public static function getShortClass()
    {
        $class = new \ReflectionClass(get_called_class());
        return $class->getShortName();
    }

    public function getTableName()
    {
        if ($this->getDataMapper() && $this->getDataMapper()->hasTable()) {
            return $this->getDataMapper()->getTable();
        }

        $class = explode("\\", get_called_class());
        
        return strtolower(static::getShortClass());
    }

	public static function getTable()
    {
        $class = new \ReflectionClass(get_called_class());
        $repo = 'App'.D_S.static::getModule().D_S.'Table'.D_S.static::getShortClass().'Table';

        return new $repo() ;
    }

    private static function getModule()
    {
        $class = explode("\\", get_called_class());
        array_shift($class);
        $module = array_shift($class);

        return $module;
    }

    public function getClass()
    {
        return get_called_class();
    }

    // retourne le chemin jusqu'au répertoire img ou un
    public function getFilePath()
    {
        return  ROOT.D_S.'public'.D_S.'img'.D_S;
    }

    public function getFilePathFromClass()
    {
        return  ROOT.D_S.'public'.D_S.'img'.D_S.$this->getClassName().'s';
    }

    public function preUpload($file)
    {
        $pos_extension = strpos($file['name'], '.');
        $extension = substr($file['name'], $pos_extension);
        $newName = uniqid().$extension;

        return $newName;
    }

	public function upload($file, $name, $dir = null)
	{
        $dir = $dir? $dir: $this->getFilePath();
        if(!is_dir($dir)){
            if(!mkdir($dir, '0777', true )){
                throw new \Exception('repertoire non cree');
            }
		}
	    $name = $dir.D_S.$name;
        if( $file['file']['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
            $moved = move_uploaded_file($file['tmp_name'], $name);
            if ($moved) {
                return $name;
            } // TODO else throw exception
        }

        return false;
    }

    public function removeFile($filename, $dir = null)
    {
        $dir = $dir? $dir: $this->getFilePath();
        if(file_exists($dir.D_S.$filename)){
           return unlink($dir.D_S.$filename);
        }
        return false;
    }

    public function getVars()
    {
        $vars = get_object_vars($this);

        // on remplace les associations par leurs id (foreign key)
        foreach($vars as $name => &$value) {
            if($value instanceof self) {
                if(method_exists($value, 'getId'))
                $value = $value->getId();
            }
        }

        return $vars;
    }


    public static function setDataMapper()
    {
       return new DataMapper(ROOT.D_S.'App'.D_S.static::getModule().D_S.'config'.D_S.'dataMapper'.D_S.static::getShortClass().'Mapper.php');
    }

	public static function dataMapper($field = null)
    {
            static::$mapper = static::setDataMapper();


        return static::$mapper;
    }

    public static function hasDataMapper()
    {
        return file_exists(ROOT.D_S.'App'.D_S.static::getModule().D_S.'config'.D_S.'dataMapper'.D_S.static::getShortClass().'Mapper.php');
    }

    public function getDataMapper()
    {
        return static::dataMapper();
    }

    /* Wrapper methods for NOOB mode aka Ruby mode */
    public static function all()
    {
        return static::getTable()->findAll();
    }

    public static function find($id)
    {
        return static::getTable()->find($id);
    }

    public static function findBy($criteria)
    {
        return static::getTable()->findBy($criteria);
    }

    public static function findOneBy($criteria)
    {
        return static::getTable()->findOneBy($criteria);
    }

    public  static function __callstatic($method, $arguments)
    {
        switch (true) {
            case (0 === strpos($method, 'findBy')):
                $by = substr($method, 6);
                $method = 'findBy';
                break;

            case (0 === strpos($method, 'findOneBy')):
                $by = substr($method, 9);
                $method = 'findOneBy';
                break;

            default:
                throw new \Exception(
                    "Undefined method '$method'. The method name must start with ".
                    "either findBy or findOneBy!"
                );
        }

        if (empty($arguments)) {
            $arguments = array();
        }
        $fieldName = lcfirst($by);

        //if ($this->_class->hasField($fieldName) || $this->_class->hasAssociation($fieldName)) {
        switch (count($arguments)) {
            case 1:
                return static::getTable()->$method(array($fieldName => $arguments[0]));

            case 2:
                return static::getTable()->$method(array($fieldName => $arguments[0]), $arguments[1]);

            case 3:
                return static::getTable()->$method(array($fieldName => $arguments[0]), $arguments[1], $arguments[2]);

            case 4:
                return static::getTable()->$method(array($fieldName => $arguments[0]), $arguments[1], $arguments[2], $arguments[3]);

            default:
                // Do nothing
        }

        throw new \Exception(static::getTable()->getEntity(), $fieldName, $method.$by);
    }

    public function __toString()
    {
        return strtolower(static::getShortClass());
    }
}