<?php
/**
 * Index模块注入文件
 */
namespace Modules\Index;

use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\ModuleDefinitionInterface;
use Phalcon\DiInterface;
use Phalcon\Loader;
use Phalcon\Mvc\View;
use Phalcon\DI;

class Module implements ModuleDefinitionInterface
{
    /**
     * Register a specific autoloader for the module
     */
    public function registerAutoloaders(DiInterface $di = null)
    {
        $appPath = DI::getDefault()->get('app_path');
        $rootPath = DI::getDefault()->get('root_path');
        $moduleName = DI::getDefault()->get('router')->getModuleName();
        $ucModuleName = ucfirst($moduleName);
        $loader = new Loader();

        $loader->registerNamespaces(
            array(
                'Cache'                       => "{$appPath}/cache/",
                'Base'                        => "{$appPath}/base/",
                'Model'                       => "{$appPath}/model/",
                'Service'                     => "{$appPath}/service/",
                'Common'                      => "{$rootPath}/common/",
                'Lib'                         => "{$rootPath}/library/",
                "{$ucModuleName}\Controllers" => "{$appPath}/modules/{$moduleName}/controllers/",
            )
        );
        $loader->registerDirs(
            array(
                'modelsDir' => "{$appPath}/model/",
            )
        );
        $loader->register();
    }

    /**
     * Register specific services for the module
     */
    public function registerServices(DiInterface $di)
    {
        $moduleName = DI::getDefault()->get('router')->getModuleName();
        $ucModuleName = ucfirst($moduleName);
        //Registering a dispatcher
        $di->remove('dispatcher');
        $di->set('dispatcher', function () use($ucModuleName){
            $dispatcher = new Dispatcher();
            $dispatcher->setDefaultNamespace("{$ucModuleName}\Controllers");
            return $dispatcher;
        });

        //Registering the view component
        $di->set('view', function () {
            $appPath = DI::getDefault()->get('app_path');
            $view = new View();
            $view->setViewsDir($appPath . '/views/');
            return $view;
        });
    }

}