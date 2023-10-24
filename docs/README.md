# CrispCMS Dockerized

CrispCMS is a powerful content management system that provides an API framework for deploying APIs. It is designed to be scalable and runs in a Docker environment. The CMS also includes the following features:

- Twig Templating System: CrispCMS utilizes the Twig templating engine, which allows for flexible and dynamic content rendering.
- Storage System: The CMS provides a built-in storage system for managing and organizing key values.
- Translation System: CrispCMS includes a translation system that allows for easy localization of content.
- API System: Easily deploy an API with CrispCMS' built in API Framework

## Installation

To install CrispCMS, follow these steps:

1. Install Docker on your machine if you haven't already.
2. Run the following compose file
4. Open localhost:80 in your browser and enjoy developing

```yaml
version: '3.3'
services:

  crisp:
    image: 'registry.jrbit.de/crispcms/core:latest'
    volumes:
      - my/local/theme/folder:/var/www/crisp/themes/crisptheme
      - ./data:/data
    ports:
      - '80:80' # Frontend
      - '81:81' # API
    links:
      - postgres
      - redis
    environment:
      - CRISP_THEME=crisptheme
      - LOG_LEVEL=INFO
      - POSTGRES_URI=postgres://postgres:postgres@postgres:5432/postgres
      - REDIS_HOST=redis
      - REDIS_INDEX=1
      - ENVIRONMENT=development
      - DEFAULT_LOCALE=en
      - LANG=en_US.UTF-8

  redis:
    image: redis:latest
    restart: always

  postgres:
    image: postgres:latest
    restart: always
    environment:
      POSTGRES_PASSWORD: postgres

```