<?php

namespace Vnet\Helpers;

class DB
{
    private static $sqlTypes = [
        'string' => [
            'CHAR',
            'VARCHAR',
            'TINYTEXT',
            'TEXT',
            'MEDIUMTEXT',
            'LONGTEXT',
            'BINARY',
            'VARBINARY',
            'TINYBLOB',
            'MEDIUMBLOB',
            'BLOB',
            'LONGBLOB',
            'JSON'
        ],
        'int' => [
            'TINYINT',
            'SMALLINT',
            'MEDIUMINT',
            'INT',
            'BIGINT'
        ],
        'float' => [
            'DECIMAL',
            'FLOAT',
            'DOUBLE'
        ],
        'bool' => [
            'BOOLEAN'
        ],
        'date' => [
            'DATETIME',
            'TIME',
            'YEAR'
        ]
    ];

    static function tableExists(string $tableName): bool
    {
        global $wpdb;

        $query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($tableName));
        $res = $wpdb->get_var($query);

        return $res == $tableName;
    }


    static function columnExists(string $table, string $colName): bool
    {
        global $wpdb;
        $res = $wpdb->get_results("SHOW COLUMNS FROM `{$table}` LIKE '{$colName}'", ARRAY_A);
        return ($res && !is_wp_error($res));
    }


    static function createTable(string $tableName, string ...$sqlColumns): string
    {
        global $wpdb;
        $query = "CREATE TABLE `{$tableName}` (" . implode(', ', $sqlColumns) . ")";
        $wpdb->query($query);
        return static::class;
    }


    static function addColIndex(string $tableName, string $colName): string
    {
        global $wpdb;
        $wpdb->query("ALTER TABLE `{$tableName}` ADD INDEX (`{$colName}`)");
        return static::class;
    }


    static function addColumn(string $tableName, string $colName, string $sqlType): string
    {
        global $wpdb;
        $wpdb->query("ALTER TABLE `$tableName` ADD `{$colName}` {$sqlType}");
        return static::class;
    }


    static function getResults(string $query): ?array
    {
        global $wpdb;
        return $wpdb->get_results($query, ARRAY_A);
    }


    static function getTableName(string $tableName): string
    {
        global $wpdb;
        $reg = "/^{$wpdb->prefix}/";
        if (preg_match($reg, $tableName)) {
            return $tableName;
        }
        return $wpdb->prefix . $tableName;
    }


    static function getColType(string $sqlColType): ?string
    {
        $sqlColType = trim(strtoupper($sqlColType));

        foreach (self::$sqlTypes as $type => $sqlTypes) {
            $regex = '/^(' . implode('|', $sqlTypes) . ')/';
            if (preg_match($regex, $sqlColType)) {
                return $type;
            }
        }

        return null;
    }


    // static function getDbColType(string $table, string $colName): ?string
    // {
    //     $table = self::getTableName($table);
    //     $res = self::getResults("SELECT `DATA_TYPE`, `CHARACTER_MAXIMUM_LENGTH`, `COLUMN_DEFAULT` FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$table}' AND COLUMN_NAME = '{$colName}' LIMIT 1");

    //     if (empty($res[0]['DATA_TYPE'])) {
    //         return null;
    //     }

    //     $value = '';

    //     if (!empty($res[0])) {
    //         if (!empty($res[0]['DATA_TYPE'])) {
    //             $value .= $res[0]['DATA_TYPE'];
    //         }
    //         if (!empty($res[0]['CHARACTER_MAXIMUM_LENGTH'])) {
    //             $value .= '(' . $res[0]['CHARACTER_MAXIMUM_LENGTH'] . ')';
    //         }
    //         if ($res[0]['COLUMN_DEFAULT'] === null) {
    //             file_put_contents(__DIR__ . '/_DEBUG_', print_r('IS NULL', true) . PHP_EOL, FILE_APPEND);
    //         }
    //     }
    //     $value = strtoupper($value);
    //     return strtoupper($value);
    // }


    // static function isUqualColType(string $table, string $colName, string  $colType)
    // {
    //     $colType = strtoupper(trim(preg_replace("/[\s]+/", '', $colType)));
    //     return $colType === self::getDbColType($table, $colName);
    // }


    static function update(string $table, array $data, array $where, $format = null, $whereFormat = null): ?int
    {
        global $wpdb;
        $table = self::getTableName($table);
        $res = $wpdb->update($table, $data, $where, $format, $whereFormat);
        return $res === false ? null : $res;
    }


    static function insert(string $table, array $data, $format = null): ?int
    {
        global $wpdb;
        $table = self::getTableName($table);
        $res = $wpdb->insert($table, $data, $format);
        return $res === false ? null : $res;
    }


    static function delete(string $table, array $where, $whereFormat = null): ?int
    {
        global $wpdb;
        $table = self::getTableName($table);
        $res = $wpdb->delete($table, $where, $whereFormat);
        return $res === false ? null : $res;
    }


    static function query(string $query)
    {
        global $wpdb;
        return $wpdb->query($query);
    }
}
