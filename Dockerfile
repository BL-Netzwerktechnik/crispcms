FROM php:8.4-fpm-trixie


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
ARG GIT_COMMIT=not_set
ARG CI_BUILD=0
ARG FRAMEWORK_VERSION=0.0.0

ENV CRISP_WORKDIR "$CRISP_WORKDIR"
ENV CI_BUILD "$CI_BUILD"
ENV GIT_COMMIT "$GIT_COMMIT"
ENV IS_DOCKER "$IS_DOCKER"
ENV FRAMEWORK_VERSION "$FRAMEWORK_VERSION"

WORKDIR "${CRISP_WORKDIR}"

VOLUME /data

COPY . "$CRISP_WORKDIR"

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Update and install base dependencies
RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -o DPkg::Options::="--force-confold" --no-install-recommends -y \
    git curl zip openssl locales nginx nginx-extras wget sudo cowsay toilet cron zip unzip && \
    rm -rf /var/lib/apt/lists/*

RUN install-php-extensions gd bcmath curl gettext sodium zip pdo pdo_pgsql intl excimer 

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install GeoIPUpdate
RUN wget 'https://github.com/maxmind/geoipupdate/releases/download/v7.1.1/geoipupdate_7.1.1_linux_amd64.deb' -O /tmp/geoipupdate.deb && \
    dpkg -i /tmp/geoipupdate.deb && \
    rm -rf /var/lib/apt/lists/* /var/cache/apt/archives /tmp/*

# Configure system and app
RUN usermod -aG sudo www-data && \
    echo '%sudo ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers && \
    cd "/var/www/crisp" && \
    /usr/local/bin/composer install && \
    chown -R 33:33 "/var/www" && \
    mkdir -p /data && chown -R 33:33 "/data" && \
    mkdir -p /var/log/crisp && \
    chown -R 33:33 /var/log/crisp && \
    rm /etc/nginx/sites-enabled/default


COPY config/php.ini /usr/local/etc/php/conf.d/zz-docker.ini
COPY config/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker /opt/entrypoint.d
COPY config/crisp-cli.sh /usr/local/bin/crisp-cli

RUN chmod +x /usr/local/bin/crisp-cli && \
    ln -s /usr/local/bin/crisp-cli /usr/local/bin/crisp

ENTRYPOINT ["/bin/bash", "-c", "chmod +x /opt/entrypoint.d/*.sh; for script in /opt/entrypoint.d/*.sh; do $script; if [ $? -eq 255 ]; then exit 255; fi; done"]
