<?php
use Controllers\Cron\Jobs\Job;
use lib\Objects\User\User;
use lib\Objects\User\MultiUserQuery;
use lib\Objects\Admin\AdminNote;
use Utils\Log\Log;

class DbCleanupJob extends Job
{
    public function run()
    {
        $settings = $this->ocConfig->getCronjobSettings('DbCleanup');

        foreach (Logs::getLogs() as $log) {
            $months = $settings['delete_'.$log.'_months'];
            if ($months > 0) {
                Logs::cleanup($log, $months);
            }
        }
        unset($months);

        $days = $settings['delete_nonActivatedUsers_days'];
        if ($days > 0) {
            foreach (
                MultiUserQuery::getNotActivatedUserIds($days) as $userId
            ) {
                User::delete($userId);
            }
        }
        unset($days);

        $disableInactiveMonths = $settings['disable_inactiveUsers_months'];
        if ($disableInactiveMonths > 0) {
            foreach (
                MultiUserQuery::getUserIdsWithoutSubmittedContent(true, $disableInactiveMonths)
                as $userId
            ) {
                UserAdmin::setBanStatus($userId, true);
                AdminNote::addAdminNote(-1, $userId, true, AdminNote::BAN);
            }
        }

        $deleteInactiveMonths = $settings['delete_inactiveDisabledUsers_months'];
        if ($deleteInactiveMonths > 0) {
            if ($deleteInactiveMonths <= $disableInactiveMonths) {

                // Disabling and deleting inactive users is a two-step process.
                // Therefore the timespan for deleting must be > timespan for disable.

                throw new \Exception(
                    "invalid delete_inactiveDisabledUsers_months setting\n".
                    "(must be greater than disable_inactiveUsers_months)"
                );
            }
            foreach (
                MultiUserQuery::getUserIdsWithoutSubmittedContent(false, $deleteInactiveMonths)
                as $userId
            ) {
                User::delete($userId);
            }
        }
    }
}
