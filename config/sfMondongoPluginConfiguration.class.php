<?php

/*
 * Copyright 2010 Pablo Díez Pascual <pablodip@gmail.com>
 *
 * This file is part of sfMondongoPlugin.
 *
 * sfMondongoPlugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sfMondongoPlugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with sfMondongoPlugin. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * sfMondongoPluginConfiguration.
 *
 * @package sfMondongoPlugin
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class sfMondongoPluginConfiguration extends sfPluginConfiguration
{
  protected $logs = array();

  public function initialize()
  {
    $this->dispatcher->connect('context.load_factories', array($this, 'listenToContextLoadFactories'));

    $this->dispatcher->connect('component.method_not_found', array($this, 'listenToComponentMethodNotFound'));
  }

  public function listenToContextLoadFactories(sfEvent $event)
  {
    $context = $event->getSubject();

    $mondongo = new Mondongo();

    // databases
    $databaseManager = $context->getDatabaseManager();
    foreach ($databaseManager->getNames() as $name)
    {
      $database = $databaseManager->getDatabase($name);
      if ($database instanceof sfMondongoDatabase)
      {
        $mondongo->setConnection($name, $database->getMondongoConnection());
      }
    }

    // log
    if (sfConfig::get('sf_logging_enabled'))
    {
      $mondongo->setLogCallable(array($this, 'log'));

      if (sfConfig::get('sf_web_debug'))
      {
        $this->dispatcher->connect('debug.web.load_panels', array($this, 'listenToDebugWebLoadPanels'));
      }
    }

    // context
    $context->set('mondongo', $mondongo);

    // container
    MondongoContainer::setDefault($mondongo);
  }

  public function getLogs()
  {
    return $this->logs;
  }

  public function log(array $log)
  {
    $this->dispatcher->notify(new sfEvent('sfMondongo', 'application.log', array('sfMondongo')));

    $this->logs[] = $log;
  }

  public function listenToDebugWebLoadPanels(sfEvent $event)
  {
    $event->getSubject()->setPanel('mondongo', new sfMondongoWebDebugPanel($event->getSubject()));
  }

  public function listenToComponentMethodNotFound(sfEvent $event)
  {
    if ('getMondongo' == $event['method'])
    {
      $event->setReturnValue($event->getSubject()->getContext()->get('mondongo'));

      return true;
    }

    return false;
  }
}
