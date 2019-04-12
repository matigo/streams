# The Search API

##### Read this section to learn about the Search endpoints and their features

### Sections

1. Perform a Search

### Searching Objects

Searches can be performed on any Channel object, with our without authentication. Non-authenticated searches will only have access to objects that are `visibility.public`. Authenticated searches will have access to the objects that match the authenticated Account's permissions. In both cases, objects that are not available to the person performing the search will be completely invisible.

As searches are performed against Channels, it's important to know the ID. This can be found by looking at the `channel_guid` meta value for the site you wish to search or the domain name of the site.

Search Objects will always follow the following strucutre:

```
{
    "guid": "8b6a9883-cd09-4c03-2364-76214671427e",
    "title": false,
    "content": "<p>Fuzzy Wuzzy was a <span class="highlight">bear</span>. Fuzzy Wuzzy had no hair! Fuzzy Wuzzy wasn't fuzzy was he?</p>",
    "url": "http:\/\/example.web\/note\/8b6a9883-cd09-4c03-2364-76214671427e",
    "privacy": "visibility.public",
    "type": "post.note",
    "score": 1
    "publish_at": "[NOW_AT]",
    "publish_unix": "[NOW_UNIX]",
    "expires_at": false,
    "expires_unix": false,
    "author": {
        "name": "@examplo",
        "display_name": "Examplo the Elf",
        "avatar": "http:\/\/example.web\/avatars\/examplo.jpg",
    }
}
```

### Perform a Search

Searches can be performed by passing a valid `Channel GUID` or domain address along with a comma-separated list of keywords to search for. If an Authentication Token is also passed, the Search results will include any private or hidden objects that the authenticated account is able to see.

> **Note:** Password-protected posts cannot be included in search results as this would defeat the purpose of protecting access to a post via a password.

To perofrm a search, send a `GET` request to the API of the website you'd like to connect to. For example, if a person is trying to sign into example.web, the request would be sent to the following location:

```
GET https://example.web/api/search
```

Required variables:

```
channel_guid={Channel identifier}
for={comma-separated list of words}
```

Optionally, `channel_guid` can be replaced with `site_url` with the site domain.

This information can be sent URL-encoded in the POST body or as a JSON package.

For example:

```
curl -X GET -H "Authorization: {Authorization Token}" \
     "https://example.web/api/search?site_url=example.web&for=bright,yellow"
```

If the search request is cromulent, the API will respond with a JSON package:

```
{
  "meta": {
    "code": 200,
    "text": false,
    "list": false
  },
  "data": [
    {an array of Search Objects}
  ]
}
```

If the `meta.code` value is not `200`, the `text` item will show the a single error. If there is more than one error to resolve, the `list` item will be an array of issues to resolve.

```
{
  "meta": {
    "code": 400,
    "text": "Please enter some more specific search criteria",
    "list": false
  },
  "data": false
}
```