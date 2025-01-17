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

$scale_min    = 0;
$colours      = "mixed";
$nototal      = (($width < 224) ? 1 : 0);
$unit_text    = "Queries/sec";
$rrd_filename = get_rrd_path($device, "app-powerdns-" . $app['app_id'] . ".rrd");
$array        = [
  'q_udpAnswers' => ['descr' => 'UDP Answers', 'colour' => '336699FF', 'invert' => 1],
  'q_udpQueries' => ['descr' => 'UDP Queries', 'colour' => '6699CCFF', 'invert' => 0],
  'q_tcpAnswers' => ['descr' => 'TCP Answers', 'colour' => '008800FF', 'invert' => 1],
  'q_tcpQueries' => ['descr' => 'TCP Queries', 'colour' => '00FF00FF', 'invert' => 0],
];

$i = 0;

if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $data) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $data['descr'];
        $rrd_list[$i]['ds']       = $ds;
        $rrd_list[$i]['colour']   = $data['colour'];
        $rrd_list[$i]['invert']   = $data['invert'];
        $i++;
    }
} else {
    echo("file missing: $rrd_filename");
}

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
