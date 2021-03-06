<?php

namespace Core\Table;

use Core\Component\Database\QueryBuilder;
use \App;
use Core\Config;
use Core\Container\ContainerAware;
use Core\Entity\Entity;

class Table extends ContainerAware
{
	protected $table;
	protected $db;
    protected $entity;
    protected $count = 1;
    protected $changeset = array();

    public function __construct($entity = null)
    {
        parent::__construct();
        $this->db = $this->container['db'];
        if ($entity) {
            $this->entity = $entity;
            $entity = $this->getEntityClass();
            $this->table = $entity::dataMapper()->hasTable() ? $entity::dataMapper()->getTable(): $this->getDbTableNameFromEntity();
        } else {
            $this->guessDbTableName();
        }
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getDbTableNameFromEntity()
    {
        $class = explode(":", $this->entity);
        $class = end($class);

        return $this->container['tool']->decamelize($class);
    }


    public function guessDbTableName()
    {
        if (empty($this->table)) {

            $class = explode("\\", get_called_class());
            $class = end($class);
            $this->table =  $this->container['tool']->decamelize(str_replace('Table', '', $class));
        }
    }

    public function getEntityClass()
    {
        $class = explode(':', $this->entity);
        $module = array_shift($class);
        $class = array_shift($class);
        $class = 'App\\'.$module.'\\Entity\\'.$class;

        return $class;
    }

    public function getEntity()
    {
        $entity = $this->entity ?
        $this->getEntityClass() :
        preg_replace('/Table$/i', '', preg_replace('/Table/i', 'Entity', get_called_class(), 1), 1);

        return $entity;
    }

    public function setChanges($changes)
    {
        if ($this->changeset === array()) {
            $this->changeset = $changes;
        } else {
            $this->changeset = array_flip(array_flip(array_merge($this->changeset, $changes)));
        }
    }

    public function getChanges()
    {
        return $this->changeset;
    }

    public function trackChanges($entity, $clone)
    {
        $class = $this->getEntity();
        if(!$entity instanceof $class && !$clone instanceof $class) {
            throw new \Exception('method cannot compare instances of different classes');
        }
        $guestVars = array_filter(get_object_vars($clone), function($v) {return !is_array($v);});
        $hostVars = array_filter(get_object_vars($entity), function($v) {return !is_array($v);});

        return array_keys(array_diff_assoc($hostVars, $guestVars));
    }

    //Not Tested
    public function trackArrayChanges($entity, $clone)
    {
        $class = $this->getEntity();
        if(!$entity instanceof $class && !$clone instanceof $class) {
            throw new \Exception('method cannot compare instances of different classes');
        }
        $guestVars = array_filter(get_object_vars($clone), function($v) {return is_array($v);});
        $hostVars = array_filter(get_object_vars($entity), function($v) {return is_array($v);});

        $differences = array();
        foreach ($hostVars as $key => $var ) {
            if($this->arrayEqual($var, $guestVars[$key]) == true && $key !== 'changeset') {
                $differences[] = $key;
            }
        }

        return $differences;
    }

    //Not tested
    private function arrayEqual($a, $b)
    {
        return (
            is_array($a) && is_array($b) &&
            count($a) == count($b) &&
            array_diff($a, $b) === array_diff($b, $a)
        );
    }

    public function createQueryBuilder($alias = '', $table = '')
    {
        $query = new QueryBuilder($this);
        if($alias === null){

            $alias = strtolower($this->getTable()[0]); // Si alias vide on utilise la premiere lettre de la classe
        }

        //$query->addAlias($alias, $this->getTable());
        if($table === ''){
            $table = $this->getTable();
        }
        $query->select($alias)->from($table, $alias);
		
        return $query;
    }

    /* TODO remove used for debug */
    public function findEntity($id, $table)
    {
        $query = $this->createQueryBuilder($table[0], $table)
            ->where('id = :id')
            ->setParameter('id', $id)
            ->getQuery();

        return $query->getSingleResult();
    }

    public function find($id)
    {
        $query = $this
        ->createQueryBuilder($this->table[0])
        ->where('id = :id')
        ->setParameter('id', $id)
        ->limit(0, 1)
            ->getQuery()
        ;

        return $query->getSingleResult();
    }

    public function findOneBy(array $criteria)
    {
        $query = $this->loadCriteria($criteria, $orderBy = array(), $limit = null, $offset = null);

        return $query->getQuery()->getSingleResult();
    }

    public function findBy(array $criteria, array $orderBy = array(), $limit = null, $offset = null)
    {
        // TODO support for limits
        $query = $this->loadCriteria($criteria, $orderBy = array(), $limit = null, $offset = null);

            if ($orderBy) {
                $query->orderBy($orderBy[0], $orderBy[1])
            ;
        }

        return $query->getQuery()->getResults();
    }

    private function loadCriteria(array $criteria, array $orderBy = array(), $limit = null, $offset = null)
    {
        $query = $this ->createQueryBuilder('a');

        return $query->where(key($criteria).' = :'.key($criteria))
            ->setParameter(key($criteria), $criteria[key($criteria)]);
    }

    public function findAll(array $orderBy = null)
    {
        $query = $this->createQueryBuilder('a');
        if ($orderBy) {
            $query->orderBy(':sort', ':order')
            ->setParameter('sort', $orderBy[0])
            ->setParameter('sort', $orderBy[1])
            ;
        }

        return $query
            ->getQuery()
            ->getResults();
    }

   /**  Magic finder */
    public function __call($method, $arguments)
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
;        $fieldName = lcfirst($by);

        //if ($this->_class->hasField($fieldName) || $this->_class->hasAssociation($fieldName)) {
        switch (count($arguments)) {
            case 1:
                return $this->$method(array($fieldName => $arguments[0]));

            case 2:
                return $this->$method(array($fieldName => $arguments[0]), $arguments[1]);

            case 3:
                return $this->$method(array($fieldName => $arguments[0]), $arguments[1], $arguments[2]);

            case 4:
                return $this->$method(array($fieldName => $arguments[0]), $arguments[1], $arguments[2], $arguments[3]);

            default:
                // Do nothing
        }

        throw new \Exception($this->entity, $fieldName, $method.$by);
    }

