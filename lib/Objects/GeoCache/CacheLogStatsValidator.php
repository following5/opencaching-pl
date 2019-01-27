<?php

namespace lib\Objects\GeoCache;

use lib\Objects\BaseObject;

class CacheLogStatsValidator extends BaseObject
{

    public function __construct()
    {
        parent::__construct();
    }

    public static function objectType()
    {
        return self::OBJECT_TYPE_GEOCACHE_LOG;
    }

    /**
     * Check the consistency of log entry counters.
     *
     * @param string $field - 'table.column' to check
     * @param boolean $repair - true => discrepancies will be repaired
     *
     * @return array|int
     *    - array of log ids with discrepancies for $repair = false
     *    - number of discrepancies for $repair = true 
     */

    public function validate($field, $repair)
    {
        list($table, $column) = explode('.', $field);
        if ($table != 'cache_logs') {
            throw new \Exception('unknown table: '.$table);
        }
        if ($column == 'picturescount') {
            $sourceTable = 'pictures';
        } elseif ($column == 'mp3count') {
            $sourceTable = 'mp3';
        } else {
            throw new \Exception('unknown column: '.$column);
        }

        $joinSource = '
            LEFT JOIN (
                SELECT object_id, COUNT(*) AS count
                FROM '.$sourceTable.'
                WHERE object_type = 1
                GROUP BY object_id
            ) AS source ON cache_logs.id = object_id';

        if ($repair) {
            $res = $this->db->simpleQuery('
                UPDATE cache_logs '.$joinSource.'
                SET cache_logs.`'.$column.'` = source.count
                WHERE cache_logs.`'.$column.'` != source.count'
            );
            return $this->db->rowCount($res);
        } else {
            return $this->db->dbFetchOneColumnArray(
                $this->db->simpleQuery('
                    SELECT id FROM cache_logs '.$joinSource.'
                    WHERE cache_logs.`'.$column.'` != source.count
                    ORDER BY id'
                ),
                'id'
            );
        }
    }

}
