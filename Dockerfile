ARG BASE_IMAGE_TAG=9.3
FROM wengerk/drupal-for-contrib:${BASE_IMAGE_TAG}

ARG BASE_IMAGE_TAG
ENV BASE_IMAGE_TAG=${BASE_IMAGE_TAG}

# Disable deprecation notice.
ENV SYMFONY_DEPRECATIONS_HELPER=disabled

# Install ezyang/htmlpurifier as required by entity_to_text
RUN COMPOSER_MEMORY_LIMIT=-1 composer require "ezyang/htmlpurifier:^4.14"

# Install vaites/php-apache-tika as required by entity_to_text_tika
RUN COMPOSER_MEMORY_LIMIT=-1 composer require "vaites/php-apache-tika:^1.2"

# Install drupal/paragraphs as required by entity_to_text_paragraphs
RUN COMPOSER_MEMORY_LIMIT=-1 composer require "drupal/paragraphs:^1.14"
RUN COMPOSER_MEMORY_LIMIT=-1 composer require --dev "drupal/entity_browser"

# Register the Drupal and DrupalPractice Standard with PHPCS.
#RUN ./vendor/bin/phpcs --config-set installed_paths \
#    `pwd`/vendor/drupal/coder/coder_sniffer

# Copy the Analyzer definition files to ease run.
COPY phpcs.xml.dist phpmd.xml ./

# Download & install PHPMD.
RUN set -eux; \
  curl -LJO https://phpmd.org/static/latest/phpmd.phar; \
  chmod +x phpmd.phar; \
  mv phpmd.phar /usr/bin/phpmd

# Download & install PHPCPD.
RUN set -eux; \
  curl -LJO https://phar.phpunit.de/phpcpd.phar; \
  chmod +x phpcpd.phar; \
  mv phpcpd.phar /usr/bin/phpcpd

# Download & install PhpDeprecationDetector.
RUN set -eux; \
  \
  apt-get update; \
  apt-get install -y \
   libbz2-dev \
  ; \
  \
  docker-php-ext-install bz2; \
  \
  curl -LJO https://github.com/wapmorgan/PhpDeprecationDetector/releases/download/2.0.24/phpcf-2.0.24.phar; \
  chmod +x phpcf-2.0.24.phar; \
  mv phpcf-2.0.24.phar /usr/bin/phpdd
