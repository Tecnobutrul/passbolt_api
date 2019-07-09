## Multi Tenant Analytics
This plugin is designed to collect analytics for a given organization and send it to a configured entry point.
The entry point can be anywhere and needs to be https with a Basic Auth. At the time of writing this document, the 
entry point is a google function.

### How to run it?
./bin/cake multi_tenant_analytics send --org=acme

### Configuration
To run, it requires the following environment variables to be set:
- PASSBOLT_MULTI_TENANT_ANALYTICS_ENTRYPOINT_URL
- PASSBOLT_MULTI_TENANT_ANALYTICS_ENTRYPOINT_USERNAME
- PASSBOLT_MULTI_TENANT_ANALYTICS_ENTRYPOINT_PASSWORD

### Analytics format
The analytics are sent following this json format:
```javascript
{
    'active_users_count' : 1
}
```
