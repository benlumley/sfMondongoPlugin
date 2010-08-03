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
 * sfMondongoDataLoadTask.
 *
 * @package sfMondongoPlugin
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class sfMondongoDataLoadTask extends sfMondongoTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', true),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
    ));

    $this->namespace = 'mondongo';
    $this->name = 'data-load';
    $this->briefDescription = 'Load fixture data';

    $this->detailedDescription = <<<EOF
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $databaseManager = new sfDatabaseManager($this->configuration);

    $this->logSection('mondongo', 'droping databases');

    foreach ($this->getMondongo()->getConnections() as $connection)
    {
      $connection->getDB()->drop();
    }

    $this->logSection('mondongo', 'parsing data');

    $data = array();
    foreach (sfFinder::type('file')
      ->name('*.yml')
      ->sort_by_name()
      ->follow_link()
      ->in(sfConfig::get('sf_data_dir').'/mondongo')
    as $file)
    {
      $data = sfToolkit::arrayDeepMerge($data, sfYaml::load($file));
    }

    $classes = array();
    foreach ($data as $class => $documents)
    {
      $classes[$class] = $this->getMondongo()->getRepository($class)->getDefinition()->getReferences();
    }

    $this->logSection('mondongo', 'loading data');

    $documents = array();
    do
    {
      $change = false;

      foreach ($classes as $class => $references)
      {
        $process = true;

        foreach ($references as $reference)
        {
          if (isset($classes[$reference['class']]))
          {
            $process = false;
          }
        }

        if ($process)
        {
          foreach ($data[$class] as $key => $datum)
          {
            // referencias
            foreach ($references as $name => $reference)
            {
              // hay referencia
              if (isset($datum[$name]))
              {
                // many
                if ('many' == $reference['type'])
                {
                  $datums = array();
                  foreach ($datum[$name] as $key)
                  {
                    if (!isset($documents[$reference['class']][$key]))
                    {
                      throw new InvalidArgumentException(sprintf('The reference "%s" of the class "%s" does not exists.', $key, $reference['class']));
                    }

                    $datums[] = $documents[$reference['class']][$key];
                  }

                  $datum[$name] = $datums;
                }
                // one
                else
                {
                  if (!isset($documents[$reference['class']][$datum[$name]]))
                  {
                    throw new InvalidArgumentException(sprintf('The reference "%s" of the class "%s" does not exists.', $name, $reference['class']));
                  }

                  $datum[$name] = $documents[$reference['class']][$datum[$name]];
                }
              }
            }

            $document = new $class();
            $document->fromArray($datum);
            $document->save();

            $documents[$class][$key] = $document;
          }

          $change = true;
          unset($classes[$class]);
        }
      }
    }
    while ($classes && $change);

    if (!$change)
    {
      throw new RuntimeException('Unable to process everything.');
    }
  }
}
