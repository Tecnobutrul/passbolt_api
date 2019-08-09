# Using docker
## Run using the docker compose
Run the container using:
```
docker-compose -f docker-compose-dev.yml up
```

This will use the configuration files in the `docker/conf` directory, by maping `config/passbolt.php` to
`docker/conf/passbolt.php`.

You can also use the environment variables in `docker/env/passbolt.env` alternatively.
By default it will fetch the composer dependencies, if you do not want this behavior you can
set the env `PHP_COMPOSER` to false.

To connect to the running container:
`docker-compose -f docker-compose-dev.yml exec passbolt-cloud bash`

Similarly to connect to mysql:
`docker-compose -f docker-compose-dev.yml exec db bash`

# Manual configuration
##Configure Nginx
For multi-tenant to work in Nginx, the following rewrite rules need to be added to the configuration file.
```
location ~* \.(jpe?g|woff|woff2|ttf|gif|png|bmp|ico|css|js|ejs|json|pdf|zip|htm|html|docx?|xlsx?|pptx?|txt|wav|swf|svg|avi|woff2|mp\d)$ {
    access_log off;
    log_not_found off;

    rewrite ^/([^/]+)/([img|css|js|fonts]+)/(.*)$ /$2/$3 break;
    rewrite ^/([^/]+)/favicon.ico$ /favicon.ico break;

    try_files $uri /webroot/$uri /index.php?$args;
  }
```

## Configure Apache
For apache, a similar configuration needs to be done. These 2 lines need to be translated in apache language:
```
rewrite ^/([^/]+)/([img|css|js|fonts]+)/(.*)$ /$2/$3 break;
rewrite ^/([^/]+)/favicon.ico$ /favicon.ico break;
```

The main idea is to remove the site from the path so that cakephp understands where it should be accessed.

# How to use

By default the container create an organization database while starting. An administrator is also created
and a link to complete the administrator setup can be found in the output of the container.

The link looks like: https://cloud.passbolt.com/acme/setup/install/5820c265-629b-4b99-bd2f-e238a34616d7/d30573c5-3829-4c92-b6e7-288fdf0d0b7a

# Production

## Emails

This container is responsible for sending email. Don't forget to enable the cronjob.

## Cache configuration

The cache configuration needs to be isolated for each organization. To do so, DO NOT FORGET to set each cache configuration with a path.
The correct cache option to do so is as below:
```
'prefix' => CACHE_PREFIX_ORG
```

CACHE_PREFIX_ORG will be set at bootstrap with a unique value for each org.

# Troubleshooting
The clear cache function does not work for now with organization. It has to be fixed
