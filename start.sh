#!/bin/bash

# Lancer PHP-FPM en arrière-plan
php-fpm -D

# Lancer nginx en avant-plan
nginx -g "daemon off;"