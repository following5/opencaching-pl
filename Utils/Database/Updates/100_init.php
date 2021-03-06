<?php

namespace Utils\Database\Updates;

return new class extends UpdateScript
{
    public function getProperties()
    {
        return [
            'uuid' => '1ED22771-73F1-C522-C9AB-D8AD13D5D60A',  // do not change.
            'run' => 'auto',
        ];
    }

    public function run()
    {
        $this->develCleanup();
        $this->adjustTables();

        // Some trigger definitions reference procedures (but not vice versa)
        // => do the procedures first:

        $this->updateProcedures();
        $this->adjustTriggers();
    }

    /**
     * Level all table differences between OC site databases as of December 2018,
     * with two exceptions:
     *
     *   - Table transaction_test at PL, NL, RO sites - is it in use? 
     *   - Column user.cog_note at RO site - is it in use?
     */
    private function adjustTables()
    {
        $this->db->addIndexIfNotExists('admin_user_notes', 'user_id'); // NL
        $this->db->addForeignKeyIfNotExists('admin_user_notes', 'admin_id', 'user', 'user_id'); // NL
        $this->db->dropIndexIfExists('approval_status', 'date_approval_2'); // NL

        $this->db->dropIndexIfExists('badge_levels', 'badge_id'); // PL; is redundant to primary key
        $this->db->addIndexIfNotExists('badge_levels', 'level'); // all but PL
        $this->db->dropIndexIfExists('badge_user', 'user_id_2'); // PL; is redundant to primary key
        $this->db->addIndexIfNotExists('badge_user', 'badge_id'); // all but PL
        $this->db->updateColumnComment('badges', 'cfg_show_positions', ' - none, L - list, M - map'); // PL, RO

        $this->db->updateColumnComment('CACHE_ACCESS_LOGS', 'source', 'B - browser - main opencaching site, M - mobile, O - okapi'); // NL
        $this->db->updateColumnType('CACHE_ACCESS_LOGS', 'user_agent', 'text DEFAULT NULL'); // all but PL
        $this->db->dropIndexIfExists('CACHE_ACCESS_LOGS', 'event_date_2'); // NL, UK

        $this->db->updateColumnComment('cache_desc', 'desc_html', 'Format in which `desc` column is encoded: 0 - DO NOT USE (unknown format based on HTML); 1 - unsafe HTML (needs to be purified before it is included on a HTML page); 2 - safe HTML (may be included "as is" on HTML pages, without any further processing).'); // NL
        $this->db->updateColumnComment('cache_desc', 'desc_htmledit', 'Unused'); // NL
        $this->db->updateColumnType('cache_logs', 'text_html', 'tinyint(1) NOT NULL DEFAULT 0'); // NL
        $this->db->addIndexIfNotExists('cache_moved', 'log_id'); // NL
        $this->db->updateColumnType('cache_notes', 'date', 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP'); // RO
        $this->db->addIndexIfNotExists('cache_npa_areas', 'parki_id'); // all but PL
        $this->db->dropIndexIfExists('caches', 'wp_oc_2'); // NL
        $this->db->dropIndexIfExists('caches', 'date_hidden_2'); // NL
        $this->db->dropIndexIfExists('caches', 'founds_2'); // NL
        $this->db->updateColumnType('caches', 'wp_gc', 'varchar(7) NOT NULL'); // RO
        $this->db->updateColumnType('caches', 'wp_nc', 'varchar(6) NOT NULL'); // RO
        $this->db->updateColumnType('caches', 'wp_ge', 'varchar(7) NOT NULL'); // RO
        $this->db->updateColumnType('caches', 'wp_oc', 'varchar(6) DEFAULT NULL'); // RO
        $this->db->updateColumnType('caches', 'wp_tc', 'varchar(7) NOT NULL'); // RO

        $this->db->addIndexIfNotExists('email_schemas', 'deleted'); // UK
        $this->db->dropIndexIfExists('email_schemas', 'deleted_2'); // PL
        $this->db->dropIndexIfExists('email_user', 'date_generated_2'); // NL
        $this->db->dropIndexIfExists('logentries', 'logtime_2'); // NL
        $this->db->updateColumnType('news', 'date_publication', 'datetime NOT NULL DEFAULT CURRENT_TIMESTAMP'); // RO
        $this->db->updateColumnType('npa_areas', 'shape', 'geometry NOT NULL'); // RO
        $this->db->dropColumnIfExists('notify_waiting', 'type'); // NL
        $this->db->addIndexIfNotExists('opensprawdzacz', 'cache_id'); // all but PL
        $this->db->addIndexIfNotExists('parkipl', 'id'); // all but PL

        $this->db->addIndexIfNotExists('pictures', 'object_type'); // NL, RO
        $this->db->addIndexIfNotExists('pictures', 'display'); // NL, RO
        $this->db->addIndexIfNotExists('pictures', 'unknown_format'); // NL, RO
        $this->db->addIndexIfNotExists('pictures', 'seq'); // NL, RO
        $this->db->addIndexIfNotExists('pictures', 'date_created'); // NL, RO

        $this->db->dropIndexIfExists('PowerTrail', 'status_2'); // NL, UK
        $this->db->dropIndexIfExists('PowerTrail_actionsLog', 'PowerTrailId_2'); // NL, UK
        $this->db->dropIndexIfExists('PowerTrail_cacheCandidate', 'cacheId_2'); // NL, RO
        $this->db->dropIndexIfExists('powerTrail_caches', 'cacheId_2'); // all
        $this->db->dropIndexIfExists('powerTrail_caches', 'cacheId_3'); // all but PL
        $this->db->addIndexIfNotExists('powerTrail_caches', 'PowerTrailId'); // all but PL
        $this->db->updateColumnType('PowerTrail_comments', 'deleted', 'tinyint(1) NOT NULL', ''); // all but PL
        $this->db->addIndexIfNotExists('PowerTrail_comments', 'PowerTrailId');  // NL
        $this->db->addIndexIfNotExists('PowerTrail_comments', 'commentType');  // all but PL
        $this->db->addIndexIfNotExists('PowerTrail_comments', 'deleted');  // all but PL
        $this->db->addIndexIfNotExists('PowerTrail_comments', 'logDateTime');  // all but PL
        $this->db->addIndexIfNotExists('PowerTrail_comments', 'userId');  // all but PL
        $this->db->dropIndexIfExists('PowerTrail_owners', 'PowerTrailId_2');  // all
        $this->db->dropIndexIfExists('PowerTrail_owners', 'PowerTrailId_3');  // all but PL

        $this->db->updateColumnComment('reports', 'note', ''); // UK
        $this->db->dropIndexIfExists('reports', 'status_2'); // NL
        $this->db->dropIndexIfExists('reports', 'type_2'); // PL
        $this->db->dropIndexIfExists('reports', 'responsible_id_2'); // PL

        // remove statpic_text default - has been moved from DB to OcConfig
        $this->db->updateColumnType('user', 'statpic_text', 'varchar(30) NOT NULL');

        $this->db->dropColumnIfExists('sys_logins', 'success'); // all but NL

        $this->db->dropColumnIfExists('user', 'country'); // NL
        $this->db->dropColumnIfExists('user', 'get_bulletin'); // NL
        $this->db->dropColumnIfExists('user', 'hide_flag'); // NL
        $this->db->dropColumnIfExists('user', 'new_pw_date'); // NL
        $this->db->addIndexIfNotExists('user', 'date_created'); // NL
        $this->db->addIndexIfNotExists('user', 'is_active_flag'); // NL
        $this->db->addIndexIfNotExists('user', 'last_login'); // NL

        $this->db->dropIndexIfExists('user_neighbourhoods', 'user_id'); // all; is redundant to user_id_2
        $this->db->addForeignKeyIfNotExists('user_neighbourhoods', 'user_id', 'user', 'user_id'); // NL
        $this->db->addForeignKeyIfNotExists('user_preferences', 'user_id', 'user', 'user_id'); // NL
        $this->db->addForeignKeyIfNotExists('user_settings', 'user_id', 'user', 'user_id'); // NL

        $this->db->addIndexIfNotExists('waypoints', 'opensprawdzacz'); // all but PL

        $this->updateTranslations('de'); // adds columns to all translation tables at PL, RO, UK
        $this->updateTranslations('fr'); // adds column to waypoint_type table at PL, RO, UK
        $this->updateTranslations('ro'); // adds columns to all translation tables at PL, UK

        $this->updateTranslations('pl');
        $this->updateTranslations('en');
        $this->updateTranslations('nl');

        $this->db->updateColumnComment('cache_type', 'sort', 'This also is the translation ID number; see I18n::getIdColumnName()');
    }

    /**
     * We don't know the procedure definitions at least at the RO site.
     * Rewrite all procedures based in the PL definitions (checked against
     * sqlAlters), to make sure that all are up-to-date.
     * Note that currently there are no DB *functions* in OCPL database.
     */
    public function updateProcedures()
    {
        $this->db->createOrReplaceProcedure(
            'nop',
            [],
            "BEGIN END"
        );

        $this->db->createOrReplaceProcedure(
            'inc_powertrail_progress',
            ['IN `user_id` INT(11)', 'IN `cache_id` INT(11)'],
            "
            BEGIN
                DECLARE pt_id int(11) DEFAULT NULL;

                SELECT PowerTrailId INTO pt_id
                FROM powerTrail_caches WHERE cacheId = cache_id
                LIMIT 1;

                IF pt_id IS NOT NULL THEN
                    INSERT INTO powertrail_progress (user_id, pt_id, founds) VALUES (user_id, pt_id, 1)
                    ON DUPLICATE KEY UPDATE founds = founds + 1;
                END IF;
            END"
        );

        $this->db->createOrReplaceProcedure(
            'dec_powertrail_progress',
            ['IN `p_user_id` INT(11)', 'IN `p_cache_id` INT(11)'],
            "
            BEGIN
                DECLARE p_pt_id int(11) DEFAULT NULL;

                SELECT PowerTrailId INTO p_pt_id
                FROM powerTrail_caches WHERE cacheId = p_cache_id
                LIMIT 1;

                IF p_pt_id IS NOT NULL THEN
                    UPDATE powertrail_progress SET founds = founds - 1
                    WHERE user_id = p_user_id AND pt_id = p_pt_id AND founds > 0;
                END IF;
            END"
        );

        $this->db->createOrReplaceProcedure(
            'inc_logs_stats',
            ['IN `type` INT(11)', 'IN `user_id` INT(11)', 'IN `cache_id` INT(11)'],
            "
            BEGIN
                CASE `type`
                    WHEN 1 THEN -- FOUND
                        CALL inc_powertrail_progress(user_id, cache_id);
                    ELSE
                        CALL nop();
                END CASE;
            END"
        );

        $this->db->createOrReplaceProcedure(
            'dec_logs_stats',
            ['IN `type` INT(11)', 'IN `user_id` INT(11)', 'IN `cache_id` INT(11)'],
            "
            BEGIN
                CASE `type`
                    WHEN 1 THEN -- FOUND
                        CALL dec_powertrail_progress(user_id, cache_id);
                    ELSE
                        CALL nop();
                END CASE;
            END"
        );

        $this->db->createOrReplaceProcedure(
            'inc_cache_watchers',
            ['IN `p_cache_id` INT(11)'],
            "
            BEGIN
                UPDATE caches SET watcher = watcher + 1
                WHERE `p_cache_id` = `cache_id`
                LIMIT 1;
            END"
        );

        $this->db->createOrReplaceProcedure(
            'dec_cache_watchers',
            ['IN `p_cache_id` INT(11)'],
            "
            BEGIN
                UPDATE caches SET watcher = watcher - 1
                WHERE `p_cache_id` = `cache_id` AND watcher > 0
                LIMIT 1;
            END"
        );
    }

    public function adjustTriggers()
    {
        // Add missing triggers at NL and RO

        $this->db->createOrReplaceTrigger(
            'cache_logs_insert',
            "AFTER INSERT ON cache_logs
            FOR EACH ROW BEGIN
                IF NEW.`deleted` = 0 THEN
                    -- new, not-deleted log is now added
                    CALL inc_logs_stats(NEW.`type`, NEW.`user_id`, NEW.`cache_id`);
                END IF;
            END"
        );

        $this->db->createOrReplaceTrigger(
            'cache_logs_update',
            "AFTER UPDATE ON cache_logs
            FOR EACH ROW BEGIN
                IF OLD.`deleted` = 0 AND NEW.`deleted` = 0 THEN
                    -- update of active log
                    IF OLD.`type` <> NEW.`type` THEN
                        CALL dec_logs_stats(OLD.`type`, OLD.`user_id`, OLD.`cache_id`);
                        CALL inc_logs_stats(NEW.`type`, NEW.`user_id`, NEW.`cache_id`);
                    END IF;

                ELSEIF OLD.`deleted` = 1 AND NEW.`deleted` = 0 THEN
                    -- log UNDELETE
                    CALL inc_logs_stats(NEW.`type`, NEW.`user_id`, NEW.`cache_id`);

                ELSEIF OLD.`deleted` = 0 AND NEW.`deleted` = 1 THEN
                    -- log DELETE
                    CALL dec_logs_stats(OLD.`type`, OLD.`user_id`, OLD.`cache_id`);

                ELSE
                    -- do NOTHING - update of removed log without status change
                    CALL nop();
                END IF;
            END"
        );

        // Replace triggers with different syntax (case, spaces, newlines ...)
        // at different sites, to make them all identical.

        $this->db->createOrReplaceTrigger(
            'cl_insert',
            "AFTER INSERT ON cache_logs
            FOR EACH ROW BEGIN
                IF (NEW.deleted = 0 AND NEW.type = 1) THEN
                    IF EXISTS (
                        SELECT 1
                        FROM user_finds
                        WHERE date = DATE(NEW.date)
                        AND user_id = NEW.user_id
                    ) THEN
                        UPDATE user_finds SET number = number + 1
                        WHERE date = DATE(NEW.date) AND user_id = NEW.user_id;
                    ELSE
                        INSERT INTO user_finds (date, user_id, number)
                        VALUES (NEW.date, NEW.user_id, 1);
                    END IF;
                END IF;
            END"
        );

        $this->db->createOrReplaceTrigger(
            'cl_update',
            "AFTER UPDATE ON cache_logs
            FOR EACH ROW BEGIN

                IF (OLD.type = 1) THEN
                    IF (NEW.deleted = 1 AND OLD.deleted = 0) OR
                        NEW.type <> 1 OR
                        DATE(NEW.date) <> DATE(OLD.date)
                    THEN
                        IF EXISTS (
                            SELECT 1 FROM user_finds
                            WHERE date = DATE(OLD.date) AND user_id = NEW.user_id
                        ) THEN
                            UPDATE user_finds SET number = number - 1
                            WHERE date = DATE(OLD.date) AND user_id = NEW.user_id;
                        END IF;
                    END IF;
                END IF;

                IF (NEW.deleted = 0 AND NEW.type = 1) THEN
                    IF (OLD.deleted = 1 OR
                        OLD.type <> 1 OR
                        DATE(NEW.date) <> DATE(OLD.date)
                    ) THEN
                        IF EXISTS (
                            SELECT 1 FROM user_finds
                            WHERE date = DATE(NEW.date) AND user_id = NEW.user_id
                        ) THEN
                            UPDATE user_finds SET number = number + 1
                            WHERE date = DATE(NEW.date) AND user_id = NEW.user_id;
                        ELSE
                            INSERT INTO user_finds (date, user_id, number)
                            VALUES (NEW.date, NEW.user_id, 1);
                        END IF;
                    END IF;
                END IF;

            END"
        );
    }

    public function rollback()
    {
        $this->db->updateColumnType('user', 'statpic_text', "varchar(30) NOT NULL DEFAULT 'Opencaching'");
    }

    /**
     * Cleanup develsite leftovers of DB versioning development
     */
    private function develCleanup()
    {
        $this->db->dropTableIfExists('db_updates');
        $this->db->dropTableIfExists('db_updates__');
    }
};
