## Ship a License

If you want to ship CrispCMS with the License System you can either

1. Use the prebuilt `licensed` image
2. Build the dockerfile yourself with the `REQUIRE_LICENSE=true` build arg.


The licensed Image makes sure that CrispCMS can only be run with a valid license!


### Pre-Package issuer.pub

Its also possible to pack your issuer public key in your dockerimage

```Dockerfile
FROM registry.jrbit.de/crispcms/core/licensed:dev # Make sure to use a licensed Image!

ENV THEME_GIT_COMMIT "$THEME_GIT_COMMIT" # Optional
ENV THEME_GIT_TAG "$THEME_GIT_TAG" # Optional
ENV DEFAULT_LOCALE "de"
ENV LANG "de_DE.UTF-8"

COPY issuer.pub /issuer.pub # This pre-installs your issuer.pub for crisp!

COPY theme /var/www/crisp/themes/crisptheme
```