<?php
/**
 * /admin/scheduler/run.php
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
include("../../_includes/start-session.inc.php");
include("../../_includes/init.inc.php");

require_once(DIR_ROOT . "classes/Autoloader.php");
spl_autoload_register('DomainMOD\Autoloader::classAutoloader');

require DIR_ROOT . 'vendor/autoload.php';

$system = new DomainMOD\System();
$error = new DomainMOD\Error();
$conversion = new DomainMOD\Conversion();
$maint = new DomainMOD\Maintenance();
$schedule = new DomainMOD\Scheduler();
$time = new DomainMOD\Time();
$timestamp = $time->stamp();

include(DIR_INC . "head.inc.php");
include(DIR_INC . "config.inc.php");
include(DIR_INC . "config-demo.inc.php");
include(DIR_INC . "software.inc.php");
include(DIR_INC . "database.inc.php");

$system->authCheck($web_root);
$system->checkAdminUser($_SESSION['s_is_admin'], $web_root);

$id = $_GET['id'];

if ($demo_install != '1') {

    $query = "SELECT id, slug, expression, next_run, active
              FROM scheduler
              WHERE id = ?";
    $q = $conn->stmt_init();

    if ($q->prepare($query)) {

        $q->bind_param('i', $id);
        $q->execute();
        $q->store_result();
        $q->bind_result($temp_id, $temp_slug, $temp_expression, $temp_next_run, $temp_active);

        while ($q->fetch()) {

            $id = $temp_id;
            $slug = $temp_slug;
            $expression = $temp_expression;
            $next_run = $temp_next_run;
            $active = $temp_active;

        }

        $q->close();

    } else $error->outputSqlError($conn, "ERROR");

    if ($active == '1') {

        $cron = \Cron\CronExpression::factory($expression);
        $next_run = $cron->getNextRunDate()->format('Y-m-d H:i:s');

    } else {

        $next_run = '0000-00-00 00:00:00';

    }

    if ($slug == 'cleanup') {

        $schedule->isRunning($connection, $id);
        $maint->performCleanup($connection);
        $schedule->updateTime($connection, $id, $timestamp, $next_run, $active);
        $schedule->isFinished($connection, $id);

        $_SESSION['s_message_success'] .= "System Cleanup Performed";

    } elseif ($slug == 'expiration-email') {

        $email = new DomainMOD\Email();
        $schedule->isRunning($connection, $id);
        $email->sendExpirations($connection, $software_title, '0');
        $schedule->updateTime($connection, $id, $timestamp, $next_run, $active);
        $schedule->isFinished($connection, $id);

    } elseif ($slug == 'update-conversion-rates') {

        $schedule->isRunning($connection, $id);
        $sql_currency = "SELECT user_id, default_currency
                         FROM user_settings";
        $result_currency = mysqli_query($connection, $sql_currency);

        while ($row_currency = mysqli_fetch_object($result_currency)) {
            $conversion->updateRates($connection, $row_currency->default_currency, $row_currency->user_id);
        }
        $schedule->updateTime($connection, $id, $timestamp, $next_run, $active);
        $schedule->isFinished($connection, $id);

        $_SESSION['s_message_success'] .= "Conversion Rates Updated";

    } elseif ($slug == 'check-new-version') {

        $schedule->isRunning($connection, $id);
        $system->checkVersion($connection, $software_version);
        $schedule->updateTime($connection, $id, $timestamp, $next_run, $active);
        $schedule->isFinished($connection, $id);

        $_SESSION['s_message_success'] .= "No Upgrade Available";

    } elseif ($slug == 'data-warehouse-build') {

        $dw = new DomainMOD\DwBuild();
        $schedule->isRunning($connection, $id);
        $dw->build($connection);
        $schedule->updateTime($connection, $id, $timestamp, $next_run, $active);
        $schedule->isFinished($connection, $id);

    } elseif ($slug == 'domain-queue') {

        $queue = new DomainMOD\DomainQueue();
        $schedule->isRunning($connection, $id);
        $queue->processQueueList($connection);
        $queue->processQueueDomain($connection);
        $schedule->updateTime($connection, $id, $timestamp, $next_run, $active);
        $schedule->isFinished($connection, $id);

    }

} else {

    if ($demo_install == '1') {

        $_SESSION['s_message_danger'] .= "Tasks Disabled in Demo Mode";

    } else {

        $_SESSION['s_message_success'] .= "Task Run";

    }

}

header("Location: index.php");
exit;
