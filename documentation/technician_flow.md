# Technician Flow

Steps:
1. Search user by email or id.
2. Confirm plan & allowed widgets.
3. Choose hardware (esp32/esp8266).
4. Select widgets (map → pin assignments).
5. Provide WiFi & MQTT credentials (or use onboarding QR).
6. Generate firmware -> ZIP -> flash.

Security:
- Never log WiFi password in plaintext.
- Use ephemeral OTA keys per build.
