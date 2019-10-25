# The Posts API

##### Read this section to learn about the Posts endpoints and their features

### Sections

1. Retrieving the Global Timeline
2. Retrieving Mentions
3. Retrieving a Specific Post
4. Retrieving a Thread
5. Publishing a Post
6. Updating a Post
7. Deleting a Post
8. Starring a Post
9. Unstarring a Post

### Post Objects

Posts are the primary object of importance on 10Centuries, which follows a standard structure:

```
{
  "guid": "8b6a9883-cd09-4c03-2364-76214671427e",
  "type": "post.note",
  "privacy": "visibility.public",
  "canonical_url": "http:\/\/example.web\/note\/8b6a9883-cd09-4c03-2364-76214671427e",
  "reply_to": false,
  "title": false,
  "content": "<p>Fuzzy Wuzzy was a bear. Fuzzy Wuzzy had no hair! Fuzzy Wuzzy wasn't fuzzy was he?<\/p>",
  "text": "Fuzzy Wuzzy was a bear. Fuzzy Wuzzy had no hair! Fuzzy Wuzzy wasn't fuzzy was he?",
  "meta": false,
  "tags": false,
  "mentions": false,
  "persona": {
    "guid": "07d2f4ec-545f-11e8-99a0-54ee758049c3",
    "as": "@examplo",
    "name": "Examplo the Elf",
    "avatar": "http:\/\/example.web\/avatars\/examplo.jpg",
    "you_follow": false,
    "is_you": false,
    "profile_url": "http:\/\/example.web\/07d2f4ec-545f-11e8-99a0-54ee758049c3\/profile"
  },
  "publish_at": "[NOW_AT]",
  "publish_unix": [NOW_UNIX],
  "expires_at": false,
  "expires_unix": false,
  "updated_at": "[NOW_AT]",
  "updated_unix": [NOW_UNIX]
}
```

#### Definitions

All Post types will follow the above structure at a minimum. Some types, such as Locker objects, may include extra details.

* `guid`: a 36-character idenifier for a Post Object (numeric IDs are never presented)
* `type`: the Type of Object ([Click here for a full list of Object Types]([HOMEURL]/objects))
* `privacy`: the Privacy Type of an Object ([Click here for a full list of Privacy Types]([HOMEURL]/privacytypes))
* `canonical_url`: a complete URL for a Post Object
* `reply_to`: a complete URL to an Object being commented on (Can be any page online available via a unique link and does not need to be on a 10Centuries-powered site)
* `title`: an optional, 512-character title for an Object
* `content`: an HTML representation of the `text` value
* `text`: the source content provided by the author
* `meta`: an array containing additional information related to a Post Object
* `tags`: an array containing a list of strings up to 128-characters each marking a Post Object
* `mentions`: an array containing a list of Personas mentioned in the `text` value
* `persona`: an array containing the details of the [Persona]([HOMEURL]/personas) used to publish the Post Object
* `publish_at`: an ISO8601 representation of the Post Object's publication date
* `publish_unix`: a unix timestamp representation of the Post Object's publication date
* `expires_at`: an ISO8601 representation of the Post Object's expiration date. If the post does not contain an expiration date, this value will be false.
* `expires_unix`: a unix timestamp representation of the Post Object's expiration date. If the post does not contain an expiration date, this value will be false.
* `updated_at`: an ISO8601 representation of the Post Object's most recent update time
* `updated_unix`: a unix timestamp representation of the Post Object's most recent update time

### Retrieving a Global Timeline (Of *All* Posts on a 10Centuries Instance)

From your application, send a `GET` request to the API of the website you'd like to connect to. For example, if a person would like to interact with [Nice.Social](https://nice.social), the request would be sent to the following location:

```
GET https://nice.social/api/posts/global
```

There are no required variables, but the results can be filtered by using one or many optional variables.

Optional variables:

```
count={the maximum number of Post Objects to retrieve (Valid: 1 ~ 250 / Default: 100)}
types={a comma-separated lits of desired Post Object Types (Valid: post.article,post.note,post.bookmark,post.quotation / Default: post.note)}
since={a unix timestamp to start retrieving posts from}
until={a unix timestamp to retrieve posts until}
```

This information can be sent URL-encoded in the GET request. If a person has an Authentication Token, it can be sent either within the header or via the request string.

For example:

```
curl -X GET "https://nice.social/api/posts/global?types=post.note&since=[NOW_UNIX]&count=50"
```

If there are posts that match the request criteria, an array of objects will be returned:

```
{
  "meta": {
    "code": 200,
    "text": false,
    "list": false
  },
  "data": [
    {an array of Post Objects}
  ]
}
```

If the `meta.code` value is not `200`, the `text` item will show the a single error. If there is more than one error to resolve, the `list` item will be an array of issues to resolve.