    public function pluck($args)
    {
        $args = func_get_args();
        $orderBy = null;
        $key = null;
        foreach ($args as $index => &$arg) {
            if (is_array($arg) && isset($arg['orderBy'])) {
                $orderBy = $arg;

                $key = $index;
            }else {
                $arg = $this->table[0].'.'.$arg;
            }
        }
        unset($args[$key]);
        $query = $this->createQueryBuilder()
        ->select($args);

        if ($orderBy) {
            if (is_array($orderBy) && isset($orderBy['sort'])) {
                $query->orderBy($orderBy['orderBy'], $orderBy['sort']);
            } else if (is_array($orderBy)) {
                $query->orderBy($orderBy['orderBy']);
            } else {
                $query->orderBy($orderBy);
            }
        }

        return $query->getQuery()
            ->getScalarResults();
    }

    public function refresh($entity, $fields)
    {
        if(!$entity instanceof Entity){
            throw new \Exception("Database problem");
        }
        $vars = $entity->getVars();
        foreach($fields as $field => $value){
            if(array_key_exists($field, $vars)){
                if($vars[$field]!== $value){
                    $entity->$field = $value;
                }
            }
        }
        return $entity;
    }

    public function update($fields, $image = null, $table = '')
    {
        if ($table) {
            $table = $this->getPrefix().$table;
        } else {
            $table = $this->getPrefix().$this->getTable();
        }

         // on n'update que ce que le champs mis à jours
        /* if ($entity->getId()) {
            $preUpdateState = $fields = $entity->getId() ? $this->find($entity->getId())->getVars() : array();
            $fields = array_diff_assoc($fields, $preUpdateState);
			$imageBackup = method_exists($entity, 'getImage') ? $entity->getImage(): '';
        }*/

        if(empty($image) || $image['image']['name'] === ''){
            unset($image);
        }
        $entity = $this->getEntity();
        $fields = $entity::dataMapper()->beforePersist($fields);

        // TODO move image preupload to Save
        //$filePath = $table === 'article'?'':D_S.$table.'s';
        //$path = ROOT.D_S.'public'.D_S.'img'.$filePath;
        /*if (isset($image)) {
            $fields['image'] = $entity->preUpload($image['image']);
        }*/

        //$entity = $this->refresh($entity, $fields);

		$sql_parts = [];
		$attributes = [];

		foreach($fields as $k => $v){
			$sql_parts[] = "$k = ?";
			$attributes[] = "$v";
		}

        // trouver un moyen plus élégant de rajouter l'id pour le parametre
        $attributes[] = $fields['id'];
		$sql = implode(', ', $sql_parts);

        if ($this->query (
        'UPDATE '.$table.'
        SET '.$sql.'
        WHERE id = ?
        ', $attributes,true, true)) {
            if (isset($image)) {
                // TODO move image preupload to Save
               /*if ($uploaded = $entity->upload($image['image'], $fields['image'])) {
                   $entity->getId()? $entity->removeFile($imageBackup): NULL;
               } else {
                    $entity->getId()? $entity->setImage($imageBackup): NULL;
                   echo "Fichier non telecharge";
               }*/
            }

            return true;
        }

        return false;
    }

    public function create($fields, $image = null, $table = '')
    {
       $uOW = $this->getUnitOfWork();


        if($table === ''){
            $table = $this->getPrefix().$this->getTable();
        }else{
            $table = $this->getPrefix().$table;
        }

        if(empty($image) || $image['image']['name'] === ''){
            unset($image);
        }

        $entity = $this->getEntity();

        $fields = $entity::dataMapper()->beforePersist($fields);

        //$filePath = $table === 'article'?'':D_S.$table.'s';
        //$path = ROOT.D_S.'public'.D_S.'img'.$filePath;
        //var_dump($image);

        // TODO move image preupload to Save
        /*if (isset($image)) {
            $fields['image'] = $entity->preUpload($image['image']);
        }*/

        $sql_parts = [];
        $attributes = [];

        foreach($fields as $k => $v){
            $sql_parts[] = "$k = ?";
            $attributes[] = "$v";
        }

        $sql = implode(', ', $sql_parts);

        //var_dump($entity::dataMapper()->getColumnFromProperty($fields));

        if ($this->query(
            'INSERT INTO '.$table.'
            SET '.$sql,
            $attributes, true, true)) {
            if (isset($image)) {
                // TODO move image upload to Save
                /*  if ($uploaded = $entity->upload($image['image'], $fields['image'])) {

                }else{
                    echo "Fichier non telecharge";
                }*/
            }

            return true;
        }

        return false;
    }

    public function delete($id)
    {
        $entity = $this->find($id);
        // TODO move image removal to Save
       /* if(method_exists($entity,'getImage')){
            $image = $entity->getImage();
            $entity->removeFile($image);
        }*/

        return $this->query('DELETE FROM '.$this->table.' WHERE id = ?',
        array($id), true);
    }


    public function getUnitOfWork()
    {
        return $this->container['unit_of_work'];
    }

    public function query($statement, $attributes = null, $one = false, $scalar = false)
    {
        $class = $this->getEntity();
        $app = $this->container['app'];
        if ($attributes) {
            $data = $app->getDb()->prepare(
                        $statement,
                        $attributes,
                        $class,
                        $one
                    );
        } else {
            $data = $app->getDb()->query(
                        $statement,
                        $class,
                        $one
                    );
        }

        if ($scalar || $data === false) {
            return $data;
        } else {
            $meta = $class::dataMapper();
            $meta->setUnitOfWork($this->getUnitOfWork());

            if ($one) {
                $entity = $meta->hydrate($data, $class);

                return $entity;
            } else {
                $entities = $meta->hydrateAll($data, $class);

                return $entities;
            }
        }
	}

    public function lastInsertId()
    {
        return $this->db->lastInsertId();
    }

    public function getPrefix()
    {
        $config = Config::getInstance(ROOT.'/config/dbConfig.php', ROOT.'/config/config.php', ROOT.'/config/security.php');
        return $config->get('db_prefix');
    }
}