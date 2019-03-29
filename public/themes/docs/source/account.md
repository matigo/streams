# The Account API

##### Read this section to learn about the Accounts endpoints and their features

### Sections

1. Get a Public Bio
2. Set Your Public Bio
3. Get Your Account Profile
4. Set Your Account Profile

### Get a Public Bio

A public profile is one that is shown to anyone who supplies a valid 36-character [Persona]([HOMEURL]/personas) GUID and returns only the public aspects of a Persona and its associated account.

From your application, send a `GET` request to the API of the website you'd like to connect to. For example, if a person is trying to read a profile from example.web, the request would be sent to the following location:

```
GET https://example.web/api/account/{Persona GUID}/bio
```

No variables are required for this endpoint, as the `Persona GUID` value is part of the request URL. If an Access Token is provided, additional information may be returned in the JSON body depending on Follower/Following permissions.

Example:

```
curl -X GET -H "Authorization: {Authorization Token}" \
     "https://example.web/api/account/{Persona GUID}/bio"
```

If the `Persona GUID` is valid, the API will respond with a JSON package:

```
{
  "meta": {
    "code": 200,
    "text": false,
    "list": false
  },
  "data": {
    "guid": "{Persona GUID}",
    "timezone": "Asia/Tokyo",
    "as": "@examplo",
    "name": "Examplo the Elf",
    "avatar_url": "http://example.web/avatars/elf.jpg",
    "site_url": "http://example.web",
    "bio": {
      "text": "I'm an example of an elf, but *don't* call me a dwarf!",
      "html": "<p>I'm an example of an elf, but <em>don't</em> call me a dwarf!</p>"
    },
    "days": [JOIN_DAYS],
    "is_you": false,
    "created_at": "[JOIN_AT]",
    "created_unix": [JOIN_UNIX]
  }
}
```

### Set Your Public Bio

A Markdown-formatted message up to 2,048-characters in length can be set as a public-facing biography for any Persona assocated with your account. If an empty string is passed, then the bio will be considered "deleted" and removed from the Persona record. This can be done by sending a `POST` request to the API of a website associated with a Persona. For example, if a person is using example.web, the request would be sent to the following location:

```
POST https://example.web/api/account/{Persona GUID}/bio
```

Required variables:

```
bio_text={a Markdown-formatted message up to 2,048-characters}
```

This information can be sent URL-encoded in the POST body or as a JSON package.

For example:

```
curl -X POST -H "Content-Type: application/x-www-form-urlencoded" \
     -H "Authorization: {Authorization Token}" \
     -d "bio_text=I'm an example of an elf, but *don't* call me a dwarf!" \
     "https://example.web/api/account/{Persona GUID}/bio"
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
    "guid": "{Persona GUID}",
    "timezone": "Asia/Tokyo",
    "as": "@examplo",
    "name": "Examplo the Elf",
    "avatar_url": "http://example.web/avatars/elf.jpg",
    "site_url": "http://example.web",
    "bio": {
      "text": "I'm an example of an elf, but *don't* call me a dwarf!",
      "html": "<p>I'm an example of an elf, but <em>don't</em> call me a dwarf!</p>"
    },
    "days": [JOIN_DAYS],
    "is_you": false,
    "created_at": "[JOIN_AT]",
    "created_unix": [JOIN_UNIX]
  }
}
```

If the process failed, the API will respond with a less-happy JSON package:

```
{
  "meta": {
    "code": 401,
    "text": "Could Not Update Public Profile",
    "list": false
  },
  "data": false
}
```

### Get Your Account Profile

An Account Profile returns a more complete picture of a signed-in Account, including details such as a full list of [Personas]([HOMEURL]/personas), [Bucket]([HOMEURL]/buckets) space available and consumed, and more. This can be performed by sending a `GET` request to the API of the website. For example, if a person is using example.web, the request would be sent to the following location:

```
POST https://example.web/api/account/me
```

No variables are required for this endpoint, but the Access Token must be included. This information can be sent in the HTTP Header or in the POST Body using the `token` variable name.

For example:

```
curl -X POST -H "Authorization: {Authorization Token}" \
     "https://example.web/api/account/me"
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
    "guid": "{Account GUID}",
    "type": "{Account Type}",
    "timezone": "Asia/Tokyo",
    "display_name": "Examplo"
    "mail_address": "mail@example.web",
    "language": {
      "code": "en-us",
      "name": "English (US)"
    },
    "personas": [{a list of Persona objects}],
    "bucket": {
      "storage": 5368709120,
      "available": 12345678,
      "files": 9876
    },
    "created_at": "[JOIN_AT]",
    "created_unix": [JOIN_UNIX],
    "updated_at": "[UPDATED_AT]",
    "updated_unix": [UPDATED_UNIX]
  }
}
```

If the process failed, the API will respond with a less-happy JSON package:

```
{
  "meta": {
    "code": 403,
    "text": "You Need to Log In First",
    "list": false
  },
  "data": false
}
```

### Set Your Account Profile

There are several elements of an Account Profile that can be modified which are shown only to the Account-holder. This data is generally used for Account management and is hidden from public viewing. To update an Account Profile, send a `POST` request to the API of a website associated with an Account. For example, if a person is using example.web, the request would be sent to the following location:

```
POST https://example.web/api/account/me
```

Required variables:

```
display_as={the name you would like to be called}
language={a language localisation tag}
mail_addr={an email address to send password resets}
timezone={a timezone code, either as a TZ database name or abbreviation}
```

This information can be sent URL-encoded in the POST body or as a JSON package.

For example:

```
curl -X POST -H "Content-Type: application/x-www-form-urlencoded" \
     -H "Authorization: {Authorization Token}" \
     -d "display_as=Examplo" \
     -d "language=en-us" \
     -d "mail_addr=email@example.web" \
     -d "timezone=Asia/Tokyo" \
     "https://example.web/api/account/me"
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
    "guid": "{Account GUID}",
    "type": "{Account Type}",
    "timezone": "Asia/Tokyo",
    "display_name": "Examplo"
    "mail_address": "mail@example.web",
    "language": {
      "code": "en-us",
      "name": "English (US)"
    },
    "personas": [{a list of Persona objects}],
    "bucket": {
      "storage": 5368709120,
      "available": 12345678,
      "files": 9876
    },
    "created_at": "[JOIN_AT]",
    "created_unix": [JOIN_UNIX],
    "updated_at": "[NOW_AT]",
    "updated_unix": [NOW_UNIX]
  }
}
```

If the process failed, the API will respond with a less-happy JSON package:

```
{
  "meta": {
    "code": 401,
    "text": "Invalid Email Address Supplied",
    "list": false
  },
  "data": false
}
```
