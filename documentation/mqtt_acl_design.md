# MQTT ACL Design

- Tenant/topic format: `ten/{user_id}/dev/{device_id}/{tele|cmd|state}`
- ACL rules:
  - devices: publish to telemetry topics, subscribe to command topic
  - users (web): subscribe to telemetry and state, publish to command topics (when allowed)
- Implementation: either mosquitto acl.conf templates or DB-based ACL plugin.
