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
 * sfMondongoDatabase
 *
 * @package sfMondongoPlugin
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class sfMondongoDatabase extends sfDatabase
{
  protected $mondongoConnection;

  public function initialize($parameters = array())
  {
    parent::initialize($parameters);

    if (!$this->hasParameter('server'))
    {
      throw new RuntimeException(sprintf('Connection "%s" without server".', $this->getParameter('name')));
    }

    $options = array();
    if ($this->hasParameter('persist'))
    {
      $options['persist'] = $this->getParameter('persist');
    }

    $mongo = new Mongo($this->getParameter('server'), $options);

    if (!$this->hasParameter('database'))
    {
      throw new RuntimeException(sprintf('Connection "%s" without database.', $this->getParameter('name')));
    }

    $database = $this->getParameter('database');

    if (is_array($database))
    {
      if (!array_key_exists('name', $database))
      {
        throw new InvalidArgumentException(sprintf('Database not valid in the connection "%s"', $this->getParameter('name')));
      }

      $db = $mongo->selectDB($database['name']);

      if (array_key_exists($database['username']) && array_key_exists($database['password']))
      {
        $db->authenticate($database['username'], $database['password']);
      }
    }
    else
    {
      $db = $mongo->selectDB($database);
    }

    $this->mondongoConnection = new MondongoConnection($db);
  }

  public function getMondongoConnection()
  {
    return $this->mondongoConnection;
  }

  public function connect()
  {
  }

  public function shutdown()
  {
  }
}
