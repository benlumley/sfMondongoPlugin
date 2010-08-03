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
 * sfMondongoFormGenerator.
 *
 * @package sfMondongoPlugin
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
class sfMondongoFormGenerator extends sfGenerator
{
  public function initialize(sfGeneratorManager $generatorManager)
  {
    parent::initialize($generatorManager);

    $this->setGeneratorClass('sfMondongoForm');
  }

  public function generate($params = array())
  {
    $models = $this->prepareModels();

    $dir     = sfConfig::get('sf_lib_dir').'/form/mondongo';
    $baseDir = $dir.'/base';

    if (!is_dir($baseDir))
    {
      mkdir($baseDir, 0777, true);
    }

    // BaseFormMondongo
    if (!file_exists($file = $dir.'/BaseFormMondongo.class.php'))
    {
      file_put_contents($file, $this->evalTemplate('BaseFormMondongo.php'));
    }

    foreach ($models as $name => $data)
    {
      $this->data         = $data;
      $this->data['name'] = $name;

      // plugin
      if (array_key_exists('plugin', $data))
      {
        $formDir     = $dir.'/'.$data['plugin']['name'];
        $formBaseDir = $formDir.'/base';

        if (!is_dir($formBaseDir))
        {
          mkdir($formBaseDir, 0777, true);
        }

        $formTemplate = 'FormPlugin.php';

        if (!is_dir($formPluginDir = $data['plugin']['dir'].'/lib/form/mondongo'))
        {
          mkdir($formPluginDir, 0777, true);
        }

        if (!file_exists($file = $formPluginDir.'/Plugin'.$name.'Form.class.php'))
        {
          file_put_contents($file, $this->evalTemplate('PluginForm.php'));
        }
      }
      else
      {
        $formDir     = $dir;
        $formBaseDir = $baseDir;

        $formTemplate = 'Form.php';
      }

      file_put_contents($formBaseDir.'/Base'.$name.'Form.class.php', $this->evalTemplate('BaseForm.php'));

      if (!file_exists($file = $formDir.'/'.$name.'Form.class.php'))
      {
        file_put_contents($file, $this->evalTemplate($formTemplate));
      }
    }
  }

  protected function prepareModels()
  {
    $models = array();
    foreach (MondongoContainer::getDefinitions() as $class => $definition)
    {
      $models[$class] = array('definition' => $definition);

      foreach (sfFinder::type('file')->name($class.'.php')->in(sfConfig::get('sf_lib_dir').'/model/mondongo') as $file)
      {
        $file = str_replace(sfConfig::get('sf_lib_dir').'/model/mondongo', '', $file);
        $file = str_replace('/'.$class.'.php', '', $file);

        if ($file)
        {
          $plugin = substr($file, 1);

          $models[$class]['plugin'] = array(
            'name' => $plugin,
            'dir'  => $this->generatorManager->getConfiguration()->getPluginConfiguration($plugin)->getRootDir(),
          );
        }
      }
    }

    return $models;
  }

  /*
   * Widgets
   */
  protected function getWidgetsForDefinition(MondongoDefinition $definition)
  {
    if ($definition instanceof MondongoDefinitionDocument)
    {
      return $this->getWidgetsForDefinitionDocument($definition);
    }
    else
    {
      return $this->getWidgetsForDefinitionDocumentEmbed($definition);
    }
  }

  protected function getWidgetsForDefinitionDocument(MondongoDefinitionDocument $definition)
  {
    $widgets = array();

    foreach ($definition->getFields() as $name => $field)
    {
      $widget = array(
        'class'   => $this->getWidgetClassForType($field['type']),
        'options' => $this->getWidgetOptionsForType($field['type']),
      );

      $widgets[$name] = $widget;
    }

    foreach ($definition->getReferences() as $name => $reference)
    {
      $options = array('model' => $reference['class'], 'multiple' => 'one' == $reference['type'] ? false : true);

      $widgets[$reference['field']] = array(
        'class'   => 'sfWidgetFormMondongoChoice',
        'options' => var_export($options, true),
      );
    }

    return $widgets;
  }

