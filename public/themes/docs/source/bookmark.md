# The Bookmark API

##### Read this section to learn about the Bookmark endpoints and their features

### Sections

1. Read a Web Page Summary

### Read a Web Page Summary

When creating Bookmarks or Quotations, it's often helpful to include some of the information from the page being linked within your own post. The Bookmark API makes this possible by parsing a requested URL and returning some of the key information in a JSON object. This API endpoint is currently able to natively support the metadata fields from a number of different blogs and news sites with regular revisions being made when exceptions are found.

To use this endpoint, send an authenticated `GET` request to the API of the website you'd like to connect to. For example, if a person is trying to read a profile from example.web, the request would be sent to the following location:

```
GET https://example.web/api/bookmark/read
```

Required variables:

```
url={the complete URL to read}
```

This information can be sent URL-encoded in the request URL.

For example:

```
curl -X GET -H "Authorization: {Authorization Token}" \
     "https://example.web/api/bookmark/read?url=[EXAMPLE_URL]"
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
    "title": "API Documentation",
    "summary": "API Documentation for the 10Centuries Platform",
    "image": "https://docs.10centuries.org/images/banner.jpg",
    "keywords": "10C, API, docs, documentation, support",
    "text": "Welcome to the documentation site for the 10Centuries API. Here we will help you understand what sort of features and functions from 10Centuries you can implement and use in your own applications."
  }
}
```

If the process failed, the API will respond with a less-happy JSON package:

```
{
  "meta": {
    "code": 401,
    "text": "Invalid URL Supplied",
    "list": false
  },
  "data": false
}
```

This data can then be used within a Bookmark or Quotation Post as a blockquote or in some other fashion.