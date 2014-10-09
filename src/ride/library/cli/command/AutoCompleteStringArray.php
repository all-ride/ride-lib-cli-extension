<?php

namespace ride\library\cli\command;

use ride\library\cli\input\AutoCompletable;

class AutoCompleteStringArray implements AutoCompletable
{

    /**
     * @var array
     */
    private $values;

    /**
     * @param array $values
     */
    public function __construct(array $values) {
        $this->values = $values;
    }

    /**
     * {@inheritdoc}
     */
    public function autoComplete($input) {
        $matches = array();

        foreach ($this->values as $value) {
            if (0 === strpos($value, $input)) {
                $matches[] = $value;
            }
        }

        return $matches;
    }

}