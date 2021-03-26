# URI SSO

Alters WordPress to delegate authn to Apache / SSO.

The plugin has a few config options, but looks for the `REMOTE_USER` environment variable and authorizes users based on that value.

Note: local user accounts will no longer function, local accounts will need to become URI affiliates by filling in HR form USP-18.  In case I hit the lottery.