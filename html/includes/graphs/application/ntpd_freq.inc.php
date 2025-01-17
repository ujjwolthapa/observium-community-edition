<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$scale_min       = 0;
$ds              = "frequency";
$colour_area     = "F6F6F6";
$colour_line     = "B3D0DB";
$colour_area_max = "FFEE99";
$graph_max       = 100;
$unit_text       = "Frequency";
$ntpdserver_rrd  = get_rrd_path($device, "app-ntpd-server-" . $app['app_id'] . ".rrd");
$ntpdclient_rrd  = get_rrd_path($device, "app-ntpd-client-" . $app['app_id'] . ".rrd");

if (rrd_is_file($ntpdclient_rrd)) {
    $rrd_filename = $ntpdclient_rrd;
}

if (rrd_is_file($ntpdserver_rrd)) {
    $rrd_filename = $ntpdserver_rrd;
}

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

// EOF
