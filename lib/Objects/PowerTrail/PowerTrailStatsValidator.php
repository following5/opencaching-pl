<?php

namespace lib\Objects\PowerTrail;

use lib\Objects\BaseObject;
use lib\Objects\GeoCache\GeoCacheLog;
use powerTrail\powerTrailBase;

class PowerTrailStatsValidator extends BaseObject
{

    public function __construct()
    {
        parent::__construct();
    }

    public static function objectType()
    {
        return self::OBJECT_TYPE_GEOPATH;
    }

    /**
     * Check the consistency of geopath counters or coordinates
     *
     * @param string $field - 'table.column' to check
     * @param boolean $repair - true => discrepancies will be repaired
     *
     * @return array|int
     *    - array of geopath ids with discrepancies for $repair = false
     *    - number of discrepancies for $repair = true 
     */

    public function validate($field, $repair)
    {
        switch ($field) {

            case 'PowerTrail.cacheCount':
                return $this->validateCounters('cacheCount', 'powerTrail_caches', $repair);
            case 'PowerTrail.conquestedCount':
                return $this->validateCounters('conquestedCount', 'PowerTrail_comments', $repair);

            case 'PowerTrail.points':
                return $this->validateCalculation('points', 'points', $repair);
            case 'PowerTrail.centerLatitude':
                return $this->validateCalculation('centerLatitude', 'avgLat', $repair);
            case 'PowerTrail.centerLongitude':
                return $this->validateCalculation('centerLongitude', 'avgLon', $repair);

            default:
                throw new \Exception('unknown field: '.$field);
        }
    }

    private function validateCounters($column, $sourceTable, $repair)
    {
        if ($sourceTable == 'PowerTrail_comments') {
            $condition = 'WHERE commentType = '.Log::TYPE_CONQUESTED.' AND deleted=0';
        } else {
            $condition = '';
        }

        $joinSource = '
            LEFT JOIN (
                SELECT PowerTrailId, COUNT(*) AS count
                FROM `'.$sourceTable.'`'.
                $condition.'
                GROUP BY PowerTrailId
            ) AS source ON PowerTrail.id = source.PowerTrailId';

        if ($repair) {
            $res = $this->db->simpleQuery('
                UPDATE PowerTrail '.$joinSource.'
                SET PowerTrail.`'.$column.'` = source.count
                WHERE PowerTrail.`'.$column.'` != source.count'
            );
            return $this->db->rowCount($res);
        } else {
            return $this->db->dbFetchOneColumnArray(
                $this->db->simpleQuery('
                    SELECT id FROM PowerTrail '.$joinSource.'
                    WHERE PowerTrail.`'.$column.'` != source.count
                    ORDER BY id'
                ),
                'id'
            );
        }
    }

    // Currently unusable, because for each cache the altitude is
    // refreshed from external provider. TODO: optimize that.

    private function validateCalculation($column, $sourceColumn, $repair)
    {
        $pts = $this->db->dbResultFetchAllAsDict(
            $this->db->simpleQuery(
                'SELECT id, `'.$column.'` FROM PowerTrail ORDER BY id'
            )
        );
        $result = ($repair ? 0 : []);

        foreach ($pts as $ptId => $value) {
            $caches = powerTrailBase::getPtCaches($ptId);
            $ptData = powerTrailBase::recalculateCenterAndPoints($caches);

            if ($ptData[$sourceColumn] != $value) {
                if ($repair) {
                    ++$result;
                    $this->db->multiVariableQuery('
                        UPDATE PowerTrail SET `'.$column.'` = :2 WHERE id = :1',
                        $ptId,
                        $ptData[$sourceColumn]
                    );
                } else {
                    $result[] = $ptId;
                }
            }
        }
        return $result;
    }

}
