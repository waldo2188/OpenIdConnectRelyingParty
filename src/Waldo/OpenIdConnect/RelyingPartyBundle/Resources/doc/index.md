OpenID Connect Relying Party Bundle
===================================

## Install
Not a clue for at this time

## Configurations
```yaml
#/app/config/config.yml

waldo_oic_rp:
    http_client:
        timeout: 5
        verify_peer: null
        max_redirects: 5
        proxy: null
    base_url: http://my-web-site.tld/
    client_id: my_client_id #OpenID Connect client id given by the OpenId Connect Provider
    client_secret: my_client_secret #OpenID Connect client secret given by the OpenId Connect Provider
    issuer: https://openid-connect-provider.tld #URL of the OpenID Connect Provider
    endpoints_url:  #Part of the URL of the OpenID Connect Provider
        authorisation: /auth
        token: /token
        userinfo: /userinfo
    display: page #How the authentication form will be display to the enduser
    scope: openid profile email address phone #List of the scope you need
    authentication_ttl: 300 #Maximum age of the authentication
    token_ttl: 300 #Maximum age for tokenID
    userinfo_signed_response_alg: null #Algorihme for signing userinfo response (RS256)
    id_token_signed_response_alg: null #Algorihme for signing tokenID response (RS256)
    jwk_url: https://openid-connect-provider.tld/op.jwk #URL to the Json Web Key of OpenID Connect Provider
    jwk_cache_ttl 86400 #Validity periods in second where the JWK is valid
    private_rsa_path: null #Path to the private RSA key
    public_rsa_path: null #Path to the public RSA key
```

```yaml
#/app/config/security.yml
security:
    providers:
        OICUserProvider: 
            id: waldo_oic_rp.user.provider
            

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        secured_area:
            pattern: ^/
            anonymous: ~
            openidconnect:
                login_path:        /login
                failure_path:      /login
    
    access_control:
        - { path: ^/private-page, roles: ROLE_OIC_USER }
        - { path: ^/login$, roles: IS_AUTHENTICATED_ANONYMOUSLY }
```




## RSA Key
The rsa keys are optional, but it's better to have for higher security.
You don't need to buy them, just follow the tutorial on Github.
https://help.github.com/articles/generating-ssh-keys

TODO
====
Add logout mechanism


Not yet implemented
===================

Encryption / Decryption
-----------------------

Need to be implemented

For now `Id Token Signed Response Alg` must be set to `RS256`

In AbstractGenericOICResourceOwner, create a Request and Response type for
http_client who abstract all the logic of Encryption / Decryption.
Need to add all parameters like `Request Object Signing Alg` in config



Client Prepares Authentication Request
---------------------------------------
http://openid.net/specs/openid-connect-basic-1_0.html#AuthenticationRequest

This options parrameter need to be implemented
 - claims_locales
 - id_token_hint
 - login_hint
 - acr_values


ID Token Validation 
-------------------
http://openid.net/specs/openid-connect-basic-1_0.html#IDTokenValidation

The point 7 is not implemented.
> If the acr Claim was requested, the Client SHOULD check that the asserted Claim 
> Value is appropriate. The meaning and processing of acr Claim Values is out of 
> scope for this document.

