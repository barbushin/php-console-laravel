<?php namespace PhpConsole\Laravel;

use Config;
use PhpConsole\Handler;
use PhpConsole\Connector;
use PhpConsole\Helper;

class ServiceProvider extends \Illuminate\Support\ServiceProvider {

    const PACKAGE_ALIAS = 'php-console';

    /** @var bool Is PHP Console server enabled */
    protected $isEnabled = true;
    /** @var string Path to PhpConsole classes directory */
    protected $phpConsolePathAlias = 'application.vendors.PhpConsole.src.PhpConsole';
    /** @var string Base path of all project sources to strip in errors source paths */
    protected $sourcesBasePath;
    /** @var bool Register PhpConsole\Helper that allows short debug calls like PC::debug($var, 'ta.g.s') */
    protected $registerHelper = true;

    /** @var string|null Server internal encoding */
    protected $serverEncoding;
    /** @var int|null Set headers size limit for your web-server. You can detect headers size limit by /PhpConsole/examples/utils/detect_headers_limit.php */
    protected $headersLimit;
    /** @var string|null Protect PHP Console connection by password */
    protected $password;
    /** @var bool Force connection by SSL for clients with PHP Console installed */
    protected $enableSslOnlyMode = false;
    /** @var array Set IP masks of clients that will be allowed to connect to PHP Console lie: array('192.168.*.*', '10.2.12*.*', '127.0.0.1') */
    protected $ipMasks = array();

    /** @var bool Enable errors handling */
    protected $handleErrors = true;
    /** @var bool Enable exceptions handling */
    protected $handleExceptions = true;

    /** @var int Maximum dumped vars array or object nested dump level */
    protected $dumperLevelLimit = 5;
    /** @var int Maximum dumped var same level array items or object properties number */
    protected $dumperItemsCountLimit = 100;
    /** @var int Maximum length of any string or dumped array item */
    protected $dumperItemSizeLimit = 50000;
    /** @var int Maximum approximate size of dumped vars result formatted in JSON */
    protected $dumperDumpSizeLimit = 500000;
    /** @var bool Convert callback items in dumper vars to (callback SomeClass::someMethod) strings */
    protected $dumperDetectCallbacks = true;
    /** @var bool Autodetect and append trace data to debug */
    protected $detectDumpTraceAndSource = false;
    /** @var \PhpConsole\Storage|null Postponed response storage */
    protected $dataStorage = false;

    /**
     * @var bool Enable eval request to be handled by eval dispatcher. Must be called after all Connector configurations.
     * $this->password is required to be set
     * use $this->ipMasks & $this->enableSslOnlyMode for additional protection
     */
    protected $isEvalEnabled = false;

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {
        $this->mergeConfigFrom(__DIR__ . '/../../config/phpconsole.php', 'phpconsole');
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot() {
        $this->publishes([__DIR__ . '/../../config/phpconsole.php' => config_path('phpconsole.php')], 'config');

        foreach (config('phpconsole') as $option => $value) {
            $this->setOption($option, $value);
        }
        $this->initPhpConsole();
    }

    protected function setOption($name, $value) {
        if (!property_exists($this, $name)) {
            throw new \Exception('Unknown property "' . $name . '" in ' . static::PACKAGE_ALIAS . ' package config');
        }
        $this->$name = $value;
    }

    protected function initPhpConsole() {
        if (!$this->dataStorage) {
            $this->dataStorage = new PhpConsole\Storage\File(storage_path('php-console.dat'), true);
        }
        if ($this->dataStorage instanceof \PhpConsole\Storage\Session) {
            throw new \Exception('Unable to use PhpConsole\Storage\Session as PhpConsole storage interface because of problems with overridden $_SESSION handler in Laravel');
        }
        Connector::setPostponeStorage($this->dataStorage);

        $connector = Connector::getInstance();

        if ($this->registerHelper) {
            Helper::register();
        }

        $isActiveClient = $connector->isActiveClient();
        if (!$this->isEnabled || !$isActiveClient) {
            if($isActiveClient) {
                $connector->disable();
            }
            return;
        }

        $handler = Handler::getInstance();
        $handler->setHandleErrors($this->handleErrors);
        $handler->setHandleErrors($this->handleExceptions);
        $handler->start();

        if ($this->sourcesBasePath) {
            $connector->setSourcesBasePath($this->sourcesBasePath);
        }
        if ($this->serverEncoding) {
            $connector->setServerEncoding($this->serverEncoding);
        }
        if ($this->password) {
            $connector->setPassword($this->password);
        }
        if ($this->enableSslOnlyMode) {
            $connector->enableSslOnlyMode();
        }
        if ($this->ipMasks) {
            $connector->setAllowedIpMasks($this->ipMasks);
        }
        if ($this->headersLimit) {
            $connector->setHeadersLimit($this->headersLimit);
        }

        if ($this->detectDumpTraceAndSource) {
            $connector->getDebugDispatcher()->detectTraceAndSource = true;
        }

        $dumper = $connector->getDumper();
        $dumper->levelLimit = $this->dumperLevelLimit;
        $dumper->itemsCountLimit = $this->dumperItemsCountLimit;
        $dumper->itemSizeLimit = $this->dumperItemSizeLimit;
        $dumper->dumpSizeLimit = $this->dumperDumpSizeLimit;
        $dumper->detectCallbacks = $this->dumperDetectCallbacks;

        if ($this->isEvalEnabled) {
            $connector->startEvalRequestsListener();
        }
    }
}
