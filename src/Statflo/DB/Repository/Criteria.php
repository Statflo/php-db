<?php

namespace Statflo\DB\Repository;

class Criteria
{
    public $condition;
    public $value;

    public function __construct($condition, $value = null)
    {
        $this->condition = $condition;
        $this->value     = $value;
    }

    public function get()
    {
        return [$this->condition => $this->value];
    }
}
