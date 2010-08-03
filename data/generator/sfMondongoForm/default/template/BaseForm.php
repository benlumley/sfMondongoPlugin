[?php

abstract class Base<?php echo $this->data['name'] ?>Form extends BaseFormMondongo
{
  public function setup()
  {
    $this->setWidgets(array(
<?php foreach ($this->getWidgetsForDefinition($this->data['definition']) as $name => $widget): ?>
      '<?php echo $name ?>' => new <?php echo $widget['class'] ?>(<?php echo $widget['options'] ?>),
<?php endforeach; ?>
    ));

    $this->setValidators(array(
<?php foreach ($this->getValidatorsForDefinition($this->data['definition']) as $name => $validator): ?>
      '<?php echo $name ?>' => new <?php echo $validator['class'] ?>(<?php echo $validator['options'] ?>),
<?php endforeach; ?>
    ));

    $this->widgetSchema->setNameFormat('<?php echo $this->underscore($this->data['name']) ?>[%s]');
  }

  public function getModelName()
  {
    return '<?php echo $this->data['name'] ?>';
  }
}
