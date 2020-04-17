FROM php:7.3-cli-alpine

RUN docker-php-ext-install pcntl
