#!/usr/bin/env php
<?php
/**
 * sync_acls.php
 * ------------------------------------------------------------
 * One-shot: generate ACLs (API→local fallback) and SIGHUP brokers.
 * Cron-friendly.
 *
 *   */5 * * * * /path/to/IOT_PLATFORM/mqtt_servers/cluster/sync_acls.php
 */

$mgr = __DIR__.'/mqtt_cluster_manager.php';
if (!file_exists($mgr)) { fwrite(STDERR, "Manager not found\n"); exit(1); }

echo "== Generate ACLs from API (fallback local) ==\n";
passthru(escapeshellcmd($mgr).' gen-acl --source=api', $rc);
if ($rc !== 0) { fwrite(STDERR, "gen-acl failed\n"); exit(2); }

echo "== Reload brokers ==\n";
passthru(escapeshellcmd($mgr).' reload', $rc);
if ($rc !== 0) { fwrite(STDERR, "reload failed (broker may not be running?)\n"); }