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
|  ENVIRONMENT            | The environment to initialize crisp in: production, staging, development. Defaults to production for security purposes. | ❌        | production                                  |
| ELASTIC_URI             | URL to the Elastic API                                                                                                  | ✔️       |                                             |
| ELASTIC_INDEX           | The index of your elastic instance to query                                                                             | ✔️       |                                             |
| LICENSE_DATA            | Echos the content of LICENSE_DATA to the LICENSE_FILE                                                                   | ✔️       |                                             |
| GIT_COMMIT              | Built by CI: The current commit hash on build time.                                                                     | 🤖       | BUILT BY CI                                 |
| FLAGSMITH_APP_URL       | API Endpoint of the Flagsmith instance                                                                                  | ❌        | https://flagsmith.internal.jrbit.de/api/v1/ |
| CRISP_FLAGSMITH_APP_URL | API Endpoint of the Flagsmith instance for the CMS                                                                      | 🤖       | BUILT BY CI                                 |
| THEME_GIT_COMMIT        | Git commit of the theme, used by VERSION_STRING. MUST BE SET for themes                                                 | 🤖       | BUILT BY CI                                 |
| FLAGSMITH_API_KEY       | API Key for your theme instance                                                                                         | 🤖       | BUILT BY CI                                 |
