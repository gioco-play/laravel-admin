<?php

namespace GiocoPlus\Admin\Form\Field;

class Rate extends Text
{
    public function render()
    {
        $this->prepend('')
            ->append('%')
            ->defaultAttribute('style', 'text-align:right;')
            ->defaultAttribute('placeholder', 0);

        return parent::render();
    }
}
