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

## How to install
### 1. Install the schema
First, create a separate database, and a connection in the configuration for emailQueue. Then:
```./bin/cake migrations migrate -p Passbolt/MultiTenantAdmin```
```./bin/cake migrations migrate --plugin EmailQueue --connection emailQueue```

### 2. Migrate existing schemas
```./bin/cake multi_tenant migrate_organizations```

## How to use
### 1. Use the shell to create an organization
```bin/cake multi_tenant add_organization --name=acme```

### 2. Create a user
```./bin/cake passbolt register_user --org=acme --first-name=Firstname --last-name=Lastname --username=name@email.com --role=admin```

### 3. For prod, don't forget to set the crontab for this instance.
