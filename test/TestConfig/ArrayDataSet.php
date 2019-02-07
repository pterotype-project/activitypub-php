<?php
namespace ActivityPub\Test\TestConfig;

use InvalidArgumentException;

class ArrayDataSet extends \PHPUnit_Extensions_Database_DataSet_AbstractDataSet
{
    /**
     * @var array
     */
    protected $tables = [];

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        foreach ($data as $tableName => $rows) {
            $columns = [];
            if (isset($rows[0])) {
                $columns = array_keys($rows[0]);
            }

            $metaData = new \PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData($tableName, $columns);
            $table = new \PHPUnit_Extensions_Database_DataSet_DefaultTable($metaData);

            foreach ($rows as $row) {
                $table->addRow($row);
            }
            $this->tables[$tableName] = $table;
        }
    }

    protected function createIterator($reverse = false)
    {
        return new \PHPUnit_Extensions_Database_DataSet_DefaultTableIterator($this->tables, $reverse);
    }

    public function getTable($tableName)
    {
        if (!isset($this->tables[$tableName])) {
            throw new InvalidArgumentException("$tableName is not a table in the current database.");
        }

        return $this->tables[$tableName];
    }
}