### Retrieving Mentions (From *All* Posts on a 10Centuries Instance)

Retrieving Mentions is the same as retrieving the Global timeline *except* the request must include a valid Authentication Token in order to identify which Account's mentions is being requested. To do this, send a `GET` request to the API.

```
GET https://nice.social/api/posts/mentions
```

No variables are required for this endpoint, but the Access Token must be included. This information can be sent in the HTTP Header or in the GET request using the `token` variable name. It is possible to use the same optional variables here as when retrieving the Global timeline.

Optional variables:

```
count={the maximum number of Post Objects to retrieve (Valid: 1 ~ 250 / Default: 100)}
types={a comma-separated lits of desired Post Object Types (Valid: post.article,post.note,post.bookmark,post.quotation / Default: post.note)}
since={a unix timestamp to start retrieving posts from}
until={a unix timestamp to retrieve posts until}
```

For example:

```
curl -X GET -H "Authorization: {Authorization Token}" \
     "https://nice.social/api/posts/mentions?types=post.note&since=[NOW_UNIX]"
```

If the process was successful, the API will respond with a JSON package:

```
{
  "meta": {
    "code": 200,
    "text": false,
    "list": false
  },
  "data": [
    {an array of Post Objects}
  ]
}
```

### Retrieving a Specific Post

Specific Post Objects can be requested by using the item's GUID. This can be useful when querying an external server to see if a Post Object has been updated, such as when re-validating WebMentions.

A Post Object can be requested by sending a `GET` request to the API of the website. For example, if a person would like to use example.web, the request would be sent to the following location:

```
GET https://example.web/api/posts/{Post GUID}
```

No variables are required for this endpoint. If the Post Object is invisible or private, a valid Authentication Token will need to be provided in order to retrieve those objects.

For example:

```
curl -X GET -H "Authorization: {Authorization Token}" \
     "https://example.web/api/posts/8b6a9883-cd09-4c03-2364-76214671427e"
```

If a Post Object with a corresponding GUID value exists, the API will respond with a JSON package:

```
{
  "meta": {
    "code": 200,
    "text": false,
    "list": false
  },
  "data": [
    {a single Post Object}
  ]
}
```

If the Post Object does not exist, the API will say as much in the response `meta`.

```
{
  "meta": {
    "code": 400,
    "text": "Invalid Post Identifier Supplied (2)",
    "list": false
  },
  "data": false
}
```

### Retrieving a Thread

Complete Post Object Threads can be requested by passing the GUID of a single Post Object. This makes it possible to easily show an entire conversation, including its branches.

A Thread can be requested by sending a `GET` request to the API of the website. For example, if a person would like to see a conversation on example.web, the request would be sent to the following location:

```
GET https://example.web/api/posts/{Post GUID}/thread
```

No variables are required for this endpoint. If the Thread contains Post Objects that are invisible or private, a valid Authentication Token will need to be provided in order to retrieve those objects.

For example:

```
curl -X GET -H "Authorization: {Authorization Token}" \
     "https://example.web/api/posts/8b6a9883-cd09-4c03-2364-76214671427e/thread"
```

If a Post Object with a corresponding GUID value exists, the API will respond with a JSON package:

```
{
  "meta": {
    "code": 200,
    "text": false,
    "list": false
  },
  "data": [
    {an array of Post Objects}
  ]
}
```

If the Thread does not exist, the API will say as much in the response `meta`.

```
{
  "meta": {
    "code": 400,
    "text": "Invalid Thread Identifier Supplied (2)",
    "list": false
  },
  "data": false
}
```

### Publishing a Post

This is perhaps one of the most important API endpoints as there's little point Authenticating and retrieveing data if there's nothing on the system to collect. This endpoint can be used to create some rather complex post types, however, the goal has been to flatten as much of the fields as possible to make posting from a myriad of applications or devices as simple as possible.

A Post Object can be published by sending an Authenticated `POST` request to the API of the website. For example, if a person would like to publish to example.com, and they have permission to do so, the request would be sent to the following location:

```
POST https://example.web/api/posts/write
```

Required variables for Articles and Notes:

```
content={the Post Object text, in plain text, Markdown-format, or HTML}
post_type={the Post Object type (Valid: post.article,post.note,post.bookmark,post.quotation)}
```

Required variables for Bookmarks and Quotations:

```
content={the Post Object text, in plain text, Markdown-format, or HTML}
post_type={the Post Object type (Valid: post.article,post.note,post.bookmark,post.quotation)}
source_url={the Source URL that is being linked}
source_title={the Title of the Source Page}
```

For example:

