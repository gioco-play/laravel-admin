<?php

namespace GiocoPlus\Admin\Form\Field;

use GiocoPlus\Admin\Form\Field;

class Nullable extends Field
{
    public function __construct()
    {
    }

    public function __call($method, $parameters)
    {
        return $this;
    }
}
