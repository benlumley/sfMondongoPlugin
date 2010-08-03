[?php

abstract class Base<?php echo $this->data['name'] ?> extends MondongoDocument
{
  static public function define($definition)
  {

<?php if (isset($this->data['connection'])): ?>
    $definition->setConnection('<?php echo $this->data['connection'] ?>');
<?php endif; ?>

<?php if (isset($this->data['collection'])): ?>
    $definition->setCollection('<?php echo $this->data['collection'] ?>');
<?php endif; ?>

<?php if (isset($this->data['fields'])): ?>
    $definition->setFields(array(
<?php foreach ($this->data['fields'] as $name => $field): ?>
      '<?php echo $name ?>' => <?php echo $this->asPhp($field) ?>,
<?php endforeach; ?>
    ));
<?php endif; ?>

<?php if (isset($this->data['references'])): ?>
<?php foreach ($this->data['references'] as $name => $reference): ?>
    $definition->reference('<?php echo $name ?>', <?php echo $this->asPhp($reference) ?>);
<?php endforeach; ?>
<?php endif; ?>

<?php if (isset($this->data['embeds'])): ?>
<?php foreach ($this->data['embeds'] as $name => $embed): ?>
    $definition->embed('<?php echo $name ?>', <?php echo $this->asPhp($embed) ?>);
<?php endforeach; ?>
<?php endif; ?>

<?php if (isset($this->data['relations'])): ?>
<?php foreach ($this->data['relations'] as $name => $relation): ?>
    $definition->relation('<?php echo $name ?>', <?php echo $this->asPhp($relation) ?>);
<?php endforeach; ?>
<?php endif; ?>

<?php if (isset($this->data['actAs'])): ?>
<?php foreach ($this->data['actAs'] as $class => $options): if (!class_exists($class)) { $class = 'MondongoExtension'.$class; } ?>
    $definition->addExtension(new <?php echo $class ?>($definition, <?php echo null !== $options ? $this->asPhp($options) : 'array()' ?>));
<?php endforeach; ?>
<?php endif; ?>

<?php if (isset($this->data['indexes'])): ?>
<?php foreach ($this->data['indexes'] as $index): ?>
    $definition->addIndex(<?php echo $this->asPhp($index) ?>);
<?php endforeach; ?>
<?php endif; ?>
  }
}
