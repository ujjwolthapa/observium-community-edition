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

$colours      = "mixed";
$nototal      = (($width < 224) ? 1 : 0);
$unit_text    = "Count";
$rrd_filename = get_rrd_path($device, "app-bind-" . $app['app_id'] . "-ns-stats.rrd");

$array = [
  'Requestv4'     => ['descr' => "IPv4 requests", 'colour' => '006600'],
  'Requestv6'     => ['descr' => "IPv6 requests", 'colour' => '66cc66'],
  'ReqEdns0'      => ['descr' => "EDNS(0) requests", 'colour' => '9999ff'],
  'RespEDNS0'     => ['descr' => "EDNS(0) responses", 'colour' => '6666ff'],
  'ReqTSIG'       => ['descr' => "TSIG requests", 'colour' => 'ff9999'],
  'RespTSIG'      => ['descr' => "TSIG responses", 'colour' => 'ff6666'],
  'ReqSIG0'       => ['descr' => "SIG(0) requests", 'colour' => 'da70d6'],
  'RespSIG0'      => ['descr' => "responses with SIG(0) sent", 'colour' => '9932cc'],
  'ReqTCP'        => ['descr' => "TCP requests", 'colour' => 'ffd700'],
  'Response'      => ['descr' => "Responses sent", 'colour' => '999999'],
  'TruncatedResp' => ['descr' => "Truncated Responses", 'colour' => 'ff0000'],
];
$i     = 0;

if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $data) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $data['descr'];
        $rrd_list[$i]['ds']       = $ds;
        $rrd_list[$i]['colour']   = $data['colour'];
        $i++;
    }
} else {
    echo("file missing: $rrd_filename");
}

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF
