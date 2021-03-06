<?php
/**
 * Provides framework late binding services
 * @file services.php
 */

use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Session\Adapter\Files as SessionAdapter;
use Phalcon\Mvc\Dispatcher as Dispatcher;

$di = new FactoryDefault();

/**
 * Router
 */
$di->set('router', function () {
    return require __DIR__ . '/routes.php';
}, true);

/**
 * URL component
 * The URL component is used to generate all kind of urls in the application
 */
$di->set('url', function () use ($config) {
    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);

    return $url;
}, true);

/**
 * Database connection component
 * Database connection is created based on parameters defined in the configuration file
 */
$di->set('db', function () use ($config, $di) {
    $dba = new DbAdapter(array(
        "host"     => $config->database->host,
        "username" => $config->database->username,
        "password" => $config->database->password,
        "dbname"   => $config->database->name
    ));


    // $eventsManager = $di->getShared('eventsManager');
    // $eventsManager->attach('db', function ($event, $connection) {
    //     if ($event->getType() == 'afterQuery') {
    //         echo $connection->getSQLStatement() . '</br>' .PHP_EOL;
    //     }
    // });

    // $dba->setEventsManager($eventsManager);
    return $dba;
}, true);

$di->set('translate', function () use ($di) {
    $dispatcher = $di->getShared('dispatcher');
    $language = $dispatcher->getParam("lang");

    if (file_exists("../app/languages/".$language.".php")) {
        require "../app/languages/".$language.".php";
    } else {
        require "../app/languages/en-US.php";
    }

    return new \Phalcon\Translate\Adapter\NativeArray(array(
        "content" => $messages
    ));
}, true);

/**
 * View component
 * Set up the view component
 */
$di->set('view', function () use ($config) {
    $view = new View();
    $view->setViewsDir($config->application->viewsDir);
    $view->registerEngines(array(
        '.volt'  => function ($view, $di) use ($config) {
                $volt = new VoltEngine($view, $di);
                $volt->setOptions(array(
                    'compiledPath'      => $config->application->cacheDir,
                    'compiledSeparator' => '_',
                    'compileAlways'     => !filter_var($config->application->enableCache, FILTER_VALIDATE_BOOLEAN)
                ));

                $volt->getCompiler()->addFunction('_', function ($resolvedArgs, $exprArgs) use ($di) {
                    if ($argNo = count($exprArgs) > 1) {
                        $msg = $exprArgs[0]['expr']['value'];
                        $argvs = 'array(';
                        for ($i = 0; $i<$argNo; $i++) {
                            $argvs .= '\'' . $i . '\'' . '=>' . '$' . $exprArgs[$i+1]['expr']['value'] . ',';
                        };
                        $argvs .= ')';
                        $stringBuilder = '$this->translate->query(\'' . $msg . '\',' . $argvs . ')';
                        return $stringBuilder;
                    } else {
                        return sprintf('$this->translate->query("%s")', $exprArgs[0]['expr']['value']);
                    }
                });

                return $volt;
        },
        '.phtml' => 'Phalcon\Mvc\View\Engine\Php'
    ));

    return $view;
}, true);

/**
 * Logger Component
 * The logger is a component for making systematic records of events, observations, or measurements.
 */
$di->set('logger', function () use ($config) {
    $logger = new Phalcon\Logger\Multiple();

    if (!filter_var($config->application->enableLogger, FILTER_VALIDATE_BOOLEAN)) {
        return $logger;
    }

    $loggerFileHandle = new Phalcon\Logger\Adapter\File($config->application->logsDir . gmdate("d-m-Y") . '.log');
    $logger->push($loggerFileHandle);

    $loggerFirePHPHandle = new Phalcon\Logger\Adapter\Firephp("");
    $logger->push($loggerFirePHPHandle);

    return $logger;
}, true);

/**
 * View Component
 * Start the session the first time a component requests the session service
 */
$di->set('session', function () {
    session_name('mySessionName3');
    $session = new Phalcon\Session\Adapter\Files();
    $session->start();

    return $session;
}, true);


/**
 * Dispatcher Component
 * Dispatching is the process of taking the request object, extracting the module name,
 * controller name, action name, and optional parameters contained in it, and then
 * instantiating a controller and calling an action of that controller.
 */
$di->set('dispatcher', function () use ($di) {
    // ERROR 404 - Page not found
    $evManager = $di->getShared('eventsManager');
    $evManager->attach(
        "dispatch:beforeException",
        function ($event, $dispatcher, $exception) {
            switch ($exception->getCode()) {
                case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                    $dispatcher->forward(
                        array(
                            'controller' => 'error',
                            'action'     => 'show404',
                        )
                    );
                    return false;
            }
        }
    );
    $dispatcher = new Dispatcher();
    $dispatcher->setEventsManager($evManager);
    return $dispatcher;
}, true);
