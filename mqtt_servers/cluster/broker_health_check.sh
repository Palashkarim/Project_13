Lightweight health probe:

    checks TCP and WS ports with nc (no external deps)

    optional $SYS test if mosquitto_sub is present

    prints a concise status line per broker

#!/usr/bin/env bash
#
# broker_health_check.sh
# ------------------------------------------------------------
# Checks each broker defined in cluster_config.json for:
#  - TCP port open
#  - WS port open
#  - TLS port open (if defined)
# Optional: if mosquitto_sub exists and you pass creds, tests $SYS topic.
#
# Usage:
#   ./broker_health_check.sh
#   MQTT_USER="u:123" MQTT_PASS="..." ./broker_health_check.sh  # optional $SYS test

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CFG="$ROOT/cluster_config.json"

have_jq=0
if command -v jq >/dev/null 2>&1; then have_jq=1; fi

if [[ $have_jq -eq 0 ]]; then
  echo "NOTE: jq not found. Using php to parse JSON."
fi

function json_get_brokers() {
  if [[ $have_jq -eq 1 ]]; then
    jq -c '.brokers[]' "$CFG"
  else
    php -r '
      $j=json_decode(file_get_contents($argv[1]),true);
      foreach($j["brokers"] as $b) echo json_encode($b,JSON_UNESCAPED_SLASHES).PHP_EOL;
    ' "$CFG"
  fi
}

function port_open() {
  local host="$1" port="$2"
  if command -v nc >/dev/null 2>&1; then
    nc -z -w2 "$host" "$port" >/dev/null 2>&1
    return $?
  else
    # Fallback: bash /dev/tcp (not in all shells)
    (echo > /dev/tcp/"$host"/"$port") >/dev/null 2>&1
    return $?
  fi
}

function sys_uptime() {
  # optional
  if ! command -v mosquitto_sub >/dev/null 2>&1; then
    echo "-"
    return
  fi
  local host="$1" port="$2"
  local user="${MQTT_USER:-}"; local pass="${MQTT_PASS:-}"
  if [[ -z "$user" || -z "$pass" ]]; then
    echo "-"
    return
  fi
  timeout 2 mosquitto_sub -h "$host" -p "$port" -u "$user" -P "$pass" -t '$SYS/broker/uptime' -C 1 2>/dev/null || true
}

json_get_brokers | while read -r row; do
  id=$(echo "$row" | php -r '$r=json_decode(stream_get_contents(STDIN),true);echo $r["id"];')
  host=$(echo "$row" | php -r '$r=json_decode(stream_get_contents(STDIN),true);echo $r["host"];')
  tcp=$(echo "$row" | php -r '$r=json_decode(stream_get_contents(STDIN),true);echo $r["tcp_port"];')
  ws=$(echo  "$row" | php -r '$r=json_decode(stream_get_contents(STDIN),true);echo $r["ws_port"];')
  tls=$(echo "$row" | php -r '$r=json_decode(stream_get_contents(STDIN),true);echo isset($r["tls_port"])?$r["tls_port"]:"";')

  status_tcp="DOWN"; port_open "$host" "$tcp" && status_tcp="UP"
  status_ws="DOWN";  port_open "$host" "$ws"  && status_ws="UP"
  status_tls="-"
  if [[ -n "$tls" ]]; then
    status_tls="DOWN"; port_open "$host" "$tls" && status_tls="UP"
  fi

  sys=$(sys_uptime "$host" "$tcp")
  printf "%-8s host=%-15s tcp=%-3s[%s] ws=%-4s[%s] tls=%-4s[%s] SYS:%s\n" \
    "$id" "$host" "$tcp" "$status_tcp" "$ws" "$status_ws" "${tls:-'-'}" "$status_tls" "${sys:-'-'}"
done

Make it executable:

chmod +x broker_health_check.sh
