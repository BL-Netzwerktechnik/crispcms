FROM php:8.1-fpm


ENV POSTGRES_URI ""
ENV REDIS_HOST ""
ENV REDIS_PORT "6379"
ENV REDIS_INDEX ""
ENV SENTRY_DSN ""
ENV ENVIRONMENT "production"
ENV REDIS_AUTH ""
ENV REDIS_PREFIX "crispcms_"
ENV ELASTIC_URI ""
ENV ELASTIC_INDEX ""
ENV FLAGSMITH_APP_URL ""
ENV SKIP_COMPOSER ""
ENV HOST ""
ENV PROTO "https"
ENV TZ "UTC"

ARG BUILD_TYPE=0
ARG IS_DOCKER=true
ARG GIT_COMMIT=not_set
ARG DEFAULT_LOCALE="en"
ARG LANG="en_US.UTF.8"
ARG CRISP_FLAGSMITH_APP_URL="https://flagsmith.internal.jrbit.de/api/v1/"
ARG CRISP_FLAGSMITH_API_KEY="PDj3dJjVc6XPjK4f6FStPz"
ARG CRISP_THEME="crisptheme"

ENV BUILD_TYPE "$BUILD_TYPE"
ENV CRISP_THEME "$CRISP_THEME"
ENV DEFAULT_LOCALE "$DEFAULT_LOCALE"
ENV LANG "$LANG"
ENV CRISP_FLAGSMITH_APP_URL "$CRISP_FLAGSMITH_APP_URL"
ENV CRISP_FLAGSMITH_API_KEY "$CRISP_FLAGSMITH_API_KEY"
ENV CRISP_THEME "$CRISP_THEME"
ENV GIT_COMMIT "$GIT_COMMIT"
ENV IS_DOCKER "$IS_DOCKER"

# Install Dependencies

RUN apt-get update && \
    apt-get install -y \
            git \
            libfreetype6-dev \
            libjpeg62-turbo-dev \
            libpng-dev \
            curl \
            zip \
            openssl \
            libpq-dev \
            libcurl4-openssl-dev \
            libsodium-dev \
            libzip-dev \
            libicu-dev \
            locales \
            nginx && \
            sed -i -e "s/# $LANG UTF-8/$LANG UTF-8/" /etc/locale.gen && \
            dpkg-reconfigure --frontend=noninteractive locales && \
            update-locale LANG="$LANG" && \
            pecl install -o -f redis && \
            docker-php-ext-configure gd --with-freetype --with-jpeg && \
            docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
            docker-php-ext-configure curl && \
            docker-php-ext-configure sodium && \
            docker-php-ext-install gd bcmath curl gettext sodium zip pdo pdo_pgsql intl && \
            docker-php-ext-enable gd bcmath curl gettext sodium zip redis pdo pdo_pgsql intl && \
            curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
            apt-get autoremove -y && \
            apt-get clean && \
            rm -rf /tmp/pear && \
            rm -rf /var/cache/apt/archives && \
            rm -rf /var/lib/apt/lists/*

COPY config/php.ini /usr/local/etc/php/conf.d/php_custom.ini
COPY config/nginx.conf /etc/nginx/conf.d/default.conf

COPY cms /var/www/crisp
COPY docker /opt/entrypoint.d

RUN rm /etc/nginx/sites-enabled/default
RUN ["chmod", "+x", "/opt/entrypoint.d/entrypoint.sh"]
RUN ["chmod", "+x", "/opt/entrypoint.d/bootstrap.sh"]


RUN ["/bin/bash", "-c", "/opt/entrypoint.d/bootstrap.sh"]

WORKDIR /var/www/crisp

ENTRYPOINT ["/bin/bash", "-c", "/opt/entrypoint.d/entrypoint.sh"]
