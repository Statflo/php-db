<?php

namespace Statflo\DB\Repository;

class FilterableCollection extends \ArrayObject implements \JsonSerializable
{
    const FILTER_LOOSE_DIFFERENT = "!=";
    const FILTER_LOOSE_EQUALS    = "=";

    const FILTER_DIFFERENT = "!==";
    const FILTER_EQUALS    = "==";

    /**
     * @param string $method.
     * @param mixed $value.
     * @param string $mode. FILTER_DIFFERENT || FILTER_LOOSE_DIFFERENT || FILTER_EQUALS || FILTER_LOOSE_EQUALS
     *
     * @return FilterableCollection
     */
    public function filter($method, $value, $mode = null)
    {
        $mode = $mode ?: self::FILTER_LOOSE_EQUALS;

        return new self(array_filter($this->getArrayCopy(), function($entry) use($method, $mode, $value) {
            return eval(sprintf('return "%s" %s "%s";', $entry->{$method}(), $mode, $value));
        }));
    }

    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }
}
