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

$rrd_filename = get_rrd_path($device, 'app-jvmoverjmx-' . $app["app_id"] . '.rrd');

$array = [
  'G1YoungGenCount' => ['descr' => 'G1 Young Gen Collection Count'],
  'ParNewCount'     => ['descr' => 'Par New Collection Count'],
  'CopyCount'       => ['descr' => 'Copy Collection Count'],
  'PSScavengeTime'  => ['descr' => 'PS Scavenge Collection Count'],
];

$i = 0;
if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $data) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $data['descr'];
        $rrd_list[$i]['ds']       = $ds;
        $i++;
    }
} else {
    echo("file missing: $rrd_filename");
}

$colours   = "mixed";
$nototal   = 1;
$unit_text = "Events";

#include($config['html_dir']."/includes/graphs/generic_multi_line.inc.php");
include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