```
curl -X POST -H "Content-Type: application/x-www-form-urlencoded" \
     -H "Authorization: {Authorization Token}" \
     -d "content=Fuzzy Wuzzy was a bear. Fuzzy Wuzzy had no hair! Fuzzy Wuzzy wasn't fuzzy, was he?" \
     -d "post_type=post.note" \
     "https://example.web/api/posts/write"
```

Note that if the `channel_guid` or `persona_guid` values are not provided, the default value for the Account (based on the Authentication Token) will be used. Generally the default Persona is the *first* Persona associated with an Account, and the default Channel is the *first* Channel associated with an Account.

When creating a Bookmark or Quotation, the Source URL, Title, and a short summary can be collected by using [the Bookmark API]([HOMEURL]/bookmark).

Optional variables:

```
channel_guid={the Channel GUID to Publish the Post Object to (Note that Write permissions for the Persona must be valid)}
persona_guid={the Persona GUID to Publish the Post Object from}
title={the title of the Post Object}
canonical_url={the Canonical URL (minus the domain and protocol) to use when publishing}
reply_to={the URL that is being Replied To (Note that the URL does not need to be a 10Centuries site)}
post_slug={the Post Slug to use when publishing}
post_privacy={the Privacy Type to use when publishing (Valid: visibility.public,visibility.private,visibility.none / Default: visibility.public)}
publish_at={the Publication Date for the Post Object in UTC}
expires_at={the Expiration Date for the Post Object in UTC}
tags={a comma-separated list of tag words (Note that every tag can be a maximum of 128 characters (512 bytes) in length)}
```

Optional Geo-Location Meta variables:
```
geo_longitude={the longitude coordinate (Valid Range: -180 ~ 180)}
geo_latitude={the latitude coordinate (Valid Range: -90 ~ 90)}
geo_altitude={the altitude coordinate in meters (Valid Range: -7500 ~ 25000)}
geo_description={a description for the geographic coordinate}
```

Note that a geographic description can be sent *without* the numeric latitude and longitude coordinates. This enables geographic information to appear as "In the Kitchen" or "Under the sofa" if the Author chooses without giving away their actual geographic position.

If a Post Object is successfully created, the API will return that item in a JSON package:

```
{
  "meta": {
    "code": 200,
    "text": false,
    "list": false
  },
  "data": [
    {the newly created Post Object}
  ]
}
```

If there was a problem, the API will return an error message. The following example is for a Post Object with no `content` value:

```
{
  "meta": {
    "code": 400,
    "text": "Please Supply Some Text",
    "list": false
  },
  "data": false
}
```

### Updating a Post

Updating (editing) a Post is almost identical to writing a new post, with the addition of one additional variable.

A Post Object can be updated by sending an Authenticated `POST` request to the very same endpoint that is used to create a new Post:

```
POST https://example.web/api/posts/write
```

The required and optional variables are the same as when Publishing a Post Object, but the item's `guid` value must be passed in order to update the Object.

Additional Required variables:

```
guid={the 36-character unique identifier for the Post Object}
```

Alternatively, the `guid` value can be part of the request URL:

```
POST https://example.web/api/posts/8b6a9883-cd09-4c03-2364-76214671427e/write
```

For example:

```
curl -X POST -H "Content-Type: application/x-www-form-urlencoded" \
     -H "Authorization: {Authorization Token}" \
     -d "content=Fuzzy Wuzzy was a bear. Fuzzy Wuzzy had no hair! Fuzzy Wuzzy wasn't fuzzy, was he?" \
     -d "post_type=post.note" \
     -d "geo_description=@The Kitchen Table" \
     "https://example.web/api/posts/8b6a9883-cd09-4c03-2364-76214671427e/write"
```

If a Post Object is successfully updated, the API will return the item in a JSON package:

```
{
  "meta": {
    "code": 200,
    "text": false,
    "list": false
  },
  "data": [
    {the updated Post Object}
  ]
}
```

If there was a problem, the API will return an error message. The following example is for a Post Object with an expiration value before the publication time:

```
{
  "meta": {
    "code": 400,
    "text": "The Post Object Cannot Expire Before it is Published",
    "list": false
  },
  "data": false
}
```

### Deleting a Post

Any Post Object that is still readable by its Author can be deleted. A Post Object that is no longer readable by its Author either does not exist or is in the process of being scrubbed from the system.

A Post Object can be deleted by sending a `DELETE` request to the same endpoint used to create the Post Object:

```
DELETE https://example.web/api/posts
```

Required variables:

```
guid={the 36-character unique identifier for the Post Object}
```

Alternatively, the `guid` value can be part of the request URL:

```
DELETE https://example.web/api/posts/8b6a9883-cd09-4c03-2364-76214671427e
```

For example:

```
curl -X DELETE -H "Authorization: {Authorization Token}" \
     "https://example.web/api/posts/8b6a9883-cd09-4c03-2364-76214671427e/write"
```

