# Data Export Guide

- Export jobs are created via /api/exports.
- Jobs are queued; export worker picks up, writes CSV/JSON to EXPORT_JOBS_DIR.
- Users limited by plan export_window_days and export rate limits.
