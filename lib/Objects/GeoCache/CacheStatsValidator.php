<?php

namespace lib\Objects\GeoCache;

use lib\Objects\BaseObject;

class CacheStatsValidator extends BaseObject
{

    public function __construct()
    {
        parent::__construct();
    }

    public static function objectType()
    {
        return self::OBJECT_TYPE_GEOCACHE;
    }

    /**
     * Check the consistency of geocache statistics, counters etc.
     *
     * @param string $field - 'table.column' to check
     * @param boolean $repair - true => discrepancies will be repaired
     *
     * @return array|int
     *    - array of cache_ids with discrepancies for $repair = false
     *    - number of discrepancies for $repair = true 
     */

    public function validate($field, $repair)
    {
        list($table, $column) = explode('.', $field);
        if ($table != 'caches') {
            throw new \Exception('unknown table: '.$table);
        }

        switch ($column) {
            case 'founds':
                return
                    $this->validateLogStats($column, GeoCacheLog::LOGTYPE_FOUNDIT, $repair) +
                    $this->validateLogStats($column, GeoCacheLog::LOGTYPE_ATTENDED, $repair);
            case 'notfounds':
                return
                    $this->validateLogStats($column, GeoCacheLog::LOGTYPE_DIDNOTFIND, $repair) +
                    $this->validateLogStats($column, GeoCacheLog::LOGTYPE_WILLATTENDED, $repair);
            case 'notes':
                return $this->validateLogStats($column, GeoCacheLog::LOGTYPE_COMMENT, $repair);
            case 'last_found':
                return
                    $this->validateLogStats($column, GeoCacheLog::LOGTYPE_FOUNDIT, $repair) +
                    $this->validateLogStats($column, GeoCacheLog::LOGTYPE_ATTENDED, $repair);

            case 'watcher':
                return $this->validateField($column, 'cache_watches', $repair);
            case 'ignorer_count':
                return $this->validateField($column, 'cache_ignore', $repair);

            case 'topratings':
                return $this->validateField($column, 'cache_rating', $repair);
            case 'votes':
            case 'score':
                return $this->validateField(
                    $column,
                    'scores',
                    $repair,
                    'IF(COUNT(*) = 0, 0, SUM(score) / COUNT(*))'
                );

            case 'picturescount':
                return $this->validateField($column, 'pictures', $repair);
            case 'mp3count':
                return $this->validateField($column, 'mp3', $repair);

            case 'desc_languages':
                return $this->validateField(
                    $column,
                    'cache_desc',
                    $repair,
                    'GROUP_CONCAT(language)'
                );

            default:
                throw new \Exception('unknown caches column: '.$column);
        }
    }

    private function validateLogStats($column, $logType, $repair)
    {
        if ($column == 'last_found') {
            $logsValueExpression = 'MAX(`date`)';
        } else {
            $logsValueExpression =  'COUNT(*)';
        }

        if (in_array($logType, [GeoCacheLog::LOGTYPE_ATTENDED, GeoCacheLog::LOGTYPE_WILLATTENDED])) {
            $cacheTypeCond = 'AND caches.type = ' . GeoCache::TYPE_EVENT;
        } elseif (in_array($logType, [GeoCacheLog::LOGTYPE_FOUNDIT, GeoCacheLog::LOGTYPE_DIDNOTFIND])) {
            $cacheTypeCond = 'AND caches.type != ' . GeoCache::TYPE_EVENT;
        } else {
            $cacheTypeCond = '';
        }

        $joinLogs = '
            LEFT JOIN (
                SELECT cache_id, '.$logsValueExpression.' AS value
                FROM cache_logs
                WHERE type = :1 AND deleted = 0
                GROUP BY cache_id
            ) AS logs USING (cache_id)';
        
        if ($repair) {
            $res = $this->db->multiVariableQuery('
                UPDATE caches '.$joinLogs.'
                SET caches.`'.$column.'` = logs.value
                WHERE caches.`'.$column.'` != logs.value '.$cacheTypeCond,
                $logType
            );
            return $this->db->rowCount($res);
        } else {
            return $this->db->dbFetchOneColumnArray(
                $this->db->multiVariableQuery('
                    SELECT cache_id FROM caches '.$joinLogs.'
                    WHERE caches.`'.$column.'` != logs.value '.$cacheTypeCond.'
                    ORDER BY cache_id',
                    $logType
                ),
                'cache_id'
            );
        }            
    }

    private function validateField(
        $column,
        $sourceTable,
        $repair,
        $sourceValueExpression = 'COUNT(*)'
    ) {
        if ($column == 'picturescount' || $column == 'mp3count') {
            $joinSource = '
                LEFT JOIN (
                    SELECT object_id, COUNT(*) AS value
                    FROM `'.$sourceTable.'`
                    WHERE object_type = 2
                    GROUP BY object_id
                ) AS source ON object_id = cache_id';
        } else {
            $joinSource = '
                LEFT JOIN (
                    SELECT cache_id, '.$sourceValueExpression.' AS value
                    FROM `'.$sourceTable.'`
                    GROUP BY cache_id
                ) AS source USING (cache_id)';
        }

        if ($repair) {
            $res = $this->db->simpleQuery('
                UPDATE caches '.$joinSource.'
                SET caches.`'.$column.'` = source.value
                WHERE caches.`'.$column.'` != IFNULL(source.value, 0)'
                // Remember that 0 = '' in SQL, so this works also for desc_languages.
            );
            return $this->db->rowCount($res);
        } else {
            return $this->db->dbFetchOneColumnArray(
                $this->db->simpleQuery('
                    SELECT cache_id FROM caches '.$joinSource.'
                    WHERE caches.`'.$column.'` != IFNULL(source.value, 0)
                    ORDER BY cache_id' 
                ),
                'cache_id'
            );
        }
    }

}
