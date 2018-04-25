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