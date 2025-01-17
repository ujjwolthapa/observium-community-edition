<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// FIXME - this could do with some performance improvements, i think. possible rearranging some tables and setting flags at poller time (nothing changes outside of then anyways)

// Time our menu filling.
$menu_start = utime();

if (OBS_DEBUG) {
    print_error("Navbar disabled for debugging.");
}

?>

    <header class="navbar navbar-fixed-top<?php if (OBS_DEBUG) {
        echo 'hidden';
    } ?>">
        <div class="navbar-inner">
            <div class="container">
                <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target="#main-nav">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="brand brand-observium" href="/" <?php if (isset($config['web']['logo'])) {
                    echo 'style="background: url(../images/' . escape_html($config['web']['logo']) . ') no-repeat; background-size: contain;"';
                } ?> >&nbsp;</a>
                <div class="nav-collapse" id="main-nav">
                    <ul class="nav">
                        <?php

                        /// Build main "globe" menu
                        $navbar['observium'] = ['url' => generate_url(['page' => 'dashboard']), 'icon' => $config['icon']['globe']];

                        // Dashboards

                        $dashboards = dbFetchRows("SELECT * FROM `dashboards`");

                        $entries = [];

                        if (safe_count($dashboards)) {
                            //$navbar['observium']['dash']['text'] = "Dashboards";
                            foreach ($dashboards as $dash) {
                                $entries[] = ['text' => $dash['dash_name'], 'url' => generate_url(['page' => "dashboard", 'dash' => $dash['dash_id']]), 'icon' => $config['icon']['overview']];
                            }
                        }

                        $entries[] = ['divider' => TRUE];

                        if ($_SESSION['userlevel'] > 7) {
                            $entries[] = ['title' => 'Create Dashboard', 'url' => generate_url(['page' => 'dashboard_add']), 'icon' => $config['icon']['plus']];
                        }

                        $navbar['observium']['entries'][] = ['title' => 'Dashboard', 'url' => generate_url(['page' => 'dashboard']), 'icon' => $config['icon']['overview'], 'entries' => $entries];
                        $navbar['observium']['entries'][] = ['divider' => TRUE];

                        unset($entries);

                        // End Dashboards

                        // Weathermaps

                        if ($config['weathermap']['enable'] === TRUE && $_SESSION['userlevel'] > 7) {
                            $entries = [];

                            foreach (dbFetchRows("SELECT * FROM `weathermaps`") as $weathermap) {
                                $entries[] = ['text' => $weathermap['wmap_name'], 'url' => generate_url(['page' => "wmap", 'mapname' => $weathermap['wmap_name']]), 'icon' => 'sprite-map'];
                            }

                            $navbar['observium']['entries'][] = ['title' => 'Weathermaps', 'url' => generate_url(['page' => 'wmap']), 'icon' => 'sprite-map-2', 'entries' => $entries];
                            $navbar['observium']['entries'][] = ['divider' => TRUE];

                        }

                        // End Weathermaps

                        // Show Groups
                        $entity_group_menu = [];
                        if (OBSERVIUM_EDITION !== 'community' && $_SESSION['userlevel'] >= 5) {
                            navbar_group_menu($navbar, $entity_group_menu);
                        }

                        $navbar['observium']['entries'][] = ['title' => 'Alerts', 'url' => generate_url(['page' => 'alerts']), 'icon' => $config['icon']['alert']];
                        if ($_SESSION['userlevel'] >= 5) {

                            $alert_checks[] = [ 'url' => generate_url(['page' => 'add_alert_check']), 'title' => 'Create New Checker', 'icon' => $config['icon']['plus'], 'userlevel' => 9 ];
                            $alert_checks[] = [ 'divider' => TRUE, 'userlevel' => 9 ];

                            $alert_check_count  = 0;  // Total count
                            $alert_checks_count = []; // Count by type
                            $alert_checks_type  = [];
                            foreach (dbFetchRows("SELECT * FROM `alert_tests` ORDER BY `entity_type`, `alert_name`") as $alert_check) {
                                //r($alert_check);
                                $alert_check_count++;
                                $alert_checks_count[$alert_check['entity_type']]++;

                                $alert_checks_type[$alert_check['entity_type']][] = [
                                  'url'   => generate_url(['page' => 'alert_check', 'alert_test_id' => $alert_check['alert_test_id']]),
                                  'title' => escape_html($alert_check['alert_name']),
                                  'icon'  => $config['entities'][$alert_check['entity_type']]['icon']
                                ];

                            }

                            if (safe_count($alert_checks_count) > 2 && $alert_check_count > 30) {
                                // Nested menus
                                foreach ($alert_checks_type as $entity_type => $entries) {
                                    $alert_checks[] = ['url'     => generate_url(['page' => 'alert_checks', 'entity_type' => $entity_type]),
                                                       'title'   => $config['entities'][$entity_type]['names'],
                                                       'icon'    => $config['entities'][$entity_type]['icon'],
                                                       'count'   => $alert_checks_count[$entity_type],
                                                       'entries' => $entries];
                                }
                            } else {
                                // Simple
                                foreach ($alert_checks_type as $entity_type => $entries) {
                                    if (safe_count($alert_checks_count) > 3 && $alert_checks_count[$entity_type] > 7) {
                                        // Force nested for single group type
                                        $alert_checks[] = ['url'     => generate_url(['page' => 'alert_checks', 'entity_type' => $entity_type]),
                                                           'title'   => $config['entities'][$entity_type]['names'],
                                                           'icon'    => $config['entities'][$entity_type]['icon'],
                                                           'count'   => $alert_checks_count[$entity_type],
                                                           'entries' => $entries];
                                    } else {
                                        $alert_checks = array_merge($alert_checks, $entries);
                                    }
                                }
                            }

                            $navbar['observium']['entries'][] = ['title'   => 'Alert Checks',
                                                                 'url'     => generate_url(['page' => 'alert_checks']),
                                                                 'count'   => $alert_check_count,
                                                                 'icon'    => $config['icon']['alert-rules'],
                                                                 'entries' => $alert_checks];
                        }

                        $navbar['observium']['entries'][] = ['title' => 'Alert Logs',
                                                             'url'   => generate_url(['page' => 'alert_log']),
                                                             'icon'  => $config['icon']['alert-log']];

                        if (OBSERVIUM_EDITION !== 'community') {
                            $navbar['observium']['entries'][] = ['title' => 'Scheduled Maintenance', 'url' => generate_url(['page' => 'alert_maintenance']), 'icon' => $config['icon']['scheduled-maintenance'], 'userlevel' => 7];
                        }

                        $navbar['observium']['entries'][] = ['divider' => TRUE];

                        if (isset($config['enable_syslog']) && $config['enable_syslog']) {
                            $navbar['observium']['entries'][] = ['title' => 'Syslog', 'url' => generate_url(['page' => 'syslog']), 'icon' => $config['icon']['syslog']];

                            if (OBSERVIUM_EDITION !== 'community') {
                                $navbar['observium']['entries'][] = [ 'title' => 'Syslog Alerts', 'url' => generate_url(['page' => 'syslog_alerts']), 'icon' => $config['icon']['syslog-alerts'] ];
                                $navbar['observium']['entries'][] = [ 'title' => 'Syslog Rules', 'url' => generate_url(['page' => 'syslog_rules']), 'icon' => $config['icon']['syslog-rules'], 'userlevel' => 7 ];
                                $navbar['observium']['entries'][] = [ 'divider' => TRUE ];
                            }
                        }

                        if (isset($config['enable_map']) && $config['enable_map']) { // FIXME link is wrong. Is this a supported feature? No. It smells. HTML page was removed, map.php generator code remains. See replacement below.
                            $navbar['observium']['entries'][] = ['title' => 'Network Map', 'url' => generate_url(['page' => 'map']), 'icon' => $config['icon']['netmap']];
                        }

                        $navbar['observium']['entries'][] = ['title' => 'Network Map', 'url' => generate_url(['page' => 'map']), 'icon' => $config['icon']['netmap']];
                        $navbar['observium']['entries'][] = ['title' => 'Network Traffic Map', 'url' => generate_url(['page' => 'map_traffic']), 'icon' => $config['icon']['map']];

                        $navbar['observium']['entries'][] = ['title' => 'Event Log', 'url' => generate_url(['page' => 'eventlog']), 'icon' => $config['icon']['eventlog']];


                        $navbar['observium']['entries'][] = ['divider' => TRUE];

                        if ($_SESSION['userlevel'] >= 7) {
                            // Print Contacts
                            $counts['contacts'] = dbFetchCell("SELECT COUNT(*) FROM `alert_contacts`");

                            $navbar['observium']['entries'][] = ['title' => 'Contacts', 'url' => generate_url(['page' => 'contacts']), 'count' => $counts['contacts'], 'icon' => $config['icon']['contacts']];
                            $navbar['observium']['entries'][] = ['divider' => TRUE];
                        }

                        if (OBSERVIUM_EDITION !== 'community' && $_SESSION['userlevel'] >= 5) {
                            // Custom OIDs

                            $oids = dbFetchRows("SELECT `oids`.*, COUNT(*) AS `count` FROM `oids` JOIN `oids_entries` ON `oids`.`oid_id` = `oids_entries`.`oid_id` WHERE 1 GROUP BY `oids`.`oid_id`");

                            $oid_count = safe_count($oids);

                            $oids_menu = [];
                            foreach ($oids as $oid) {
                                $oids_menu[] = ['title' => $oid['oid_descr'], 'url' => generate_url(['page' => 'customoid', 'oid_id' => $oid['oid_id']]), 'count' => $oid['count'], 'icon' => $config['icon']['customoid']];
                            }

                            $navbar['observium']['entries'][] = ['title' => 'Custom OIDs', 'url' => generate_url(['page' => 'customoids']), 'count' => $oid_count, 'icon' => $config['icon']['customoid'], 'entries' => $oids_menu];
                            //$navbar['observium']['entries'][] = array('divider' => TRUE);

                            // Probes
                            $navbar['observium']['entries'][] = ['title' => 'Probes', 'url' => generate_url(['page' => 'probes']), 'icon' => $config['icon']['status']];
                            $navbar['observium']['entries'][] = ['divider' => TRUE];

                        }

                        $navbar['observium']['entries'][] = ['title' => 'Hardware Inventory', 'url' => generate_url(['page' => 'inventory']), 'icon' => $config['icon']['inventory']];

                        if ($cache['packages']['count']) {
                            $navbar['observium']['entries'][] = ['title' => 'Software Packages', 'url' => generate_url(['page' => 'packages']), 'icon' => $config['icon']['packages']];
                        }

                        $navbar['observium']['entries'][] = ['divider' => TRUE];

                        // Build search submenu
                        $search_sections = ['ipv4' => 'IPv4 Address', 'ipv6' => 'IPv6 Address', 'mac' => 'MAC Address', 'arp' => 'ARP/NDP Tables', 'fdb' => 'FDB Tables'];
                        if ($cache['wifi_sessions']['count']) {
                            $search_sections['dot1x'] = '.1x Sessions';
                        }

                        foreach ($search_sections as $search_page => $search_name) {
                            $search_menu[] = ['title' => $search_name, 'url' => generate_url(['page' => 'search', 'search' => $search_page]), 'icon' => $config['icon']['search']];
                        }

                        $navbar['observium']['entries'][] = ['title' => 'Search', 'url' => generate_url(['page' => 'search']), 'icon' => $config['icon']['search'], 'entries' => $search_menu];

                        //////////// Build devices menu
                        $navbar['devices'] = ['url' => generate_url(['page' => 'devices']), 'icon' => $config['icon']['devices'], 'title' => 'Devices'];

                        $navbar['devices']['entries'][] = ['title'       => 'All Devices',
                                                           'count'       => $cache['devices']['stat']['count'],
                                                           'count_array' => $cache['devices']['stat'],
                                                           'url'         => generate_url(['page' => 'devices']),
                                                           'icon'        => $config['icon']['devices']];

                        if (safe_count($entity_group_menu['device'])) {
                            $navbar['devices']['entries'][] = ['divider' => TRUE];
                            $navbar['devices']['entries'][] = ['title' => 'Groups', 'url' => generate_url(['page' => 'groups', 'entity_type' => 'device']), 'icon' => $config['icon']['group'], 'count' => safe_count($entity_group_menu['device']), 'entries' => $entity_group_menu['device']];
                        }

                        $navbar['devices']['entries'][] = ['divider' => TRUE];

                        // Build location submenu
                        if ($config['web_show_locations']) {
                            switch ($config['location']['menu']['type']) {
                                case 'geocoded':
                                    $navbar['devices']['entries'][] = ['locations' => TRUE]; // Pretty complicated recursive function, workaround not having it converted to returning an array
                                    break;
                                case 'nested':
                                    $locations = ['title' => 'Locations', 'icon' => $config['icon']['location']]; // Init empty array

                                    foreach (get_locations() as $location) {
                                        // If location is empty, substitute by OBS_VAR_UNSET as empty location parameter would be ignored
                                        $name = ($location === '' ? OBS_VAR_UNSET : $location);

                                        $location_split = explode($config['location']['menu']['nested_split_char'], $name, $config['location']['menu']['nested_max_depth']);

                                        // Turn array around if nested reversed option is active
                                        if ($config['location']['menu']['nested_reversed']) {
                                            $location_split = array_reverse($location_split);
                                        }

                                        $ref = &$locations; // Start from top menu array
                                        $last = safe_count($location_split);

                                        for ($i = 0; $i < $last; $i++) {
                                            $location_part = trim($location_split[$i]);

                                            $ref = &$ref['entries'][$location_part];

                                            if (!is_array($ref)) {
                                                // Get partial location string up to the point where we are
                                                $location_slice = array_slice($location_split, 0, $i + 1);
                                                // Turn array around again (to normal presentation) if nested reversed option is active
                                                if ($config['location']['menu']['nested_reversed']) {
                                                    $location_slice = array_reverse($location_slice);
                                                }
                                                // Generate URL based on slice
                                                $location_url = generate_url([ 'page' => 'devices',
                                                                               'location_text' => '"' . trim(implode($config['location']['menu']['nested_split_char'], $location_slice)) . '"' ]);

                                                $ref = ['icon' => $config['icon']['location'], 'title' => $location_part, 'url' => $location_url];
                                                if ($i === ($last - 1)) {
                                                  $ref['count'] = $cache['device_locations'][$location];
                                                }
                                            }
                                        }
                                    }

                                    $navbar['devices']['entries'][] = $locations;
                                    break;
                                case 'plain':
                                default:
                                    foreach (get_locations() as $location) {
                                        // If location is empty, substitute by OBS_VAR_UNSET as empty location parameter would be ignored
                                        $name = ($location === '' ? OBS_VAR_UNSET : $location);

                                        // No nested menu, just list all locations one after another
                                        $location_menu[] = ['url' => generate_location_url($location), 'icon' => $config['icon']['location'], 'title' => $name, 'count' => $cache['device_locations'][$location] ];
                                    }

                                    $navbar['devices']['entries'][] = ['title' => 'Locations', 'url' => generate_url(['page' => 'locations']), 'icon' => $config['icon']['locations'], 'scrollable' => TRUE, 'entries' => $location_menu];
                                    break;
                            }

                            $navbar['devices']['entries'][] = ['divider' => TRUE];
                        }

                        // Build list per device type
                        foreach ($config['device_types'] as $devtype) {
                            if (array_key_exists($devtype['type'], (array)$cache['devices']['types'])) {
                                $navbar['devices']['entries'][] = ['title'       => $devtype['text'],
                                                                   'icon'        => $devtype['icon'],
                                                                   'count_array' => $cache['devices']['types'][$devtype['type']],
                                                                   'count'       => $cache['devices']['types'][$devtype['type']]['count'],
                                                                   'url'         => generate_url(['page' => 'devices', 'type' => $devtype['type']])];
                            }
                        }

                        if ($cache['devices']['stat']['down'] + $cache['devices']['stat']['ignored'] + $cache['devices']['stat']['disabled']) {
                            $navbar['devices']['entries'][] = ['divider' => TRUE];
                            if ($cache['devices']['stat']['down']) {
                                $navbar['devices']['entries'][] = ['url' => generate_url(['page' => 'devices', 'status' => '0']), 'icon' => $config['icon']['exclamation'], 'title' => 'Down', 'count_array' => ['down' => $cache['devices']['stat']['down']]];
                            }
                            if ($cache['devices']['stat']['ignored']) {
                                $navbar['devices']['entries'][] = ['url' => generate_url(['page' => 'devices', 'ignore' => '1']), 'icon' => $config['icon']['ignore'], 'title' => 'Ignored', 'count_array' => ['ignored' => $cache['devices']['stat']['ignored']]];
                            }
                            if ($cache['devices']['stat']['disabled']) {
                                $navbar['devices']['entries'][] = ['url' => generate_url(['page' => 'devices', 'disabled' => '1']), 'icon' => $config['icon']['shutdown'], 'title' => 'Disabled', 'count_array' => ['disabled' => $cache['devices']['stat']['disabled']]];
                            }
                        }

                        if ($_SESSION['userlevel'] >= 9) {
                            $navbar['devices']['entries'][] = [ 'divider' => TRUE ];
                            $navbar['devices']['entries'][] = [ 'url' => generate_url(['page' => 'addhost']), 'icon' => $config['icon']['plus'], 'title' => 'Add Device' ];
                            $navbar['devices']['entries'][] = [ 'url' => generate_url(['page' => 'delhost']), 'icon' => $config['icon']['minus'], 'title' => 'Delete Device' ];
                        }

                        if ($cache['vm']['count']) {
                            $navbar['devices']['entries'][] = ['divider' => TRUE];
                            $navbar['devices']['entries'][] = ['title' => 'Virtual Machines', 'count' => $cache['vm']['count'], 'url' => generate_url(['page' => 'vms']), 'icon' => $config['icon']['virtual-machine']];
                        }

                        //////////// Build ports menu
                        $navbar['ports'] = ['url' => generate_url(['page' => 'ports']), 'icon' => $config['entities']['port']['icon'], 'title' => 'Ports'];

                        $navbar['ports']['entries'][] = ['title' => 'All Ports', 'count' => $cache['ports']['stat']['count'], 'url' => generate_url(['page' => 'ports']), 'icon' => $config['entities']['port']['icon']];
                        $navbar['ports']['entries'][] = ['divider' => TRUE];

                        if (safe_count($entity_group_menu['port'])) {
                            $navbar['ports']['entries'][] = ['title' => 'Groups', 'url' => generate_url(['page' => 'groups', 'entity_type' => 'port']), 'icon' => $config['icon']['group'], 'count' => safe_count($entity_group_menu['port']), 'entries' => $entity_group_menu['port']];
                            $navbar['ports']['entries'][] = ['divider' => TRUE];
                        }

                        $navbar['ports']['entries'][] = ['title' => 'VLANs', 'url' => generate_url(['page' => 'vlan']), 'icon' => $config['icon']['vlan']];
                        $navbar['ports']['entries'][] = ['divider' => TRUE];


                        if ($cache['p2pradios']['count']) {
                            $navbar['ports']['entries'][] = ['title' => 'P2P Radios', 'count' => $cache['p2pradios']['count'], 'url' => generate_url(['page' => 'p2pradios']), 'icon' => $config['entities']['p2pradio']['icon']];
                            $navbar['ports']['entries'][] = ['divider' => TRUE];
                        }

                        if ($config['enable_billing']) {
                            $navbar['ports']['entries'][] = ['title' => 'Traffic Accounting', 'url' => generate_url(['page' => 'bills']), 'icon' => $config['icon']['billing']];
                            $ifbreak                      = 1;
                        }

                        if ($cache['neighbours']['count']) {
                            $navbar['ports']['entries'][] = ['title' => 'Neighbours', 'url' => generate_url(['page' => 'neighbours']), 'icon' => $config['icon']['neighbours'], 'count' => $cache['neighbours']['count']];
                            $ifbreak                      = 1;
                        }

                        if ($config['enable_pseudowires'] && $cache['pseudowires']['count']) {
                            $navbar['ports']['entries'][] = ['title' => 'Pseudowires', 'count' => $cache['pseudowires']['count'], 'url' => generate_url(['page' => 'pseudowires']), 'icon' => $config['icon']['pseudowire']];
                            $ifbreak                      = 1;
                        }

                        if ($cache['mac_accounting']['count']) {
                            $navbar['ports']['entries'][] = ['title' => 'MAC Accounting', 'count' => $cache['mac_accounting']['count'], 'url' => generate_url(['page' => 'ports', 'mac_accounting' => 'yes']), 'icon' => $config['icon']['port']];
                            $ifbreak                      = 1;
                        }

                        if ($cache['cbqos']['count']) {
                            $navbar['ports']['entries'][] = ['title' => 'CBQoS', 'count' => $cache['cbqos']['count'], 'url' => generate_url(['page' => 'ports', 'cbqos' => 'yes']), 'icon' => $config['icon']['cbqos']];
                            $ifbreak                      = 1;
                        }

                        if ($cache['sla']['count']) {
                            $navbar['ports']['entries'][] = ['title' => 'IP SLA', 'count' => $cache['sla']['count'], 'url' => generate_url(['page' => 'slas']), 'icon' => $config['icon']['sla']];
                            $ifbreak                      = 1;
                        }

                        if ($ifbreak) {
                            $navbar['ports']['entries'][] = ['divider' => TRUE];
                            $ifbreak                      = 0;
                        }

                        if ($_SESSION['userlevel'] >= '5') {
                            // FIXME new icons
                            if ($config['int_customers']) {
                                $navbar['ports']['entries'][] = ['url' => generate_url(['page' => 'customers']), 'icon' => $config['icon']['port-customer'], 'title' => 'Customers'];
                                $ifbreak                      = 1;
                            }
                            if ($config['int_l2tp']) {
                                $navbar['ports']['entries'][] = ['url' => generate_url(['page' => 'iftype', 'type' => 'l2tp']), 'icon' => $config['icon']['users'], 'title' => 'L2TP'];
                                $ifbreak                      = 1;
                            }
                            if ($config['int_transit']) {
                                $navbar['ports']['entries'][] = ['url' => generate_url(['page' => 'iftype', 'type' => 'transit']), 'icon' => $config['icon']['port-transit'], 'title' => 'Transit'];
                                $ifbreak                      = 1;
                            }
                            if ($config['int_peering']) {
                                $navbar['ports']['entries'][] = ['url' => generate_url(['page' => 'iftype', 'type' => 'peering']), 'icon' => $config['icon']['port-peering'], 'title' => 'Peering'];
                                $ifbreak                      = 1;
                            }
                            if ($config['int_peering'] && $config['int_transit']) {
                                $navbar['ports']['entries'][] = ['url' => generate_url(['page' => 'iftype', 'type' => 'peering,transit']), 'icon' => $config['icon']['port-peering-transit'], 'title' => 'Peering & Transit'];
                                $ifbreak                      = 1;
                            }
                            if ($config['int_core']) {
                                $navbar['ports']['entries'][] = ['url' => generate_url(['page' => 'iftype', 'type' => 'core']), 'icon' => $config['icon']['port-core'], 'title' => 'Core'];
                                $ifbreak                      = 1;
                            }

                            // Custom interface groups can be set - see Interface Description Parsing
                            foreach ($config['int_groups'] as $int_type) {
                                $navbar['ports']['entries'][] = ['url' => generate_url(['page' => 'iftype', 'type' => $int_type]), 'icon' => $config['icon']['port'], 'title' => str_replace(',', ' & ', $int_type)];
                                $ifbreak                      = 1;
                            }
                        }

                        if ($ifbreak) {
                            $navbar['ports']['entries'][] = ['divider' => TRUE];
                        }

                        $navbar['ports']['entries']['statuses'] = ['title' => 'Status Breakdown', 'url' => generate_url(['page' => '#']), 'icon' => $config['entities']['port']['icon'], 'entries' => []];

                        if ($cache['ports']['stat']['errored']) {
                            $navbar['ports']['entries']['statuses']['entries'][] = ['url' => generate_url(['page' => 'ports', 'errors' => 'yes']), 'icon' => $config['icon']['flag'], 'title' => 'Errored', 'count' => $cache['ports']['stat']['errored']];
                        }

                        if ($cache['ports']['stat']['down']) {
                            $navbar['ports']['entries']['statuses']['entries'][] = ['url' => generate_url(['page' => 'ports', 'state' => 'down']), 'icon' => $config['icon']['down'], 'title' => 'Down', 'count' => $cache['ports']['stat']['down']];
                        }

                        if ($cache['ports']['stat']['shutdown']) {
                            $navbar['ports']['entries']['statuses']['entries'][] = ['url' => generate_url(['page' => 'ports', 'state' => 'shutdown']), 'icon' => $config['icon']['shutdown'], 'title' => 'Shutdown', 'count' => $cache['ports']['stat']['shutdown']];
                        }

                        if ($cache['ports']['stat']['ignored']) {
                            $navbar['ports']['entries']['statuses']['entries'][] = ['url' => generate_url(['page' => 'ports', 'ignore' => '1']), 'icon' => $config['icon']['ignore'], 'title' => 'Ignored', 'count' => $cache['ports']['stat']['ignored']];
                        }

                        if ($cache['ports']['stat']['poll_disabled']) {
                            $navbar['ports']['entries']['statuses']['entries'][] = ['url' => generate_url(['page' => 'ports', 'disabled' => '1']), 'icon' => $config['icon']['ignore'], 'title' => 'Poll Disabled', 'count' => $cache['ports']['stat']['poll_disabled']];
                        }

                        if ($cache['ports']['stat']['deleted']) {
                            $navbar['ports']['entries']['statuses']['entries'][] = ['url' => generate_url(['page' => 'deleted-ports']), 'icon' => $config['icon']['stop'], 'title' => 'Deleted', 'count' => $cache['ports']['stat']['deleted']];
                        }

                        //////////// Build health menu
                        $navbar['health'] = ['url' => '#', 'icon' => $config['icon']['health'], 'title' => 'Health'];

                        $health_items = ['processor' => ['text' => 'Processors', 'icon' => $config['icon']['processor']],
                                         'mempool'   => ['text' => 'Memory', 'icon' => $config['icon']['mempool']],
                                         'storage'   => ['text' => 'Storage', 'icon' => $config['icon']['storage']]
                        ];

                        if ($cache['printersupplies']['count']) {
                            $health_items['printersupplies'] = ['text' => 'Printer Supplies', 'icon' => $config['icon']['printersupply']];
                        }

                        if ($cache['status']['count']) {
                            $health_items['status'] = ['text' => 'Status', 'icon' => $config['entities']['status']['icon'], 'count_array' => $cache['statuses']['stat']];
                        }

                        if ($cache['counter']['count']) {
                            $health_items['counter'] = ['text' => 'Counter', 'icon' => $config['entities']['counter']['icon'], 'count_array' => $cache['counters']['stat']];
                        }

                        foreach ($health_items as $item => $item_data) {
                            $navbar['health']['entries'][] = ['url' => generate_url(['page' => 'health', 'metric' => $item]), 'icon' => $item_data['icon'], 'title' => $item_data['text'], 'count_array' => $item_data['count_array']];
                            unset($menu_sensors[$item]);
                            $sep++;
                        }

                        //r($cache['sensor_types']);

                        $menu_items[0] = ['temperature', 'humidity', 'fanspeed', 'airflow'];
                        $menu_items[1] = ['current', 'voltage', 'power', 'apower', 'rpower', 'frequency'];
                        $menu_items[2] = array_diff(array_keys((array)$cache['sensors']['types']), $menu_items[0], $menu_items[1]);

                        foreach ($menu_items as $key => $items) {
                            if ($key > 1 && is_array($items)) {
                                sort($items);
                            } // Do not sort first basic health entities

                            foreach ($items as $item) {
                                if (isset($cache['sensors']['types'][$item]) && is_array($cache['sensors']['types'][$item])) {
                                    if ($sep) {
                                        $navbar['health']['entries'][] = ['divider' => TRUE];
                                        $sep                           = 0;
                                    }

                                    //$alert_icon = ($cache['sensor_types'][$item]['alert'] ? '<i class="' . $config['icon']['flag'] . ' mini-icon"></i>' : '');
                                    $navbar['health']['entries'][] = [
                                      'url'         => generate_url(['page' => 'health', 'metric' => $item]),
                                      'count_array' => $cache['sensors']['types'][$item],
                                      'count'       => $cache['sensors']['types'][$item]['count'],
                                      'alert_count' => $cache['sensors']['types'][$item]['alert'],
                                      'icon'        => $config['sensor_types'][$item]['icon'],
                                      'title'       => nicecase($item)
                                    ];
                                }
                            }
                            $sep++;
                        }

                        //////////// Build applications menu
                        if ($_SESSION['userlevel'] >= '5' && ($cache['applications']['count']) > 0) {
                            $navbar['apps'] = ['url' => '#', 'icon' => $config['icon']['apps'], 'title' => 'Apps'];

                            $app_list = dbFetchRows("SELECT `app_type`, COUNT(*) AS `count` FROM `applications`" . generate_where_clause($cache['where']['devices_permitted']) . " GROUP BY `app_type`;");
                            foreach ($app_list as $app) {
                                $image = $config['html_dir'] . "/images/icons/" . $app['app_type'] . ".png";
                                //$icon  = (is_file($image) ? $app['app_type'] : "apps");
                                // Detect and add application icon
                                $icon  = $app['app_type'];
                                $image = $config['html_dir'] . '/images/apps/' . $icon . '.png';
                                if (is_file($image)) {
                                    // Icon found
                                    //$icon = $app['app_type'];
                                } else {
                                    [$icon] = explode('-', str_replace('_', '-', $app['app_type']));
                                    $image = $config['html_dir'] . '/images/apps/' . $icon . '.png';
                                    if ($icon != $app['app_type'] && is_file($image)) {
                                        // 'postfix_qshape' -> 'postfix'
                                        // 'exim-mailqueue' -> 'exim'
                                    } else {
                                        $icon = 'apps'; // Generic
                                    }
                                }

                                $entry          = ['url' => generate_url(['page' => 'apps', 'app' => $app['app_type'], 'count' => $app['count']]), 'title' => nicecase($app['app_type'])];
                                $entry['image'] = 'images/apps/' . $icon . '.png';
                                if (is_file($config['html_dir'] . '/images/apps/' . $icon . '_2x.png')) {
                                    // HiDPI icon
                                    $entry['image_2x'] = 'images/apps/' . $icon . '_2x.png';
                                }

                                $navbar['apps']['entries'][] = $entry;
                            }
                            unset($entry);
                        }

                        //////////// Build routing menu
                        if ($_SESSION['userlevel'] >= '5' && ($cache['routing']['bgp']['count'] + $cache['routing']['ospf']['count'] + $cache['routing']['cef']['count'] + $cache['routing']['vrf']['count']) > 0) {
                            $navbar['routing'] = ['url' => '#', 'icon' => $config['icon']['routing'], 'title' => 'Routing'];

                            $separator = 0;

                            if (safe_count($entity_group_menu['bgp_peer']) || safe_count($entity_group_menu['bgp_peer_af'])) {
                                if (safe_count($entity_group_menu['bgp_peer'])) {
                                    $navbar['routing']['entries'][] = ['title'   => 'BGP Peer Groups', 'url' => generate_url(['page' => 'groups', 'entity_type' => 'bgp_peer']),
                                                                       'icon'    => $config['icon']['group'], 'count' => safe_count($entity_group_menu['bgp_peer']),
                                                                       'entries' => array_merge($entity_group_menu['bgp_peer'])];
                                }
                                if (safe_count($entity_group_menu['bgp_peer_af'])) {
                                    $navbar['routing']['entries'][] = ['title'   => 'BGP Peer (AFI/SAFI) Groups', 'url' => generate_url(['page' => 'groups', 'entity_type' => 'bgp_peer_af']),
                                                                       'icon'    => $config['icon']['group'], 'count' => safe_count($entity_group_menu['bgp_peer_af']),
                                                                       'entries' => array_merge($entity_group_menu['bgp_peer_af'])];
                                }
                                $separator = 1;
                            }

                            if ($cache['routing']['vrf']['count']) {
                                if ($separator) {
                                    $navbar['routing']['entries'][] = ['divider' => TRUE];
                                    $separator                      = 0;
                                }
                                $navbar['routing']['entries'][] = ['url' => generate_url(['page' => 'routing', 'protocol' => 'vrf']), 'icon' => $config['icon']['vrf'], 'title' => 'VRFs', 'count' => $cache['routing']['vrf']['count']];

                            }

                            if ($cache['routing']['cef']['count']) {
                                if ($separator) {
                                    $navbar['routing']['entries'][] = ['divider' => TRUE];
                                    $separator                      = 0;
                                }

                                $navbar['routing']['entries'][] = ['url' => generate_url(['page' => 'routing', 'protocol' => 'cef']), 'icon' => $config['icon']['cef'], 'title' => 'CEF', 'count' => $cache['routing']['cef']['count']];
                                $separator++;
                            }

                            if ($cache['routing']['eigrp']['count']) {
                                if ($separator) {
                                    $navbar['routing']['entries'][] = ['divider' => TRUE];
                                    $separator                      = 0;
                                }

                                $navbar['routing']['entries'][] = ['url' => generate_url(['page' => 'routing', 'protocol' => 'eigrp']), 'icon' => $config['icon']['ospf'], 'title' => 'EIGRP', 'count' => $cache['routing']['eigrp']['count']];
                                $separator++;
                            }

                            if ($cache['routing']['ospf']['count']) {
                                if ($separator) {
                                    $navbar['routing']['entries'][] = ['divider' => TRUE];
                                    $separator                      = 0;
                                }

                                $navbar['routing']['entries'][] = ['url' => generate_url(['page' => 'routing', 'protocol' => 'ospf']), 'icon' => $config['icon']['ospf'], 'title' => 'OSPF', 'count' => $cache['routing']['ospf']['count']];
                                $separator++;
                            }

                            // BGP Sessions
                            if ($cache['routing']['bgp']['count']) {
                                if ($separator) {
                                    $navbar['routing']['entries'][] = ['divider' => TRUE];
                                    $separator                      = 0;
                                }

                                $navbar['routing']['entries'][] = ['url' => generate_url(['page' => 'routing', 'protocol' => 'bgp', 'type' => 'all', 'graph' => 'NULL']), 'icon' => $config['icon']['bgp'], 'title' => 'BGP All Sessions', 'count' => $cache['routing']['bgp']['count']];
                                $navbar['routing']['entries'][] = ['url' => generate_url(['page' => 'routing', 'protocol' => 'bgp', 'type' => 'external', 'graph' => 'NULL']), 'icon' => $config['icon']['bgp-external'], 'title' => 'BGP External', 'count' => $cache['routing']['bgp']['external']];
                                $navbar['routing']['entries'][] = ['url' => generate_url(['page' => 'routing', 'protocol' => 'bgp', 'type' => 'internal', 'graph' => 'NULL']), 'icon' => $config['icon']['bgp-internal'], 'title' => 'BGP Internal', 'count' => $cache['routing']['bgp']['internal']];
                            }

                            // Do Alerts at the bottom
                            if ($cache['routing']['bgp']['alerts']) {
                                $navbar['routing']['entries'][] = ['divider' => TRUE];
                                $navbar['routing']['entries'][] = ['url' => generate_url(['page' => 'routing', 'protocol' => 'bgp', 'adminstatus' => 'start', 'state' => 'down']), 'icon' => $config['icon']['bgp-alert'], 'title' => 'BGP Alerts', 'count' => $cache['routing']['bgp']['alerts']];
                            }
                        }

                        // Custom navbar entries.
                        if (is_file("includes/navbar-custom.inc.php")) {
                            include_once("includes/navbar-custom.inc.php");
                        }


                        // Build navbar from $navbar array
                        foreach ($navbar as $dropdown) {
//  echo('            <li class="divider-vertical" style="margin: 0;"></li>' . PHP_EOL);

                            if (str_contains($dropdown['icon'], 'sprite')) {
                                $element = 'span';
                            } else {
                                $element = 'i';
                            }

                            echo('            <li class="dropdown">' . PHP_EOL);
                            echo('              <a href="' . $dropdown['url'] . '" class="visible-lg visible-xl dropdown-toggle" data-hover="dropdown" data-toggle="dropdown">' . PHP_EOL);
                            echo('                <' . $element . ' class="' . $dropdown['icon'] . '"></' . $element . '> ' . escape_html($dropdown['title']) . ' <b class="caret"></b></a>' . PHP_EOL);
                            echo('              <a href="' . $dropdown['url'] . '" class="visible-xs visible-sm visible-md dropdown-toggle" data-hover="dropdown" data-toggle="dropdown">' . PHP_EOL);
                            echo('                <' . $element . ' class="' . $dropdown['icon'] . '" style="margin-right: 5px;"></i></a>' . PHP_EOL);
                            echo('              <ul role="menu" class="dropdown-menu">' . PHP_EOL);

                            foreach ($dropdown['entries'] as $entry) {
                                if (isset($entry['entries']) && safe_count($entry['entries'])) {
                                    navbar_submenu($entry);
                                } else {
                                    navbar_entry($entry);
                                }
                            }

                            echo('              </ul>' . PHP_EOL);
                            echo('            </li>' . PHP_EOL);
                        }

                        unset($navbar);

                        // The menus on the right are not handled by the navbar array code yet.
                        // max-width: 80vw - See: https://jira.observium.org/browse/OBS-3667
                        ?>
                    </ul>
                    <ul class="nav pull-right">
                        <li class="dropdown hidden-xs navbar-narrow">
                            <form id="searchform" class="navbar-search" action="#"
                                  style="margin: 5px 10px -5px 5px;">
                                <input style="width: 120px;" onkeyup="lookup(this.value);"
                                       onblur="$('#suggestions').fadeOut()" type="text" value="" class="dropdown-toggle"
                                       placeholder="Search"/>
                            </form>
                            <div id="suggestions" class="typeahead dropdown-menu pre-scrollable" style="max-width: 80vw; max-height: 85vh;"></div>
                        </li>
                        <?php

                        // Script for go to first founded link in search
                        register_html_resource('script', '
$(function() {
  $(\'form#searchform\').each(function() {
    $(this).find(\'input\').keypress(function(e) {
      if (e.which==10 || e.which==13) {
        //console.log($(\'div#suggestions > li > a\').first().prop(\'href\'));
        $(\'form#searchform\').prop(\'action\', $(\'div#suggestions > li > a\').first().prop(\'href\'));
        // Only submit if we actually have a suggestion to link to
        if ($(\'div#suggestions > li > a\').length > 0) {
          this.form.submit();
        }
      }
    });
  });
});
');

                        $ua = detect_browser();
                        if (get_var_true($_SESSION['touch'])) {
                            //$ua['url'] = generate_url($vars, array('touch' => 'no'));
                            $ua['attribs']['onclick'] = "ajax_action('touch_off');";
                        } else {
                            //$ua['url'] = generate_url($vars, array('touch' => 'yes'));
                            $ua['attribs']['onclick'] = "ajax_action('touch_on');";
                        }

                        if (get_var_true($vars['touch'])) {
                            $ua['icon'] = 'icon-hand-up';
                        }

                        $ua['remote_addr']  = get_remote_addr();
                        $remote_addr_header = get_remote_addr(TRUE); // Remote addr by http header
                        if ($remote_addr_header && $ua['remote_addr'] != $remote_addr_header) {
                            $ua['remote_addr'] = $remote_addr_header . ' (' . $ua['remote_addr'] . ')';
                        }
                        $ua['content'] = 'Your current IP:&nbsp;' . $ua['remote_addr'] . '<br />';
                        if ($config['web_mouseover'] && $ua['type'] !== 'mobile') {
                            // Add popup with current IP and previous session
                            $last_id                = dbFetchCell("SELECT `id` FROM `authlog` WHERE `user` = ? AND `result` LIKE 'Logged In%' ORDER BY `id` DESC LIMIT 1;", [$_SESSION['username']]);
                            $ua['previous_session'] = dbFetchRow("SELECT * FROM `authlog` WHERE `user` = ? AND `id` < ? AND `result` LIKE 'Logged In%' ORDER BY `id` DESC LIMIT 1;", [$_SESSION['username'], $last_id]);
                            if ($ua['previous_session']) {
                                $ua['previous_browser'] = detect_browser($ua['previous_session']['user_agent']);
                                $ua['content']          .= '<hr><span class="small">Previous session from&nbsp;<em class="pull-right">' . ($_SESSION['userlevel'] > 5 ? $ua['previous_session']['address'] : preg_replace('/^\d+/', '*', $ua['previous_session']['address'])) . '</em></span>';
                                $ua['content']          .= '<br /><span class="small pull-right"><em> at ' . format_timestamp($ua['previous_session']['datetime']) . '</span>';
                                $ua['content']          .= '<br /><span class="small pull-right">' . get_icon($ua['previous_browser']['icon']) . '&nbsp;' . $ua['previous_browser']['browser_full'] . ' (' . $ua['previous_browser']['platform'] . ')' . '</em></span>';
                            }
                        }

                        $ua['url'] = "#";

                        echo '<li>' . generate_tooltip_link($ua['url'], ' <i class="' . $ua['icon'] . '"></i>', $ua['content'], NULL, $ua['attribs']) . '</li>';

                        ?>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-hover="dropdown" data-toggle="dropdown"><i
                                  class="<?php echo $config['icon']['tools']; ?>"></i> <b class="caret"></b></a>
                            <ul role="menu" class="dropdown-menu">
                                <?php
                                // Refresh menu
                                echo('<li class="dropdown-submenu">');
                                echo('  <a role="menuitem" tabindex="-1" href="' . generate_url($vars) . '"><span><i class="' . $config['icon']['refresh'] . '"></i> Refresh</span>&nbsp;<span id="countrefresh"></span></a>');
                                echo('  <ul class="dropdown-menu">');
                                foreach ($page_refresh['list'] as $refresh_time) {
                                    $refresh_class = ($refresh_time == $page_refresh['current'] ? 'active' : '');
                                    if (!$page_refresh['allowed']) {
                                        $refresh_class = 'disabled';
                                    }
                                    if ($refresh_time == 0) {
                                        echo('    <li class="' . $refresh_class . '"><a href="' . generate_url($vars, ['refresh' => '0']) . '"><i class="' . $config['icon']['stop'] . '"></i> Manually</a></li>');
                                    } else {
                                        echo('    <li class="' . $refresh_class . '"><a href="' . generate_url($vars, ['refresh' => $refresh_time]) . '"><i class="' . $config['icon']['refresh'] . '"></i> Every ' . format_uptime($refresh_time, 'longest') . '</a></li>');
                                    }
                                }
                                echo('  </ul>');
                                echo('</li>');
                                echo('<li class="divider"></li>');

                                echo('<li class="dropdown-submenu">');
                                echo('  <a role="menuitem" href="#"><span><i class="' . $config['icon']['users'] . '"></i> Personalisation</span></a>');
                                echo('  <ul class="dropdown-menu">');

                                // This definition not exist in community edition
                                if (OBSERVIUM_EDITION !== 'community') {
                                    foreach ($config['themes'] as $theme_name => $theme_data) {
                                        if ($_SESSION['theme'] !== $theme_name) {
                                            echo('<li><a href="#" onclick="ajax_action(\'theme\', \'' . $theme_name . '\');" title="Switch to ' . $theme_data['name'] . '"><i class="' . $theme_data['icon'] . '" style="font-size: 16px; color: #555;"></i> ' . $theme_data['name'] . '</a></li>');
                                        }
                                    }
                                    if ($config['web_theme_default'] !== $_SESSION['theme'] && $config['web_theme_default'] !== 'system') {
                                        // Reset default
                                        echo('<li><a href="#" onclick="ajax_action(\'theme\', \'reset\');" title="Reset to default"><i class="sprite-refresh" style="font-size: 16px; color: #555;"></i> Reset</a></li>');
                                    }

                                    echo('    <li class="divider"></li>');
                                }

                                if ($config['graphs']['size'] === 'big') {
                                    echo('<li><a href="#" onclick="ajax_action(\'normal_graphs\');" title="Switch to normal graphs"><i class="' . $config['icon']['graphs-small'] . '" style="font-size: 16px; color: #555;"></i> Normal Graphs</a></li>');
                                } else {
                                    echo('<li><a href="#" onclick="ajax_action(\'big_graphs\');" title="Switch to larger graphs"><i class="' . $config['icon']['graphs-large'] . '" style="font-size: 16px; color: #555;"></i> Large Graphs</a></li>');
                                }

                                echo('  </ul>');
                                echo('</li>');

                                ?>
                                <li><a href="<?php echo(generate_url(['page' => 'preferences'])); ?>"
                                       title="My Profile"><?php echo get_icon('user-self'); ?> My
                                        Profile</a></li>
                                <?php

                                if ($_SESSION['userlevel'] >= 10) {
                                    echo('<li class="divider"></li>');
                                    echo('<li class="dropdown-submenu">');
                                    echo('  <a role="menuitem" tabindex="-1" href="' . generate_url(['page' => 'user_add']) . '"><span><i class="' . $config['icon']['users'] . '"></i> Users & Groups</span></a>');
                                    echo('  <ul class="dropdown-menu">');
                                    if (auth_usermanagement()) {
                                        echo('    <li><a href="' . generate_url(['page' => 'user_add']) . '"><i class="' . $config['icon']['user-add'] . '"></i> Add User</a></li>');
                                    }
                                    echo('    <li><a href="' . generate_url(['page' => 'user_edit']) . '"><i class="' . $config['icon']['user-edit'] . '"></i> Edit User</a></li>');
                                    //if (auth_usermanagement())
                                    //{
                                    //   echo('    <li><a href="' . generate_url(array('page' => 'user_edit')) . '"><i class="' . $config['icon']['user-delete'] . '"></i> Remove User</a></li>');
                                    //}

                                    echo('    <li class="divider"></li>');

                                    echo('    <li><a href="' . generate_url(['page' => 'roles']) . '"><i class="' . $config['icon']['users'] . '"></i> Roles</a></li>');

                                    echo('    <li class="divider"></li>');

                                    echo('    <li><a href="' . generate_url(['page' => 'authlog']) . '"><i class="' . $config['icon']['user-log'] . '"></i> Authentication Log</a></li>');
                                    echo('  </ul>');
                                    echo('</li>');

                                    echo('<li class="dropdown-submenu">');
                                    echo('  <a role="menuitem" tabindex="-1" href="' . generate_url(['page' => 'settings']) . '"><span><i class="' . $config['icon']['settings'] . '"></i> Global Settings</span></a>');
                                    echo('  <ul class="dropdown-menu">');
                                    echo('    <li><a href="' . generate_url(['page' => 'settings']) . '"><i class="' . $config['icon']['settings-change'] . '"></i> Edit</a></li>');
                                    echo('    <li><a href="' . generate_url(['page' => 'settings', 'format' => 'config']) . '"><i class="' . $config['icon']['config'] . '"></i> Full Dump</a></li>');
                                    echo('    <li><a href="' . generate_url(['page' => 'settings', 'format' => 'changed_config']) . '"><i class="' . $config['icon']['config'] . '"></i> Changed Dump</a></li>');
                                    if (OBS_DISTRIBUTED) {
                                        echo('    <li class="divider"></li>');
                                        echo('    <li><a href="' . generate_url(['page' => 'pollers']) . '"><i class="' . $config['icon']['pollers'] . '"></i> Pollers</a></li>');
                                    }
                                    echo('  </ul>');
                                    echo('</li>');
                                }

                                echo('<li class="divider"></li>');

                                navbar_entry([ 'title' => 'Polling Information', 'url' => generate_url(['page' => 'pollerlog']), 'icon' => $config['icon']['pollerlog'], 'userlevel' => 5 ]);
                                navbar_entry([ 'title' => 'Process List', 'url' => generate_url(['page' => 'processes']), 'icon' => $config['icon']['processes'], 'userlevel' => 7 ]);
                                navbar_entry([ 'title' => 'OSes', 'url' => generate_url(['page' => 'os']), 'icon' => $config['icon']['config'], 'userlevel' => 7 ]);
                                navbar_entry([ 'title' => 'MIBs', 'url' => generate_url(['page' => 'mibs']), 'icon' => $config['icon']['mibs'], 'userlevel' => 7 ]);

                                if (auth_can_logout()) {
                                    ?>
                                    <li class="divider"></li>
                                    <li><a href="<?php echo(generate_url(['page' => 'logout'])); ?>" title="Logout"><i
                                              class="<?php echo $config['icon']['logout']; ?>"></i> Logout</a></li>
                                    <?php
                                }
                                ?>
                                <li class="divider"></li>
                                <li><a href="<?php echo OBSERVIUM_DOCS_URL; ?>/" title="Help" target="_blank"><i
                                          class="<?php echo $config['icon']['help']; ?>"></i> Help</a></li>
                                <li><?php echo(generate_link('<i class="' . $config['icon']['info'] . '"></i> About ' . OBSERVIUM_PRODUCT, ['page' => 'about'], [], FALSE)); ?></li>
                            </ul>
                        </li>
                    </ul>
                </div><!-- /.nav-collapse -->
            </div>
        </div><!-- /navbar-inner -->
    </header>

    <script type="text/javascript">
        if (!Date.now) {
            Date.now = function () {
                return new Date().getTime();
            }
        }

        key_count_global = 0;
        key_press_time = Date.now()

        function lookup(inputString) {
            if (inputString.trim().length == 0) {
                $('#suggestions').fadeOut(); // Hide the suggestions box
            } else {
                key_count_global++;
                // Added timeout 0.3s before send query
                // Prevent use quotes in query string, for do not use XSS attacks
                setTimeout("lookupwait(" + key_count_global + ",\"" + inputString.replace(/"/g, '\\x22').replace(/'/g, '\\x27') + "\")", 300);
            }
            key_press_time = Date.now()
        }

        function lookupwait(key_count, inputString) {
            if (key_count == key_count_global && (Date.now() - key_press_time >= 300)) {
                $.post("ajax/search.php", {queryString: "" + inputString + ""}, function (data) { // Do an AJAX call
                    $('#suggestions').fadeIn(); // Show the suggestions box
                    $('#suggestions').html(data); // Fill the suggestions box
                });
            }
        }

        <?php
        if (isset($page_refresh['nexttime'])) // Begin Refresh JS
        {
        ?>

        // set initial seconds left we're counting down to
        var seconds_left = <?php echo($page_refresh['nexttime'] - time()); ?>;
        // get tag element
        var countrefresh = document.getElementById('countrefresh');

        // update the tag with id "countdown" every 1 second
        setInterval(function () {
            // do some time calculations
            var minutes = parseInt(seconds_left / 60);
            var seconds = parseInt(seconds_left % 60);

            // format countdown string + set tag value
            if (minutes > 0) {
                minutes = minutes + 'min&nbsp;';
                seconds = seconds + 'sec';
            } else {
                minutes = '';
                if (seconds > 0) {
                    seconds = seconds + 'sec';
                } else {
                    seconds = '0sec';
                }
            }

            countrefresh.innerHTML = '<div class="label">' + minutes + seconds + '</div>';

            seconds_left = seconds_left - 1;

        }, 1000);

        <?php
        } // End Refresh JS

        $menu_time = utime() - $menu_start;

        ?>

    </script>

<?php

//EOF
