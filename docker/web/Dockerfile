FROM ubuntu:19.10

ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get update && \
    apt-get install -y \
      apache2 \
      php7.3 \
      php7.3-cli \
      libapache2-mod-php7.3 \
      php-apcu \
      php-xdebug \
      php7.3-gd \
      php7.3-json \
      php7.3-ldap \
      php7.3-mbstring \
      php7.3-mysql \
      # php7.3-pgsql \
      # php7.3-sqlite3 \
      php7.3-xml \
      php7.3-xsl \
      php7.3-zip \
      # php7.3-soap \
      # php7.3-opcache \
      nano \
      sendmail \
      composer \
      php-curl

COPY apache-host.conf /etc/apache2/sites-available/000-default.conf
COPY run.sh /usr/local/bin/run
RUN chmod +x /usr/local/bin/run

EXPOSE 80
CMD ["/usr/local/bin/run"]
