FROM gitpod/workspace-full

RUN sudo install-packages php-xdebug && sudo pecl install -o -f redis && echo "extension=redis.so" | sudo tee -a "/etc/php/8.1/cli/conf.d/20-redis.ini" > /dev/null

RUN sudo update-alternatives --set php $(which php8.1)

RUN sudo rm -rf /var/lib/apt/lists/*


COPY config/crisp-cli.sh /usr/local/bin/crisp-cli

RUN ["chmod", "+x", "/usr/local/bin/crisp-cli"]