If a Post Object is successfully deleted, the API will return a JSON package:

```
{
  "meta": {
    "code": 200,
    "text": false,
    "list": false
  },
  "data": {
    "post_guid": "8b6a9883-cd09-4c03-2364-76214671427e",
    "channel_guid": "[CHANNEL_GUID]",
    "result": 1,
    "sok": 1
  }
}
```

If there was a problem, the API will return an error message:

```
{
  "meta": {
    "code": 400,
    "text": "You do not own the rights to this Post.",
    "list": false
  },
  "data": false
}
```

### Starring a Post

Any Post Object can be starred by sending an Authenticated `POST` request to the website hosting the item, along with the identifier.

```
POST https://example.web/api/posts/8b6a9883-cd09-4c03-2364-76214671427e/star
```

For example:

```
curl -X POST -H "Authorization: {Authorization Token}" \
     "https://example.web/api/posts/8b6a9883-cd09-4c03-2364-76214671427e/star"
```

If a Post Object is successfully starred, the API will return the item in a JSON package:

```
{
  "meta": {
    "code": 200,
    "text": false,
    "list": false
  },
  "data": [
    {the starred Post Object}
  ]
}
```

If there was a problem, the API will return an error message:

```
{
  "meta": {
    "code": 400,
    "text": "Could Not Star Post Object",
    "list": false
  },
  "data": false
}
```

### Unstarring a Post

Any Post Object that has been starred by an account can also be "unstarred" by sending an Authenticated `DELETE` request to the website hosting the item, along with the identifier.

```
DELETE https://example.web/api/posts/8b6a9883-cd09-4c03-2364-76214671427e/star
```

For example:

```
curl -X DELETE -H "Authorization: {Authorization Token}" \
     "https://example.web/api/posts/8b6a9883-cd09-4c03-2364-76214671427e/star"
```

If a Post Object is successfully unstarred, the API will return the item in a JSON package:

```
{
  "meta": {
    "code": 200,
    "text": false,
    "list": false
  },
  "data": [
    {the recently unstarred Post Object}
  ]
}
```

If there was a problem, the API will return an error message:

```
{
  "meta": {
    "code": 400,
    "text": "Could Not Remove Star from Post Object",
    "list": false
  },
  "data": false
}
```

### Pinning a Post

Any Post Object can be pinned by sending an Authenticated `POST` request to the website hosting the item, along with the identifier. Pin values are set by Persona by Post and can be any of the following:

* `pin.none` / `none` ⇢ the default "unpinned" value
* `pin.orange` / `orange`
* `pin.yellow` / `yellow`
* `pin.black` / `black`
* `pin.green` / `green`
* `pin.blue` / `blue`
* `pin.red` / `red`

Pin colours do not have any pre-defined meaning and are meant have meanings assigned by the Account holder. These can be viewed in a similar fashion to flags in email applications. If no pin value or if an invalid pin value is supplied, the default value of `pin.none` will be used.

```
POST https://example.web/api/posts/8b6a9883-cd09-4c03-2364-76214671427e/pin
```

Required variables when pinning:

```
value={the pin value (either as pin.{colour} or {colour})}
```

For example:

```
curl -X POST -H "Content-Type: application/x-www-form-urlencoded" \
     -H "Authorization: {Authorization Token}" \
     -d "value=pin.orange" \
     "https://example.web/api/posts/8b6a9883-cd09-4c03-2364-76214671427e/pin"
```

If a Post Object is successfully pinned, the API will return the item in a JSON package:

```
{
  "meta": {
    "code": 200,
    "text": false,
    "list": false
  },
  "data": [
    {the pinned Post Object}
  ]
}
```

If there was a problem, the API will return an error message:

```
{
  "meta": {
    "code": 400,
    "text": "Could Not Pin Post Object",
    "list": false
  },
  "data": false
}
```

### Unpinning a Post

Any Post Object that has been pinned by an account can also be "unpinned" by sending an Authenticated `DELETE` request to the website hosting the item, along with the identifier or a `POST` request with a value of `pin.none`.

```
DELETE https://example.web/api/posts/8b6a9883-cd09-4c03-2364-76214671427e/pin
```

For example:

```
curl -X DELETE -H "Authorization: {Authorization Token}" \
     "https://example.web/api/posts/8b6a9883-cd09-4c03-2364-76214671427e/pin"
```

If a Post Object is successfully unpinned, the API will return the item in a JSON package:

```
{
  "meta": {
    "code": 200,
    "text": false,
    "list": false
  },
  "data": [
    {the recently unpinned Post Object}
  ]
}
```

If there was a problem, the API will return an error message:

```
{
  "meta": {
    "code": 400,
    "text": "Could Not Remove Pin from Post Object",
    "list": false
  },
  "data": false
}
```