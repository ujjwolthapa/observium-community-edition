#!/usr/bin/env php
<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage scripts
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2022 Observium Limited
 *
 */

chdir(dirname($argv[0]).'/..');

$options = getopt("da");
if (isset($options['d'])) { array_shift($argv); } // for compatibility
if (isset($options['a'])) { array_shift($argv); } // for compatibility

include_once("includes/sql-config.inc.php");

//$config['rancid_version'] = '3.7.1';

// Detect configured version
if (isset($config['rancid_version'])) {
  // Rancid version set manually
  $rancid_config_version = ltrim($config['rancid_version'], 'vV');
  list($rancid_config_base) = explode('.', $rancid_config_version);
  // $rancid_message = "Used RANCID version configured manually in \$config['rancid_version']: $rancid_version";
} else {
  // Dummy version for compare
  $rancid_config_version = 0;
  $rancid_config_base = 0;
}

// Detect locally installed version
if ($rancid_cmd = external_exec('which rancid-run')) {
  list(, $rancid_cmd_version) = explode(' ', external_exec($rancid_cmd . ' -V'));
  list($rancid_cmd_base) = explode('.', $rancid_cmd_version);
} else {
  // Dummy version for compare
  $rancid_cmd_version = 0;
  $rancid_cmd_base = 0;
}

if ($rancid_config_base > 0 && $rancid_cmd_base > 0 && $rancid_config_base != $rancid_cmd_base) {
  // If configured version base different from detected, than prefer configured (ie, for force v2 delimiter
  $rancid_version_base = $rancid_config_base;
  $rancid_version = $rancid_config_version;
  $rancid_message = "Used RANCID version configured manually in \$config['rancid_version']: $rancid_version";
} elseif (version_compare($rancid_cmd_version, $rancid_config_version, '>')) {
  // RANCID locally detected, use maximum version
  $rancid_version_base = $rancid_cmd_version;
  $rancid_version = $rancid_cmd_version;
  $rancid_message = "Used RANCID version detected on system: $rancid_version";
} elseif (version_compare($rancid_config_version, '0', '>')) {
  // RANCID not detected, but version configured
  $rancid_version_base = $rancid_config_base;
  $rancid_version = $rancid_config_version;
  $rancid_message = "Used RANCID version configured manually in \$config['rancid_version']: $rancid_version";
} else {
  // Last compat version
  $rancid_version_base = '2';
  $rancid_version = '2';
  $rancid_message = "Used default RANCID version: $rancid_version";
}
$rancid_version = rtrim($rancid_version, '.0');

// Set delimiter
if ($rancid_version_base < 3) {
  $delimiter = ':';
} else {
  $delimiter = ';';
}

print_debug($rancid_message);

$os_maps = [];

// Add user defined os maps first
if (is_array($config['rancid']['os_map'])) {
  foreach ($config['rancid']['os_map'] as $os => $name) {
    $os_maps[$os][] = [ 'name' => $name ];
  }
}

// OS maps from definitions
foreach ($config['os'] as $os => $entry) {
  if (!isset($entry['rancid'])) { continue; }

  $os_maps[$os] = $entry['rancid'];
}

print_debug_vars($os_maps, 1);

?>
# RANCID router.db autogenerated by <?php echo realpath($_SERVER['SCRIPT_FILENAME']) . PHP_EOL; ?>
# <?php echo $rancid_message . PHP_EOL; ?>
# Do not edit this file directly!

<?php

$where = 'WHERE 1';
$params = [];
if (!isset($options['a'])) {
  $where .= ' AND `poller_id` = ?';
  $params[] = $config['poller_id'];
}
// Limit devices with rancid supported os
$where .= generate_query_values_and(array_keys($os_maps), 'os');
$sql = "SELECT `hostname`, `os`, `version`, `hardware`, `disabled`, `status` FROM `devices` " . $where . " ORDER BY `hostname`";
foreach (dbFetchRows($sql, $params) as $device) {
  // rancid_device_blacklist or rancid_host_blacklist??
  if (is_array($config['rancid_device_blacklist']) &&
      in_array($device['hostname'], $config['rancid_host_blacklist'], TRUE)) { continue; }

  if ($device['disabled'] || !$device['status']) {
    $status = "down";
  } else {
    $status = "up";
  }
  //print_vars($device);

  // New defs
  foreach ($os_maps[$device['os']] as $rancid_map) {
    // Need check hardware for map
    if (isset($rancid_map['hardware']) &&
        !preg_match($rancid_map['hardware'], $device['hardware'])) { continue; }

    // Need check min os version
    if (isset($rancid_map['version_min']) &&
        version_compare($rancid_map['version_min'], $device['version'], '>')) { continue; }

    // Need check min rancid version
    if (isset($rancid_map['rancid_min']) &&
        version_compare(rtrim($rancid_map['rancid_min'], '.0'), $rancid_version, '>')) { continue; }

    // All checks complete, write rancid entry and break loop
    echo($device['hostname'] . $delimiter . $rancid_map['name'] . $delimiter . $status . PHP_EOL);
    break;
  }
}

// EOF