  protected function getWidgetsForDefinitionDocumentEmbed(MondongoDefinitionDocumentEmbed $definition)
  {
    $widgets = array();

    foreach ($definition->getFields() as $name => $field)
    {
      $widget = array(
        'class'   => $this->getWidgetClassForType($field['type']),
        'options' => $this->getWidgetOptionsForType($field['type']),
      );

      $widgets[$name] = $widget;
    }

    return $widgets;
  }

  protected function getWidgetClassForType($type)
  {
    $class = 'sfWidgetFormInputText';

    switch ($type)
    {
      case 'bin_data':
        $class = 'sfWidgetFormInputFile';
        break;
      case 'boolean':
        $class = 'sfWidgetFormInputCheckbox';
        break;
      case 'date':
        $class = 'sfWidgetFormDateTime';
        break;
    }

    return $class;
  }

  protected function getWidgetOptionsForType($type)
  {
    $options    = array();
    $attributes = array();

    $options    = count($options) ? sprintf('array(%s)', implode(', ', $options)) : 'array()';
    $attributes = count($attributes) ? sprintf('array(%s)', implode(', ', $attributes)) : 'array()';

    return sprintf('%s, %s', $options, $attributes);
  }

  /*
   * Validators
   */
  protected function getValidatorsForDefinition(MondongoDefinition $definition)
  {
    if ($definition instanceof MondongoDefinitionDocument)
    {
      return $this->getValidatorsForDefinitionDocument($definition);
    }
    else
    {
      return $this->getValidatorsForDefinitionDocumentEmbed($definition);
    }
  }

  protected function getValidatorsForDefinitionDocument(MondongoDefinitionDocument $definition)
  {
    $validators = array();

    foreach ($definition->getFields() as $name => $field)
    {
      $validator = array(
        'class'   => $this->getValidatorClassForType($field['type']),
        'options' => $this->getValidatorOptionsForType($field['type']),
      );

      $validators[$name] = $validator;
    }

    foreach ($definition->getReferences() as $name => $reference)
    {
      $options = array('model' => $reference['class'], 'multiple' => 'one' == $reference['type'] ? false : true);

      $validators[$reference['field']] = array(
        'class'   => 'sfValidatorMondongoChoice',
        'options' => var_export($options, true),
      );
    }

    return $validators;
  }

  protected function getValidatorsForDefinitionDocumentEmbed(MondongoDefinitionDocumentEmbed $definition)
  {
    $validators = array();

    foreach ($definition->getFields() as $name => $field)
    {
      $validator = array(
        'class'   => $this->getValidatorClassForType($field['type']),
        'options' => $this->getValidatorOptionsForType($field['type']),
      );

      $validators[$name] = $validator;
    }

    return $validators;
  }

  protected function getValidatorClassForType($type)
  {
    $class = 'sfValidatorString';

    switch ($type)
    {
      case 'bin_data':
        $class = 'sfValidatorFile';
        break;
      case 'boolean':
        $class = 'sfValidatorBoolean';
        break;
      case 'date':
        $class = 'sfValidatorDateTime';
        break;
      case 'float':
        $class = 'sfValidatorNumber';
        break;
      case 'integer':
        $class = 'sfValidatorInteger';
        break;
    }

    return $class;
  }

  protected function getValidatorOptionsForType($type)
  {
    $options    = array();
    $attributes = array();

    $options    = count($options) ? sprintf('array(%s)', implode(', ', $options)) : 'array()';
    $attributes = count($attributes) ? sprintf('array(%s)', implode(', ', $attributes)) : 'array()';

    return sprintf('%s, %s', $options, $attributes);
  }

  /*
   * Misc
   */
  public function underscore($name)
  {
    return strtolower(preg_replace(array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'), '\\1_\\2', $name));
  }
}
