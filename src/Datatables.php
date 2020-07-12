<?php

namespace Neko\Datatables;

use Closure;
use Neko\Datatables\DB\DatabaseInterface;
use Neko\Datatables\Http\Request;
use Neko\Datatables\Iterators\ColumnCollection;

/**
 * Class Datatables
 *
 * @package Neko\Datatables
 */
class Datatables
{
    /**
     * @var \Neko\Datatables\DB\DatabaseInterface
     */
    protected $db;

    /**
     * @var \Neko\Datatables\Iterators\ColumnCollection
     */
    protected $columns;

    /**
     * @var \Neko\Datatables\QueryBuilder
     */
    protected $builder;

    /**
     * @var Option
     */
    public $options;

    /**
     * @var array
     */
    protected $response;

    /**
     * @var array
     */
    protected $distinctColumn = [];
    protected $arrayColumn = [];

    /**
     * @var array
     */
    protected $distinctData = [];
    protected $arrayData = [];

    /**
     * Datatables constructor.
     *
     * @param \Neko\Datatables\DB\DatabaseInterface $db
     * @param Request $request
     */
    public function __construct(DatabaseInterface $db, Request $request = null)
    {
        $this->db = $db->connect();
        $this->options = new Option($request ?: Request::createFromGlobals());
    }

    /**
     * @param $column
     * @param Closure $closure
     * @return Datatables
     */
    public function add($column, Closure $closure): Datatables
    {
        $column = new Column($column);
        $column->closure = $closure;
        $column->interaction = false;
        $this->columns->append($column);

        return $this;
    }

    /**
     * @param $column
     * @param Closure $closure
     * @return Datatables
     */
    public function edit($column, Closure $closure): Datatables
    {
        $column = $this->columns->getByName($column);
        $column->closure = $closure;
        return $this;
    }

    /**
     * @param $column
     * @param Closure $closure
     * @return Datatables
     */
    public function filter($column, Closure $closure): Datatables
    {
        $column = $this->columns->getByName($column);
        $column->customFilter = $closure;

        return $this;
    }

    /**
     * @param $name
     * @return Datatables
     */
    public function setDistinctResponseFrom($name): Datatables
    {
        $this->distinctColumn[] = $name;

        return $this;
    }

    /**
     * @param $array
     * @return Datatables
     */
    public function setDistinctResponse($array): Datatables
    {
        $this->distinctData = $array;

        return $this;
    }

    public function setResponse($array): Datatables
    {
        $this->arrayData = $array;
        $this->columns = new ColumnCollection();
        $cols = array_keys(call_user_func_array('array_merge', $array));
        foreach ($cols as $name) {
            $this->columns->append(new Column($name));
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns->names();
    }

    /**
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->builder->full;
    }

    /**
     * @param string $column
     * @return Datatables
     */
    public function hide(string $column, $searchable = false): Datatables
    {
        $this->columns->getByName($column)->hide($searchable);

        return $this;
    }

    /**
     * @param string $query
     * @return Datatables
     */
    public function query($query): Datatables
    {
        $this->builder = new QueryBuilder($query, $this->options, $this->db);
        $this->columns = $this->builder->columns();
        return $this;
    }

    /**
     * @return Datatables
     */
    public function generate(): Datatables
    {
        $this->builder->setColumnAttributes();
        $this->builder->setFilteredQuery();
        $this->builder->setFullQuery();
        $this->setResponseData();

        return $this;
    }

    public function render(): Datatables
    {
        $columns = $this->options->columns();
        if ($columns) {
            $attributes = array_column($columns, null, 'data');

            foreach ($attributes as $index => $attr) {
                if ($this->columns->visible()->isExists($index)) {
                    $this->columns->visible()->get($index)->attr = $attr;
                }
            }
        }
        $this->setResponseDataArray();
        return $this;
    }

    /**
     * @return array
     */
    protected function getData(): array
    {
        $data = $this->db->query($this->builder->full);
        return array_map([$this, 'prepareRowData'], $data);
    }

    /**
     * @param $row
     * @return array
     */
    protected function prepareRowData($row): array
    {
        $keys = $this->builder->isDataObject() ? $this->columns->names() : array_keys($this->columns->names());

        $values = array_map(function (Column $column) use ($row) {
            return $column->value($row);
        }, $this->columns->visible()->getArrayCopy());

        return array_combine($keys, $values);
    }

    public function checkAssoc(): bool
    {
        if (!$this->options->columns()) {
            return false;
        }

        $data = array_column($this->options->columns(), 'data');
        $rangeSet = array_map('strval', array_keys($data));

        return array_intersect($data, $rangeSet) !== $data;
    }

    protected function getDataArr(): array
    {
        $data = $this->arrayData;
        return array_map([$this, 'prepareArrRowData'], $data);
    }

    protected function prepareArrRowData($row): array
    {
        $keys = self::checkAssoc() ? $this->columns->names() : array_keys($this->columns->names());

        $values = array_map(function (Column $column) use ($row) {
            return $column->value($row);
        }, $this->columns->visible()->getArrayCopy());

        return array_combine($keys, $values);
    }

    /**
     * @return array
     */
    private function getDistinctData(): array
    {
        foreach ($this->distinctColumn as $column) {
            $output[$column] = array_column($this->db->query($this->builder->getDistinctQuery($column)), $column);
        }

        return $output ?? [];
    }

    /**
     *
     */
    public function setResponseData(): void
    {
        $this->response['draw'] = $this->options->draw();
        $this->response['recordsTotal'] = $this->db->count($this->builder->query);
        $this->response['recordsFiltered'] = $this->db->count($this->builder->filtered);
        $this->response['data'] = $this->getData();

        if (\count($this->distinctColumn) > 0 || \count($this->distinctData) > 0) {
            $this->response['distinctData'] = array_merge($this->response['distinctData'] ?? [],
                $this->getDistinctData(), $this->distinctData);
        }
    }

    public function setResponseDataArray(): void
    {
        $this->response['draw'] = $this->options->draw();
        $this->response['recordsTotal'] = count($this->arrayData);
        $this->response['recordsFiltered'] = count($this->getDataArr());
        $this->response['data'] = $this->getDataArr();
    }

    public function increment($data)
    {
        return substr($data, 0, 3).'...';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * @return string
     */
    public function toJson(): string
    {
        header('Content-type: application/json;');

        return json_encode($this->response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->response;
    }
}
