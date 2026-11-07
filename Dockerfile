FROM php:8.3-cli
RUN apt-get update && apt-get install -y libsqlite3-dev && docker-php-ext-install pdo_sqlite && rm -rf /var/lib/apt/lists/*
WORKDIR /app
COPY . /app
RUN mkdir -p /app/uploads && chmod -R 777 /app
ENV PORT=8000
EXPOSE 8000
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT} -t /app"]
