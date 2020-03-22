<?php
/**
 * Datatables PHP Model
 */

namespace Neko\Datatables;

use Neko\Datatables\Request\Column;
use Neko\Datatables\Request\Order;
use Neko\Datatables\Request\Search;
use Iterator;

/**
 * Interface RequestInterface
 *
 * @package Neko\Datatables
 * @see     https://datatables.net/manual/server-side
 * @author  Krzysztof Kardasz <krzysztof@kardasz.eu>
 */
interface RequestInterface
{
    /**
     * @return int|null
     */
    public function getDraw(): ?int;

    /**
     * @return Iterator|Column[]
     */
    public function getColumns(): Iterator;

    /**
     * @param int $index
     *
     * @return null|Column
     */
    public function getColumnAt(int $index): ?Column;

    /**
     * @return Iterator|Order[]
     */
    public function getOrder(): Iterator;

    /**
     * @return int|null
     */
    public function getStart(): ?int;

    /**
     * @return int|null
     */
    public function getLength(): ?int;

    /**
     * @return Search|null
     */
    public function getSearch(): ?Search;
}
