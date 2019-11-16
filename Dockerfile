FROM php:7.2-apache-stretch
RUN apt-get update \
    && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql

# コンテナ内のディレクトリ配置については、
# 元のDockerイメージのマニュアルを参照してください。
COPY ./app   /var/www/html
COPY ./data  /var/www/data