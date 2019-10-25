FROM php:7.3-apache

RUN apt-get update

RUN apt-get -y install locales
RUN echo "en_US.UTF-8 UTF-8" >> /etc/locale.gen
RUN echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen
RUN echo "en_GB.UTF-8 UTF-8" >> /etc/locale.gen
RUN echo "fi_FI.UTF-8 UTF-8" >> /etc/locale.gen
RUN echo "sv_SE.UTF-8 UTF-8" >> /etc/locale.gen
RUN locale-gen

RUN a2enmod rewrite
RUN docker-php-ext-install gettext
