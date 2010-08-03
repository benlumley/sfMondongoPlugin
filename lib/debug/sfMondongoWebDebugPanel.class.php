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
 * Panel Web Debug for sfMondongo.
 *
 * @package sfMondongoPlugin
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class sfMondongoWebDebugPanel extends sfWebDebugPanel
{
  public function getTitle()
  {
    if ($nb = count($this->getLogs()))
    {
      return '<img src="/sfMondongoPlugin/images/web_debug/sf_mondongo.png" alt="Mondongo Queries" /> '.$nb;
    }
  }

  public function getPanelTitle()
  {
    return 'Mondongo Queries';
  }

  public function getPanelContent()
  {
    return '
      <div id="sfWebDebugDatabaseLogs">
        <h3>Mondongo Version: '.Mondongo::VERSION.'</h3>
        <ol>'.implode("\n", $this->getLogsList()).'</ol>
      </div>
    ';
  }

  protected function getLogs()
  {
    return sfContext::getInstance()->getConfiguration()->getPluginConfiguration('sfMondongoPlugin')->getLogs();
  }

  protected function getLogsList()
  {
    $logs = array();
    foreach ($this->getLogs() as $log)
    {
      $connection = $log['connection'];
      $database   = $log['database'];
      $collection = $log['collection'];
      unset($log['connection'], $log['database'], $log['collection']);

      $logs[] = sprintf(<<<EOF
<li>
  <p class="sfWebDebugDatabaseQuery"><pre>%s</pre></p>
  <div class="sfWebDebugDatabaseLogInfo">{ connection: %s, database: %s, collection: %s }</div>
</li>
EOF
        ,
        sfYaml::dump($log),
        $connection,
        $database,
        $collection
      );
    }

    return $logs;
  }
}
