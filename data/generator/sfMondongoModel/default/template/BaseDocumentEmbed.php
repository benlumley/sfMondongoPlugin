[?php

abstract class Base<?php echo $this->data['name'] ?> extends MondongoDocumentEmbed
{
  static public function define($definition)
  {

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

  }
}
