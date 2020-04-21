<?php

namespace Neko\Datatables\Iterators;


use FilterIterator;

/**
 * Class VisibleColumns
 *
 * @package Neko\Datatables\Iterators
 */
class VisibleColumns extends FilterIterator
{

    /**
     * @return bool
     */
    public function accept(): bool
    {
        return !$this->current()->hidden;
    }
}