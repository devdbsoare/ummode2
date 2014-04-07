<?php

<<<<<<< HEAD
use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Url as UrlResolver;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;
use Phalcon\Session\Adapter\Files as SessionAdapter;

$di = new FactoryDefault();

/**
 * The URL component is used to generate all kind of urls in the application
 */
$di->set('url', function () use ($config) {
    $url = new UrlResolver();
    $url->setBaseUri($config->application->baseUri);

    return $url;
}, true);
=======
use Phalcon\Mvc\View;

$di = new Phalcon\DI\FactoryDefault();

>>>>>>> f6f4725d1343c175c0293a3d04b78ab542148db4

/**
 * Database connection
 * Database connection is created based on parameters defined in the configuration file
 */
$di->set('db', function () use ($config) {
    return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
        "host" => $config->database->host,
        "username" => $config->database->username,
        "password" => $config->database->password,
        "dbname" => $config->database->name
    ));
});

/**
 * Setting up the view component
 */
$di->set('view', function () use ($config) {
    $view = new View();
    $view->setViewsDir($config->application->viewsDir);
    $view->registerEngines(array(
        '.volt' => function ($view, $di) use ($config) {
            $volt = new VoltEngine($view, $di);
            $volt->setOptions(array(
                'compiledPath' => $config->application->cacheDir,
                'compiledSeparator' => '_',
<<<<<<< HEAD
                'compileAlways' => true//$config->application->enableCache
=======
                'compileAlways' => $config->application->enableCache
>>>>>>> f6f4725d1343c175c0293a3d04b78ab542148db4
            ));

            return $volt;
        },
        '.phtml' => 'Phalcon\Mvc\View\Engine\Php'
    ));

    return $view;
}, true);
<<<<<<< HEAD

//Start the session the first time a component requests the session service
$di->set('session', function () {
    $session = new Phalcon\Session\Adapter\Files();
    $session->start();
    return $session;
});
=======
>>>>>>> f6f4725d1343c175c0293a3d04b78ab542148db4