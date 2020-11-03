<?php

namespace GiocoPlus\Admin\Form\Field;

use GiocoPlus\Admin\Form\Field;

class Editor extends Field
{
    protected static $js = [
        // '//cdn.ckeditor.com/4.5.10/standard/ckeditor.js',
    ];

    public function render()
    {
        $prefix = config('lfm.url_prefix');
        $options = "{
            filebrowserImageBrowseUrl: '/{$prefix}?type=Images',
            filebrowserImageUploadUrl: '/{$prefix}/upload?type=Images&_token=',
            filebrowserBrowseUrl: '/{$prefix}?type=Files',
            filebrowserUploadUrl: '/{$prefix}/upload?type=Files&_token='
          }";

        //$this->script = "CKEDITOR.replace('{$this->column}', $options);";
        $this->script = "$('textarea.ckeditor').ckeditor($options);";
        return parent::render();
    }
}
