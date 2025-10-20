#!/usr/bin/env bash
set -euo pipefail
# Generate self-signed certs for testing (use Let's Encrypt in production)
DOMAIN="${1:-iot.local}"
CERT_DIR="./infra/nginx/certs"
mkdir -p "$CERT_DIR"

openssl req -x509 -newkey rsa:4096 -sha256 -days 365 \
  -nodes -keyout "${CERT_DIR}/server.key" \
  -out "${CERT_DIR}/server.crt" \
  -subj "/CN=${DOMAIN}"

cp "${CERT_DIR}/server.crt" "${CERT_DIR}/chain.crt" # dummy chain

echo "[OK] Self-signed TLS cert generated at ${CERT_DIR}."
