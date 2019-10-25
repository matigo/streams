# Supported Markdown Codes

##### Read this section to learn about Markdown and how to format your posts.

### Sections

1. History
2. Basic Syntax
3. Nice Footnotes

### History

Markdown is a text-to-HTML conversion tool for web writers. Markdown allows you to write using an easy-to-read, easy-to-write plain text format, then convert it to structurally valid HTML that can be read by most modern browsers. Markdown was [created by John Gruber](https://daringfireball.net/projects/markdown/) and shared with the world for free.

The Markdown syntax allows you to write text naturally and format it without using HTML tags. More importantly: in Markdown format, your text stays enjoyable to read for a human being, and this is true enough that it makes a Markdown document publishable as-is, as plain text. If you are using text-formatted email, you already know some part of the syntax.

If you have some understanding of HTML, you can also read [the full documentation of Markdown's syntax](https://daringfireball.net/projects/markdown/), available on John's web site.

### Basic Syntax

###### Italicising sections of text:

```
Fuzzy Wuzzy *was a bear*. Fuzzy Wuzzy had no hair ...
```

Becomes: Fuzzy Wuzzy *was a bear*. Fuzzy Wuzzy had no hair ...

The "was a bear" section becomes italicised because it is surrounded by a pair of single asterisks.

###### Bolding sections of text:

```
Fuzzy Wuzzy was a bear. Fuzzy Wuzzy **had no hair** ...
```

Becomes: Fuzzy Wuzzy was a bear. Fuzzy Wuzzy **had no hair** ...

The "had no hair" section becomes bolded because it is surrounded by a pair of double asterisks.

###### Bold + Italics

```
Fuzzy Wuzzy was a bear. ***Fuzzy Wuzzy*** had no hair ...
```

Becomes: Fuzzy Wuzzy was a bear. ***Fuzzy Wuzzy*** had no hair ...

The second "Fuzzy Wuzzy" section becomes bolded and italicised because it is surrounded by a pair of triple asterisks.

###### Strike-Through

```
Fuzzy Wuzzy was a &#126;&#126;lizard&#126;&#126; bear. Fuzzy Wuzzy had no hair ...
```

Becomes: Fuzzy Wuzzy was a ~~lizard~~ bear. Fuzzy Wuzzy had no hair ...

Here, "Lizard" is struck out because it is surrounded by a pair of double-tildes.

###### Bulleted Lists

```
* Pinocchio
* Big Bad Wolf
* Puss in Boots
* Gingerbread Man
* Robin Hood
* Three Blind Mice
```

Becomes:

* Pinocchio
* Big Bad Wolf
* Puss in Boots
* Gingerbread Man
* Robin Hood
* Three Blind Mice

So long as the first character of a line is an asterisk, and the previous line is empty, a bulleted list will be created.

###### Numbered Lists

```
1. Pinocchio
2. Big Bad Wolf
3. Puss in Boots
4. Gingerbread Man
5. Robin Hood
6. Three Blind Mice
```

Becomes:

1. Pinocchio
2. Big Bad Wolf
3. Puss in Boots
4. Gingerbread Man
5. Robin Hood
6. Three Blind Mice

So long as the first character of a line is a number followed by a period, and the previous line is empty, a numbered list will be created. The numbers do not even need to be unique or sequential for this to work.

```
1. Pinocchio
1. Big Bad Wolf
1. Puss in Boots
7. Gingerbread Man
4. Robin Hood
8. Three Blind Mice
```

Becomes:

1. Pinocchio
1. Big Bad Wolf
1. Puss in Boots
7. Gingerbread Man
4. Robin Hood
8. Three Blind Mice

###### Monospacing

```
`Fuzzy Wuzzy` was a bear. `Fuzzy Wuzzy` had no hair ...
```

Becomes: `Fuzzy Wuzzy` was a bear. `Fuzzy Wuzzy` had no hair ...

The two "Fuzzy Wuzzy" sections become monospaced because they are surrounded by a pair of backticks.

###### Code Blocks

```
&#96;&#96;&#96;
Fuzzy Wuzzy was a bear.
    Fuzzy Wuzzy had no hair ...
&#96;&#96;&#96;
```

Becomes:

```
Fuzzy Wuzzy was a bear.
    Fuzzy Wuzzy had no hair ...
```

When text is sandwiched between a trio of backticks white spacing is maintained.

###### Links

```
A lot of people get their information from [Wikipedia](https://www.wikipedia.org/).
```

Becomes: A lot of people get their information from [Wikipedia](https://www.wikipedia.org/).

Using the `[{text}]({link})` syntax allows you to make just about anything into a link.

###### Images

```
![Matigo's Avatar](https://matigo.ca/avatars/jason_fox_box.jpg)
```

Becomes:

![Matigo's Avatar](https://matigo.ca/avatars/jason_fox_box.jpg)

###### Footnotes

The footnote formatting is not *true* Markdown, but is used within Streams with a high degree of regularity. Footnotes can consist of formatted paragraphs, lists, and links. They're made by wrapping a number-prefixed section of text in enclosing square brackets. Just as with the lists, the numbers do not need to be sequential or even unique. The Markdown rendering engine will take care of numbering footnotes accordingly.

Footnotes are always appended to the object they belong to.

```
Fuzzy Wuzzy was a bear&lsqb;1. A friendly one!] ...
```

Becomes: Fuzzy Wuzzy was a bear[1. A friendly one!] ...
