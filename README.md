# CrispCMS Dockerized
<h2>Next Generation Content Management Framework - Dockerized</h2>

⚠️ README work in progress ⚠️

## Run in development

To develop a theme, you can run crisp locally by mounting the theme folder to your workspace, example docker-compose file below

```yaml
version: '3.3'
services:

  crisp:
    image: 'd3a9ba1e348be819bb4fcf2a829a04007fa28086d0ba5'
    volumes:
      - my/local/theme/folder:/var/www/crisp/themes/crisptheme
    ports:
      - '80:80'
    links:
      - postgres
      - redis
    environment:
      - CRISP_THEME=crisptheme
      - VERBOSITY=3
      - POSTGRES_URI=postgres://postgres:postgres@postgres:5432/postgres
      - REDIS_HOST=redis
      - REDIS_INDEX=1
      - ENVIRONMENT=development
      - FLAGSMITH_APP_URL=https://flagsmith.internal.jrbit.de/api/v1/
      - CRISP_FLAGSMITH_API_KEY=K8BJRBi6xiE3HweHoRQhQA # API Key for the development environment

  redis:
    image: redis:latest
    restart: always

  postgres:
    image: postgres:latest
    restart: always
    environment:
      POSTGRES_PASSWORD: postgres

```

## Environment Variables

| Variable                | Description                                                                                                             | Optional | Default Value                               |
|-------------------------|-------------------------------------------------------------------------------------------------------------------------|----------|---------------------------------------------|
| CRISP_THEME             | The name of the theme to install                                                                                        | ❌        | crisptheme                                  |
| POSTGRES_URI            | JDBC Uri for the postgres database                                                                                      | ❌        |                                             |
| REDIS_HOST              | The hostname of the redis server                                                                                        | ❌        |                                             |
| REDIS_PORT              | The port of the redis server                                                                                            | ✔️       | 6379                                        |
| REDIS_INDEX             | The index of the redis server                                                                                           | ❌        |                                             |
| REDIS_AUTH              | The password of the redis server                                                                                        | ✔️       |                                             |
| REDIS_PREFIX            | For multiple installations the prefix of the keys                                                                       | ❌        | crispcms                                    |
| SENTRY_DSN              | Sentry datasource name for error tracing                                                                                | ✔️       |                                             |
| DEFAULT_LOCALE          | Default locale to use, default depends on the image.                                                                    | ❌        | de                                          |
| ENVIRONMENT             | The environment to initialize crisp in: production, staging, development. Defaults to production for security purposes. | ❌        | production                                  |
| ELASTIC_URI             | URL to the Elastic API                                                                                                  | ✔️       |                                             |
| ELASTIC_INDEX           | The index of your elastic instance to query                                                                             | ✔️       |                                             |
| GIT_COMMIT              | Built by CI: The current commit hash on build time.                                                                     | 🤖       | BUILT BY CI                                 |
| FLAGSMITH_APP_URL       | API Endpoint of the Flagsmith instance                                                                                  | ❌        | https://flagsmith.internal.jrbit.de/api/v1/ |
| CRISP_FLAGSMITH_APP_URL | API Endpoint of the Flagsmith instance for the CMS                                                                      | 🤖       | BUILT BY CI                                 |
| THEME_GIT_COMMIT        | Git commit of the theme, used by VERSION_STRING. MUST BE SET for themes                                                 | 🤖       | BUILT BY CI                                 |
| FLAGSMITH_API_KEY       | API Key for your theme instance                                                                                         | ✔️       |                                             |
