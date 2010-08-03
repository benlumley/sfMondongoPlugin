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
 * sfMondongoModelGenerator.
 *
 * @package sfMondongoPlugin
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class sfMondongoModelGenerator extends sfGenerator
{
  protected $data = array();

  public function initialize(sfGeneratorManager $generatorManager)
  {
    parent::initialize($generatorManager);

    $this->setGeneratorClass('sfMondongoModel');
  }

  public function generate($params = array())
  {
    $schema = $this->prepareSchema();

    $dir     = sfConfig::get('sf_lib_dir').'/model/mondongo';
    $baseDir = $dir.'/base';

    if (!is_dir($baseDir))
    {
      mkdir($baseDir, 0777, true);
    }

    // documents
    foreach ($schema['documents'] as $name => $data)
    {
      $this->data         = $data;
      $this->data['name'] = $name;

      // plugin
      if (array_key_exists('plugin', $data))
      {
        $documentDir     = $dir.'/'.$data['plugin']['name'];
        $documentBaseDir = $documentDir.'/base';

        if (!is_dir($documentBaseDir))
        {
          mkdir($documentBaseDir, 0777, true);
        }

        $documentTemplate   = 'DocumentPlugin.php';
        $repositoryTemplate = 'RepositoryPlugin.php';

        if (!is_dir($documentPluginDir = $data['plugin']['dir'].'/lib/model/mondongo'))
        {
          mkdir($documentPluginDir, 0777, true);
        }

        if (!file_exists($file = $documentPluginDir.'/Plugin'.$name.'.php'))
        {
          file_put_contents($file, $this->evalTemplate('PluginDocument.php'));
        }

        if (!file_exists($file = $documentPluginDir.'/Plugin'.$name.'Repository.php'))
        {
          file_put_contents($file, $this->evalTemplate('PluginRepository.php'));
        }
      }
      else
      {
        $documentDir     = $dir;
        $documentBaseDir = $baseDir;

        $documentTemplate   = 'Document.php';
        $repositoryTemplate = 'Repository.php';
      }

      // document
      file_put_contents($documentBaseDir.'/Base'.$name.'.php', $this->evalTemplate('BaseDocument.php'));

      if (!file_exists($file = $documentDir.'/'.$name.'.php'))
      {
        file_put_contents($file, $this->evalTemplate($documentTemplate));
      }

      // repository
      if (!file_exists($file = $documentDir.'/'.$name.'Repository.php'))
      {
        file_put_contents($file, $this->evalTemplate($repositoryTemplate));
      }
    }

    // embeds
    foreach ($schema['embeds'] as $name => $data)
    {
      $this->data         = $data;
      $this->data['name'] = $name;

      // plugin
      if (array_key_exists('plugin', $data))
      {
        $documentDir     = $dir.'/'.$data['plugin']['name'];
        $documentBaseDir = $documentDir.'/base';

        if (!is_dir($documentBaseDir))
        {
          mkdir($documentBaseDir, 0777, true);
        }

        $documentTemplate = 'DocumentEmbedPlugin.php';

        if (!is_dir($documentPluginDir = $data['plugin']['dir'].'/lib/model/mondongo'))
        {
          mkdir($documentPluginDir, 0777, true);
        }

        if (!file_exists($file = $documentPluginDir.'/Plugin'.$name.'.php'))
        {
          file_put_contents($file, $this->evalTemplate('PluginDocumentEmbed.php'));
        }
      }
      else
      {
        $documentDir     = $dir;
        $documentBaseDir = $baseDir;

        $documentTemplate = 'DocumentEmbed.php';
      }

      // document
      file_put_contents($documentBaseDir.'/Base'.$name.'.php', $this->evalTemplate('BaseDocumentEmbed.php'));

      if (!file_exists($file = $documentDir.'/'.$name.'.php'))
      {
        file_put_contents($file, $this->evalTemplate($documentTemplate));
      }
    }
  }

  protected function prepareSchema()
  {
    $schema = array('documents' => array(), 'embeds' => array());

    $finder = sfFinder::type('file')->name('*.yml')->sort_by_name()->follow_link();

    // plugins
    foreach ($this->generatorManager->getConfiguration()->getPlugins() as $name)
    {
      $plugin = $this->generatorManager->getConfiguration()->getPluginConfiguration($name);

      foreach ($finder->in($plugin->getRootDir().'/config/mondongo') as $file)
      {
        $data = sfYaml::load($file);

        foreach (array('documents', 'embeds') as $type)
        {
          if (!array_key_exists($type, $data))
          {
            continue;
          }

          foreach ($data[$type] as $model => $datum)
          {
            if (array_key_exists($model, $schema[$type]))
            {
              $schema[$type][$model] = sfToolkit::arrayDeepMerge($schema[$type][$model], $datum);
            }
            else
            {
              $schema[$type][$model] = $datum;
            }

            if (!array_key_exists('plugin', $schema[$type][$model]))
            {
              $schema[$type][$model]['plugin'] = array('name' => $name, 'dir' => $plugin->getRootDir());
            }
          }
        }
      }
    }

    // project
    foreach ($finder->in(sfConfig::get('sf_config_dir').'/mondongo') as $file)
    {
      $data = sfYaml::load($file);
      $schema = sfToolkit::arrayDeepMerge($schema, $data);
    }

    // relations
    foreach ($schema['documents'] as $class => $document)
    {
      if (array_key_exists('references', $document))
      {
        foreach ($document['references'] as $name => $reference)
        {
          $relationName = $class.'s';
          if (array_key_exists('foreignAlias', $reference))
          {
            $relationName = $reference['foreignAlias'];
          }

          $relationType = 'many';
          if (array_key_exists('foreignType', $reference) && 'one' == $reference['foreignType'])
          {
            $relationtype = 'one';
          }

          $schema['documents'][$reference['class']]['relations'][$relationName] = array(
            'class'  => $class,
            'column' => $reference['field'],
            'type'   => $relationType,
          );
        }
      }
    }

    return $schema;
  }

  public function asPhp($variable)
  {
    return str_replace(array("\n", 'array ('), array('', 'array('), var_export($variable, true));
  }
}
