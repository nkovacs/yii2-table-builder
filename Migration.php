<?php

namespace nkovacs\tablebuilder;

use yii\base\InvalidConfigException;

class Migration extends \yii\db\Migration
{
    /**
     * @var string|boolean|null table options used in createTable.
     * If true, this will be set to ENGINE=InnoDB if the db driver is mysql.
     * Set it to null to disable this behavior.
     */
    protected $tableOptions = true;

    /**
     * Initializes the migration.
     * This method will set tableOptions to use InnoDB if the db driver is mysql.
     */
    public function init()
    {
        parent::init();
        if ($this->tableOptions === true) {
            if ($this->db->driverName === 'mysql') {
                $this->tableOptions = 'ENGINE=InnoDB';
            } else {
                $this->tableOptions = '';
            }
        }
    }

    /**
     * Returns a foreign key name in the form "fk_$tableName__$columnName".
     * @return string
     */
    public function makeFkName($tableName, $columnName)
    {
        $tableName = $this->db->schema->getRawTableName($tableName);
        return 'fk_' . $tableName . '__' . $columnName;
    }

    /**
     * Returns a primary key name in the form "pk_$tableName".
     * This is used for compound primary keys.
     * @return string
     */
    public function makePkName($tableName)
    {
        $tableName = $this->db->schema->getRawTableName($tableName);
        return 'pk_' . $tableName;
    }

    /**
     * Builds tables specified in $tables.
     * Each element of $tables is a table definition array indexed by the table name.
     * The elements of a table definition can be simple column types or arrays.
     * Columns that are arrays are foreign keys, and should be in the following format:
     * ['column definition', 'ref table', 'ref column', 'delete' => 'on delete option', 'update' => 'on update option']
     * 'delete' and 'update' are optional, and default to null.
     * Referenced tables can be specified in any order, and can be tables that already exist.
     * Foreign keys are named automatically using makeFkName.
     * Compound primary keys can be specified using an array element in the table definition like this:
     * ['primary' => ['column1', 'column2']]
     * or
     * ['primary' => 'column1, column2']
     *
     * If an error occurs, build will attempt to return the database to a consistent state
     * by dropping all foreign keys and tables that have already been created.
     * @param array $tables array of tables to build.
     */
    public function build($tables)
    {
        $keys = [];
        $completedTables = [];
        $completedKeys = [];
        try {
            foreach ($tables as $tableName => $config) {
                $primary = false;
                $columns = [];
                foreach ($config as $columnName => $column) {
                    if (is_array($column)) {
                        if (array_key_exists('primary', $column)) {
                            if (is_array($column['primary'])) {
                                $primary = implode(',', $column['primary']);
                            } else {
                                $primary = $column['primary'];
                            }
                            continue;
                        }
                        if (!array_key_exists(0, $column)) {
                            throw new InvalidConfigException("Type missing in $tableName($columnName)");
                        }
                        if (!array_key_exists(1, $column)) {
                            throw new InvalidConfigException("Related table missing in $tableName($columnName)");
                        }
                        if (!array_key_exists(2, $column)) {
                            throw new InvalidConfigException("Related column missing in $tableName($columnName)");
                        }
                        $columns[$columnName] = $column[0];
                        $keys[] = [
                            'name' => self::makeFkName($tableName, $columnName),
                            'table' => $tableName,
                            'column' => $columnName,
                            'relTable' => $column[1],
                            'relColumn' => $column[2],
                            'delete' => isset($column['delete']) ? $column['delete'] : null,
                            'update' => isset($column['update']) ? $column['update'] : null,
                        ];
                    } else {
                        $columns[$columnName] = $column;
                    }
                }
                $this->createTable($tableName, $columns, $this->tableOptions);
                $completedTables[] = $tableName;
                if ($primary !== false) {
                    $this->addPrimaryKey($this->makePkName($tableName), $tableName, $primary);
                }
            }
            foreach ($keys as $key) {
                $this->addForeignKey(
                    $key['name'],
                    $key['table'],
                    $key['column'],
                    $key['relTable'],
                    $key['relColumn'],
                    $key['delete'],
                    $key['update']
                );
                $completedKeys[] = [$key['name'], $key['table']];
            }
        } catch (\Exception $e) {
            echo "\nCaught exception, cleaning up\n";
            try {
                foreach ($completedKeys as $key) {
                    $this->dropForeignKey($key[0], $key[1]);
                }
                foreach ($completedTables as $table) {
                    $this->dropTable($table);
                }
            } catch (\Exception $e) {
                echo "Failed to clean up.\n";
                echo $e->getMessage();
            }
            throw $e;
        }
    }

    /**
     * Drops tables specified in $tables.
     * Foreign keys defined in $tables are dropped before the tables themselves.
     * @param array $tables see build
     */
    public function teardown($tables)
    {
        foreach ($tables as $tableName => $config) {
            foreach ($config as $columnName => $column) {
                if (is_array($column) && !array_key_exists('primary', $column)) {
                    $this->dropForeignKey($this->makeFkName($tableName, $columnName), $tableName);
                }
            }
        }

        foreach ($tables as $tableName => $config) {
            $this->dropTable($tableName);
        }
    }
}
