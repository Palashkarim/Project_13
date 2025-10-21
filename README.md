# IoT Platform — Project Overview

This repository contains a self-hosted IoT/Device management platform.  
Parts intentionally **excluded** in this export: `infra/`, `server_core/`, `web_dashboard/`, `mqtt_servers/`, `db_servers/`, `windows/`, and `storage/`.

## What is included
- Top-level orchestration files (Docker Compose, .env.example)
- `k8s/` manifests (optional)
- `ai_engine/` Python microservice (FastAPI) for analytics and ML tasks
- `ota_repo/` simple OTA repository & metadata
- `notifications/` email/sms/push helper templates and PHP wrappers
- `security/` ACL helper scripts and intrusion detection skeletons
- `tools/` CLI scripts (PHP) and webhook receiver
- `backups/` scripts for snapshots and archival
- `documentation/` helpful guides and API docs
- `tests/` skeletons for unit/integration/e2e

## Quick start (local, minimal)
1. Copy `.env.example` → `.env` and update secret values.
2. `docker compose up --build` (requires server_core and web_dashboard images/containers to be present - those are skipped per request).
3. Use `ai_engine/` to run analytics services.

## Notes
- Replace placeholders and secrets before production.
- Many files include TODOs with clear instructions.
