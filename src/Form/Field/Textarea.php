<?php

namespace Encore\Admin\Form\Field;

use Encore\Admin\Form\Field;

class Textarea extends Field
{
    use PlainInput;
    /**
     * Default rows of textarea.
     *
     * @var int
     */
    protected $rows = 5;

    /**
     * Set rows of textarea.
     *
     * @param int $rows
     *
     * @return $this
     */
    public function rows($rows = 5)
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $this->defaultAttribute('id', $this->id)
        ->defaultAttribute('name', $this->elementName ?: $this->formatName($this->column))
        ->defaultAttribute('value', old($this->column, $this->value()))
        ->defaultAttribute('class', 'form-control '.$this->getElementClassString())
        ->defaultAttribute('placeholder', $this->getPlaceholder());

        if (is_array($this->value)) {
            $this->value = json_encode($this->value, JSON_PRETTY_PRINT);
        }

        return parent::render()->with(['rows' => $this->rows]);
    }
}
