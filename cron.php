<?php
/**
 * /cron.php
 *
 * This file is part of DomainMOD, an open source domain and internet asset manager.
 * Copyright (c) 2010-2017 Greg Chetcuti <greg@chetcuti.com>
 *
 * Project: http://domainmod.org   Author: http://chetcuti.com
 *
 * DomainMOD is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * DomainMOD is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with DomainMOD. If not, see
 * http://www.gnu.org/licenses/.
 *
 */
?>
<?php
include("_includes/init.inc.php");
include("_includes/software.inc.php");

require_once(DIR_ROOT . "classes/Autoloader.php");
spl_autoload_register('DomainMOD\Autoloader::classAutoloader');

require DIR_ROOT . 'vendor/autoload.php';

$system = new DomainMOD\System();
$error = new DomainMOD\Error();
$maint = new DomainMOD\Maintenance();
$conversion = new DomainMOD\Conversion();
$schedule = new DomainMOD\Scheduler();
$time = new DomainMOD\Time();

include(DIR_INC . "head.inc.php");
include(DIR_INC . "config.inc.php");
include(DIR_INC . "config-demo.inc.php");
include(DIR_INC . "database.inc.php");

if ($demo_install != '1') {

    $sql = "UPDATE scheduler
            SET is_running = '0'";
    $result = mysqli_query($connection, $sql);

    $sql = "SELECT id, `name`, slug, expression, active
            FROM scheduler
            WHERE active = '1'
              AND is_running = '0'
              AND next_run <= '" . $time->stamp() . "'";
    $result = mysqli_query($connection, $sql);

    while ($row = mysqli_fetch_object($result)) {

        $cron = \Cron\CronExpression::factory($row->expression);
        $next_run = $cron->getNextRunDate()->format('Y-m-d H:i:s');

        if ($row->slug == 'cleanup') {

            $schedule->isRunning($connection, $row->id);
            $maint->performCleanup($connection);
            $schedule->updateTime($connection, $row->id, $time->stamp(), $next_run, $row->active);
            $schedule->isFinished($connection, $row->id);

        } elseif ($row->slug == 'expiration-email') {

            $email = new DomainMOD\Email();
            $schedule->isRunning($connection, $row->id);
            $email->sendExpirations($connection, $software_title, '1');
            $schedule->updateTime($connection, $row->id, $time->stamp(), $next_run, $row->active);
            $schedule->isFinished($connection, $row->id);

        } elseif ($row->slug == 'update-conversion-rates') {

            $schedule->isRunning($connection, $row->id);
            $sql_currency = "SELECT user_id, default_currency
                             FROM user_settings";
            $result_currency = mysqli_query($connection, $sql_currency);

            while ($row_currency = mysqli_fetch_object($result_currency)) {

                $conversion->updateRates($connection, $row_currency->default_currency, $row_currency->user_id);

            }
            $schedule->updateTime($connection, $row->id, $time->stamp(), $next_run, $row->active);
            $schedule->isFinished($connection, $row->id);

        } elseif ($row->slug == 'check-new-version') {

            $schedule->isRunning($connection, $row->id);
            $system->checkVersion($connection, $software_version);
            $schedule->updateTime($connection, $row->id, $time->stamp(), $next_run, $row->active);
            $schedule->isFinished($connection, $row->id);

        } elseif ($row->slug == 'data-warehouse-build') {

            $dw = new DomainMOD\DwBuild();
            $schedule->isRunning($connection, $row->id);
            $dw->build($connection);
            $schedule->updateTime($connection, $row->id, $time->stamp(), $next_run, $row->active);
            $schedule->isFinished($connection, $row->id);

        } elseif ($row->slug == 'domain-queue') {

            $queue = new DomainMOD\DomainQueue();
            $schedule->isRunning($connection, $row->id);
            $queue->processQueueList($connection);
            $queue->processQueueDomain($connection);
            $schedule->updateTime($connection, $row->id, $time->stamp(), $next_run, $row->active);
            $schedule->isFinished($connection, $row->id);

        }

    }

}
