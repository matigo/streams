# The Authentication API

##### Read this section to learn about the Authentication endpoints and their features

### Sections

1. Getting an Access Token
2. Authentication Procedure
3. Logging Out
4. Checking Token Status

### Access Tokens

Access tokens are used to authenticate requests against the 10Centuries API, and a `Channel GUID`[1. A `Channel GUID` is a Channel's unique identifier. [Learn more about Channels here]([HOMEURL]/channels).] is used during the authentication process to create an Access Token. Every [Channel]([HOMEURL]/channels) has a 36-character global unique identifier and can be found in the `<head>` of any 10Centuries-based web site as a `<meta>` record. For example, this website's Channel GUID is as follows:

```
<meta name="channel_guid" content="[CHANNEL_GUID]" />;
```

For applications that sign a person into a 10Centuries-based web site, it is recommended to ask for the site's URL, then programmatically read the `<meta>` tags.

Note that not all API endpoints require the use of an Access Token, but all API endpoints will accept them.

### Getting an Access Token

Access tokens are currently only granted through the 10Centuries API by passing a valid `Channel GUID` along with an account's login and password. This is referred to as a [Resource Owner Password Credential Grant](https://tools.ietf.org/html/rfc6749#section-4.3) in the OAuth 2.0 specification. Logins are currently only in the form of email addresses. Regardless of how many Personas[2. a Persona is a public-facing aspect of an Account. An Account can have an unlimited number of Personas, but a Persona can only be assocated with one Account. [Learn more about Personas here]([HOMEURL]/personas).] or websites a person may have, they will only need one access token to access them all.

### Authentication Procedure

From your application, send a `POST` request to the API of the website you'd like to connect to. For example, if a person is trying to sign into example.web, the request would be sent to the following location:

```
POST https://example.web/api/auth/login
```

Required variables:

```
account_name={email address of Account}
account_pass={password for the Account}
channel_guid={Channel identifier}
```

This information can be sent URL-encoded in the POST body or as a JSON package.

For example:

```
curl -X POST -H "Content-Type: application/x-www-form-urlencoded" \
     -d "channel_guid={36-character Channel ID}" \
     -d "account_name=person@email.address" \
     -d "account_pass=correct horse battery staple" \
     "https://example.web/api/auth/login"
```

If the authentication attempt is successful, the API will respond with a JSON package:

```
{
  "meta": {
    "code": 200,
    "text": false,
    "list": false
  },
  "data": {
    "token": "{A Valid Authentication Token}",
    "lang_cd": "en-us"
  }
}
```

If the `meta.code` value is not `200`, the `text` item will show the a single error. If there is more than one error to resolve, the `list` item will be an array of issues to resolve.

### Logging Out

When a person logs out of their account, the Access Token will be expired and no longer available for use. This can be performed by sending a `POST` request to the API of the website. For example, if a person is trying to log out from example.web, the request would be sent to the following location:

```
POST https://example.web/api/auth/logout
```

No variables are required for this endpoint, but the Access Token must be included. This information can be sent in the HTTP Header or in the POST Body using the `token` variable name.

For example:

```
curl -X POST -H "Authorization: {Authorization Token}" \
     "https://example.web/api/auth/logout"
```

If the process was successful, the API will respond with a JSON package:

```
{
  "meta": {
    "code": 200,
    "text": false,
    "list": false
  },
  "data": {
    "account": false,
    "distributors": false,
    "is_active": false,
    "updated_at": "[NOW_AT]",
    "updated_unix": [NOW_UNIX]
  }
}
```

### Checking an Access Token's Status

Once an Access Token is granted, they remain valid until authorization is revoked by the Account-holder, or until they have been idle for 30 days from the time of creation. Validity of an Access Token can always be performed by sending a `GET` request to the API of the website. For example, if a person would like to use example.web, the request would be sent to the following location:

```
GET https://example.web/api/auth/status
```

No variables are required for this endpoint, but the Access Token must be included. This information can be sent in the HTTP header or as part of the URL string using the `token` variable name.

For example:

```
curl -X GET -H "Authorization: {Authorization Token}" \
     "https://example.web/api/auth/status"
```

If the Access Token is still valid, the API will respond with a JSON package:

```
{
  "meta": {
    "code": 200,
    "text": false,
    "list": false
  },
  "data": {
    "account": {an Account object},
    "distributors": [{a list of Channels where the Account can be used}],
    "is_active": true,
    "updated_at": "[NOW_AT]",
    "updated_unix": [NOW_UNIX]
  }
}
```