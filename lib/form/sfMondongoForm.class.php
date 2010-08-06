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
 * sfMondongoForm.
 *
 * @package sfMondongoPlugin
 * @author  Pablo Díez Pascual <pablodip@gmail.com>
 */
abstract class sfMondongoForm extends BaseForm
{
  protected $document;

  public function __construct(MondongoDocumentBaseSpeed $document = null, array $options = array(), $CSRFSecret = null)
  {
    $class = $this->getModelName();

    if ($document)
    {
  	  if (!$document instanceof $class)
  	  {
  	    throw new InvalidArgumentException(sprintf('The document is not of the class "%s".', $class));
  	  }

  	  $this->document = $document;
    }
    else
    {
      $this->document = new $class();
    }

  	$defaults = $this->document->toArray();

  	// sfWidgetFormDate does not support DateTime object as value
  	foreach ($defaults as &$default)
  	{
  	  if ($default instanceof DateTime)
  	  {
  	    $default = $default->getTimestamp();
  	  }
  	}

  	parent::__construct($defaults, $options, $CSRFSecret);
  }

  abstract public function getModelName();

  public function getDocument()
  {
    return $this->document;
  }

  public function isNew()
  {
    return $this->document->isNew();
  }

  public function save()
  {
    if (!$this->isValid())
    {
      throw new LogicException('Cannot save the sfMondongoForm if it is not valid.');
    }

    $this->document->fromArray($this->getValues());

    $this->document->save();

    return $this->getDocument();

  }
}
