

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

