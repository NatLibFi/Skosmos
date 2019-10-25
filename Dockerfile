FROM php:7.3-apache

RUN apt-get update && \
  apt-get -y install locales && \
  for locale in en_GB en_US fi_FI fr_FR sv_SE; do \
    echo "${locale}.UTF-8 UTF-8" >> /etc/locale.gen ; \
  done && \
  locale-gen

RUN a2enmod rewrite
RUN docker-php-ext-install gettext
