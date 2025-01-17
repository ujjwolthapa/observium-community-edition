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

$rrd_filename = get_rrd_path($device, "app-nfsd-" . $app['app_id'] . ".rrd");

$array = [
  "calls", "badcalls", "badclnt", "xdrcall"
];

$i = 0;

if (rrd_is_file($rrd_filename)) {
    foreach ($array as $name) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $name;
        $rrd_list[$i]['ds']       = 'rpc' . $name;
        $i++;
    }
} else {
    echo("file missing: $rrd_filename");
}

$colours   = "mixed";
$nototal   = 0;
$unit_text = "Rows";

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
