# ai_engine/Dockerfile
# Lightweight Python image for the AI microservice.
FROM python:3.11-slim

RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential gcc libpq-dev && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# copy source
COPY . /app

# non-root user for security
RUN useradd -ms /bin/bash appuser
USER appuser

ENV APP_PORT=8082 \
    MODEL_DIR=/app/models \
    DB_HOST=postgres \
    DB_PORT=5432 \
    DB_NAME=iot \
    DB_USER=postgres \
    DB_PASS=password \
    DEFAULT_TARIFF_PER_KWH=0.12 \
    AI_TOKEN=""

EXPOSE 8082

CMD ["python", "-m", "uvicorn", "api:app", "--host", "0.0.0.0", "--port", "8082"]
