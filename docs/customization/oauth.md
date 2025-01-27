## GitHub

To enable GitHub OAuth, add:

```yaml
environment:
  - AUTHENTIK_CLIENT_ID=CLIENT_ID # change id
  - AUTHENTIK_CLIENT_SECRET=CLIENT_SECRET # change secret
  - AUTHENTIK_REDIRECT_URI=http://localhost/oauth/authentik/callback # change domain and provider
  - AUTHENTIK_BASE_URL=http://your-authentik-url # change url
```

## Keycloak

To enable Keycloak OAuth, add:

```yaml
environment:
  - KEYCLOAK_CLIENT_ID=CLIENT_ID # change id
  - KEYCLOAK_CLIENT_SECRET=CLIENT_SECRET # change secret
  - KEYCLOAK_REDIRECT_URI=http://localhost/oauth/keycloak/callback # change domain and provider
  - KEYCLOAK_BASE_URL=http://your-keycloak-url # change url
  - KEYCLOAK_REALM=YOUR_REALM # change realm
```

## Zitadel

To enable Zitadel OAuth, add:

```yaml
environment:
  - ZITADEL_CLIENT_ID=CLIENT_ID # change id
  - ZITADEL_CLIENT_SECRET=CLIENT_SECRET # change secret
  - ZITADEL_REDIRECT_URI=http://localhost/oauth/zitadel/callback # change domain and provider
  - ZITADEL_BASE_URL=http://your-zitadel-url # change url
  - ZITADEL_ORGANIZATION_ID=ORGANIZATION_ID # change id (optional configuration)
  - ZITADEL_PROJECT_ID=PROJECT_ID # change id (optional configuration)
```
