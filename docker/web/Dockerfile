FROM ubuntu:latest

ENV DEBIAN_FRONTEND noninteractive
RUN   apt-get update && \
      apt -y install software-properties-common && \
      add-apt-repository ppa:ondrej/php && \
      apt-get update && \
      apt-get install -y \
      apache2 \
      php7.4 \
      php7.4-cli \
      libapache2-mod-php7.4 \
      php-apcu \
      php-xdebug \
      php7.4-gd \
      php7.4-json \
      php7.4-ldap \
      php7.4-mbstring \
      php7.4-mysql \
      # php7.4-pgsql \
      # php7.4-sqlite3 \
      php7.4-xml \
      php7.4-xsl \
      php7.4-zip \
      # php7.4-soap \
      # php7.4-opcache \
      nano \
      sendmail \
      composer \
      php-curl

COPY apache-host.conf /etc/apache2/sites-available/000-default.conf
COPY run.sh /usr/local/bin/run
RUN chmod +x /usr/local/bin/run

EXPOSE 80
CMD ["/usr/local/bin/run"]
