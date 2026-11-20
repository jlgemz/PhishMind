FROM python:3.10-slim

WORKDIR /app

# Install build dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

# Support builds from repo root OR from inside ml_service folder
COPY requirements.txt* ml_service/requirements.txt* ./
RUN if [ -f ml_service/requirements.txt ]; then cp ml_service/requirements.txt requirements.txt; fi \
    && pip install --no-cache-dir -r requirements.txt

# Copy service code
COPY . ./
RUN if [ -d "/app/ml_service" ]; then cp -rn /app/ml_service/* /app/ 2>/dev/null || true; fi

# Default port 8001 (can be overridden by PORT env)
ENV PORT 8001
EXPOSE 8001

CMD ["sh", "-c", "uvicorn app:app --host 0.0.0.0 --port ${PORT:-8001}"]
