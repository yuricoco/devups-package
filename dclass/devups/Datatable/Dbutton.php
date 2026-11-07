<?php


namespace dclass\devups\model;


class Dbutton
{
    public $content;
    public $class;
    public $action;
    public $href;
    public $type = "button";
    public $directive = [];

    public static function init($content, $action, $class = '', $directive = [])
    {
        $btn = new self();
        $btn->content = $content;
        $btn->action = $action;
        $btn->directive = $directive;
        $btn->class = $class;
        return $btn;
    }
    public static function link($content, $action, $class = '', $directive = [])
    {
        $btn = new self();
        $btn->content = $content;
        $btn->href = $action;
        $btn->class = $class;
        $btn->directive = $directive;
        return $btn;
    }

    public function render()
    {

        if ($this->href)
            if ($this->directive) {
                $directive = \Form::serialysedirective($this->directive);
                return '<a class="' . $this->class . '" ' . $directive . ' href="' . $this->href . '" >' . $this->content . '</a>';
            } else {
                return '<a class="' . $this->class . '" href="' . $this->href . '" >' . $this->content . '</a>';
            }
        else
            if ($this->directive) {
                $directive = \Form::serialysedirective($this->directive);
                return '<button type="' . $this->type . '" ' . $directive . ' >' . $this->content . '</button>';
            } else {
                return '<button type="button" class="' . $this->class . '" ' . $this->action . ' >' . $this->content . '</button>';
            }

    }
}