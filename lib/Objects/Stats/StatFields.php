<?php

namespace lib\Objects\Stats;

/**
 * Returns information about where calculated statistics are
 * stored in the database, and which class validates them.
 */

class StatFields
{
    public static function fields()
    {
        static $fields;

        if (!$fields) {
            $fields = [

                'caches.founds',
                'caches.notfounds',
                'caches.notes',
                'caches.last_found',
                'caches.watcher',
                'caches.ignorer_count',
                'caches.topratings',
                'caches.votes',
                'caches.score',
                'caches.picturescount',
                'caches.mp3count',
                'caches.desc_languages',

                'cache_logs.picturescount',
                'cache_logs.mp3count',

                'PowerTrail.cacheCount',
                'PowerTrail.conquestedCount',

                // Caclulcation of these currently is way too slow for validation:
                // 'PowerTrail.points',
                // 'PowerTrail.centerLatitude',
                // 'PowerTrail.centerLongitude',

                // not implemented yet
                // 'powertrail_progress.founds',

                'user.hidden_count',
                'user.founds_count',
                'user.notfounds_count',
                'user.log_notes_count',
                'user.cache_ignores',  // obsolete

                'user_finds.number',
            ];
        }
        return $fields;
    }

    public static function validatorClass($field)
    {
        if (!in_array($field, self::fields())) {
            throw new \Exception('unknown stats field: '.$field);
        }
        $table = explode('.', $field)[0];

        switch ($table)
        {
            case 'caches':
                return '\lib\Objects\GeoCache\CacheStatsValidator';

            case 'cache_logs':
                return '\lib\Objects\GeoCache\CacheLogStatsValidator';

            case 'PowerTrail':
            case 'powertrail_progress':
                return '\lib\Objects\PowerTrail\PowerTrailStatsValidator';

            case 'user':
            case 'user_finds':
                return '\lib\Objects\User\UserStatsValidator';

            default:
                throw new \Exception('validator not defined for table '.$table);
        }
    }

}
