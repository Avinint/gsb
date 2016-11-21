<?php 

use Pimple\Container;

abstract class BaseApp
{
    protected static $instance;
    protected $db;
    protected $accessControl;
    protected $exceptionHandler;
    protected $environment;
    protected $container;

    public static function getInstance($environment = 'prod')
    {
        if(is_null(self::$instance))
        {
            self::$instance = new App($environment);
		}

        return self::$instance;
    }

    public function getContainer($service = '')
    {
        if (empty($service)) {
            return $this->container;
        }

        return $this->container[$service];
    }

    public function __construct($environment)
    {
        $this->container = new Container();
		$this->container['env'] = $environment;
        Core\Container\ContainerBuilder::init($this->container);
		$this->exceptionHandler = $this->getContainer('exception_handler');
    }

    public function getRouter()
    {
        return $this->getContainer('router');
    }

    public function getEnvironment()
    {
        return $this->getContainer('env');
    }

    public static function load()
    {
        session_start();
        require_once ROOT.'/Core/Autoloader.php';
        require_once ROOT.'/vendor/autoload.php';
        \Core\AutoLoader::register();
    }

    public function getTable($fullName = '')
    {
        $name = explode(':', $fullName);
        $module = array_shift($name);
        $name = array_pop($name);

        $className = 'App\\'.$module.'\\Table\\'.ucfirst($name).'Table';
        if (!class_exists($className)) {
            $className = 'Core\\Table\\Table';
        }

        return new $className($name);
    }

    public function getConfig()
    {
       return $this->getContainer('config');
    }

    public function getDb()
    {
        return $this->getContainer('db');
    }

    public function decamelize($string)
    {
        return strtolower(preg_replace('/(?|([a-z\d])([A-Z])|([^\^])([A-Z][a-z]))/', '$1_$2', $string));
    }
}