# Channels

A Channel is a chronologically-sequenced syndication stream where Posts of different types are distributed. Posts can be from one or many [Personas]([HOMEURL]/personas) from one or many Accounts.

Simply put, a Channel consists of website content that is published under the name of one or more [Personas]([HOMEURL]/personas).

### Identifying a Channel GUID

Every Channel has a 36-character global unique identifier and can be found in the `<head>` of any 10Centuries-based web site as a `<meta>` record. For example, this website's Channel GUID is as follows:

```
<meta name="channel_guid" content="[CHANNEL_GUID]" />;
```

For applications that sign a person into a 10Centuries-based web site, it is recommended to ask for the site's URL, then programmatically read the `<meta>` tags.