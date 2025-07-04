FROM php:8.4-fpm-bullseye


ENV POSTGRES_URI ""
ENV SENTRY_DSN ""
ENV ENVIRONMENT "production"
ENV SKIP_COMPOSER ""
ENV HOST ""
ENV PROTO "https"
ENV TZ "UTC"
ENV DEFAULT_LOCALE "en"
ENV LANG "en_US.UTF.8"
ENV MAXMIND_ACCOUNT_ID ""
ENV MAXMIND_LICENSE ""
ENV MAXMIND_EDITION_IDS "GeoLite2-ASN GeoLite2-City GeoLite2-Country"

ARG CRISP_WORKDIR="/var/www/crisp"
ARG IS_DOCKER=true
ARG REQUIRE_LICENSE=false
ARG GIT_COMMIT=not_set
ARG CI_BUILD=0


ENV CRISP_WORKDIR "$CRISP_WORKDIR"
ENV CI_BUILD "$CI_BUILD"
ENV GIT_COMMIT "$GIT_COMMIT"
ENV IS_DOCKER "$IS_DOCKER"
ENV REQUIRE_LICENSE "$REQUIRE_LICENSE"

WORKDIR "${CRISP_WORKDIR}"

VOLUME /data

# Install Dependencies
RUN echo 'pm.max_children = 200' >> /usr/local/etc/php-fpm.d/zz-docker.conf && \
    echo 'pm.start_servers = 50' >> /usr/local/etc/php-fpm.d/zz-docker.conf && \
    echo 'pm.min_spare_servers = 50' >> /usr/local/etc/php-fpm.d/zz-docker.conf && \
    echo 'pm.max_spare_servers = 150' >> /usr/local/etc/php-fpm.d/zz-docker.conf && \
    apt-get update && \
    apt-get install -o DPkg::Options::="--force-confold" --no-install-recommends -y git libfreetype6-dev libjpeg62-turbo-dev libpng-dev curl zip openssl libpq-dev libcurl4-openssl-dev libsodium-dev libzip-dev libicu-dev libssl-dev locales nginx nginx-extras wget sudo cowsay toilet cron && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
    pecl install excimer && \
    docker-php-ext-install gd bcmath curl gettext sodium zip pdo pdo_pgsql intl && \
    docker-php-ext-enable gd bcmath curl gettext sodium zip pdo pdo_pgsql intl excimer && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    wget 'https://github.com/maxmind/geoipupdate/releases/download/v5.1.1/geoipupdate_5.1.1_linux_amd64.deb' -O /tmp/geoipupdate.deb && \
    dpkg -i /tmp/geoipupdate.deb && \
    apt-get autoremove -y && \
    apt-get clean && \
    rm -rf /tmp/pear && \
    rm -rf /var/cache/apt/archives && \
    rm -rf /var/lib/apt/lists/* && \
    usermod -aG sudo www-data && \
    echo '%sudo ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers && \
    chown -R 33:33 "/var/www" && \
    mkdir -p /data && chown -R 33:33 "/data" && \
    mkdir -p /var/log/crisp && \
    chown -R 33:33 /var/log/crisp && \
    rm /etc/nginx/sites-enabled/default


COPY config/php.ini /usr/local/etc/php/conf.d/php_custom.ini
COPY config/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker /opt/entrypoint.d
COPY config/crisp-cli.sh /usr/local/bin/crisp-cli

RUN chmod +x /usr/local/bin/crisp-cli && \
    ln -s /usr/local/bin/crisp-cli /usr/local/bin/crisp


COPY . "$CRISP_WORKDIR"

ENTRYPOINT ["/bin/bash", "-c", "chmod +x /opt/entrypoint.d/*.sh; for script in /opt/entrypoint.d/*.sh; do $script; if [ $? -eq 255 ]; then exit 255; fi; done"]
