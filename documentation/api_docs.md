# API Documentation (short)

- POST /api/login { email, password } -> { token }
- GET  /api/profile
- GET  /api/widgets
- POST /api/technician/codegen { user_id, hardware, wifi_ssid, wifi_password, mqtt_user, mqtt_password, widgets[] }
- GET  /api/technician/firmware/{buildId}/download
- POST /api/exports -> { job_id }

(See server_core/ README and controllers for full list)
