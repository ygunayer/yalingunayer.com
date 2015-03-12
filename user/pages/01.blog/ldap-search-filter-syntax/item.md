---
title: LDAP Search Filter Syntax
date: 13:59 03/08/2014 
taxonomy:
    category: blog
    tag: [active directory, ldap]
---

Accessing the Active Directory is a pretty common problem in software projects, especially in enterprise-related ones. Although, since it’s usually an *“implement once, reuse forever”* feature, it’s usually overlooked as well people usually join projects halfway through, when all critical features have long been implemented.

In my case, I had to come up with our own solution to generate LDAP queries when the project I joined halfway through didn’t have the necessary components to query the AD and return results, even though an LDAP-based authentication was a core feature. I personally took over that part and implemented something similar to CodeIgniter’s active record feature, a set of simple classes to generates LDAP queries (search filters, to be exact), run them and parse the results into strongly typed, annotation-based models.

That particular implementation will be the subject of a future article, but in the meantime I’ve decided to share what I’ve gathered through my work. Not much, perhaps, but here it is anyway.

Let’s begin by clarifying what LDAP and Active Directory are and how they’re queried. A directory service is a hierarchical data storage service often used when more reads are made than writes, for instance, DNSes (Domain Name Systems). LDAP (Lightweight Directory Access Protocol) is both an implementation of directory services and a protocol that defines its own way of maintaining connection (through the TCP/IP protocol) and querying data (LDAP search filters). Microsoft’s Active Directory (AD), on the other hand, is an implementation of the LDAP protocol with some additional features. In that sense, querying the Active Directory means constructing an LDAP search filter, sending it to the server, and enumerating the results that are returned.

The simplest LDAP search filter takes the form of ```({attribute}{operator}{value})```, a simple comparison filter where the ```attribute``` parameter is the name of the attribute to scan and the ```value``` is the expression to match with the specified ```operator```. Values support wildcards and are both accent and case insensitive (with a few exceptions such as passwords), while attribute names are not.

Here’s a very simple search filter that queries all objects on AD (note the enclosing parentheses).

```
(objectClass=*)
```

Filters can nest other filters, and when they do, they get the form of ```({operator}{filter}{filter}…)```, where the operator is a logical operator (```AND```, ```OR``` and ```NOT```) that binds the sub-filters. So a query to find users with names beginning with ```Yalin``` would be:

```
(&(objectClass=user)(name=Yalin*))
```

When combining multiple ```NOT AND``` filters;

```
(!(&(objectClass=user)(name=Yalin*)))
```

Combining filters with different joining logical operators isn’t very much difficult either. Here’s a query to find users who are titled ```Programmer``` and whose names or e-mails begin with ```Yalin``` (or ```yalin``` for that matter):

```
(&(objectClass=user)(title=Programmer)(|(name=Yalin*)(mail=yalin*)))
```

Let’s format it as if it was a tree so it’s easier to read:

```
(&
  (objectClass=user)
  (title=Programmer)
  (|
    (name=Yalin*)
    (mail=yalin*)
  )
)
```

LDAP search filters also support a special operator that allows using custom comparison mechanisms. It’s achieved by specifying the ```OID``` of the mechanism between semicolons right after the attribute name and before the operator. Here’s an example.

```
(!(userAccountControl:1.2.840.113556.1.4.803:=2))
```

If you’re not familiar with the ```OID``` term, here’s a short explanation. OIDs are part of a standard set by the IEEE as a means to universally represent any type of objects using unique identifiers called OIDs (object identifiers). In this sense, even an apple or an orange might have an OID. They’re constructed by concatenating dots and numbers, and their structure is similar to what packages are in Java, where each segment designates an entry in the OID hierarchy. In our example, which is ```1.2.840.113556.1.4.803```, 1 means ISO (the standard), 2 means member body, 840 means US (yes, the United States), 113556 means Microsoft, and so forth. For more detailed information about OID ```1.2.840.113556.1.4.803``` see [OID repository – {iso(1) member-body(2) us(840) microsoft(113556) 1 4 803}](http://oid-info.com/get/1.2.840.113556.1.4.803).

OIDs are commonly used for specifying various kinds of mechanisms in protocols like LDAP and Kerberos, and the OID 1.2.840.113556.1.4.803 refers to an LDAP mechanism named ```LDAP_MATCHING_RULE_BIT_AND```, of which purpose is what its name suggests: checking if a bit at a certain index is set. When applied on the ```userAccountControl``` attribute with the value ```2```, as in the case of ```(userAccountControl:1.2.840.113556.1.4.803:=2)```, it translates to “see if the second bit on the userAccountControl attribute’s value is set”. And as it happens, the second bit on userAccountControl is the “disabled account” flag, so when it’s set, it means that the user account is disabled. Therefore, negating the result of this comparison filters out all disabled accounts and leaves us with only the active users.

If you’re interested, here’s a list of all userAccountControl flags: [How to use the UserAccountControl flags to manipulate user account properties](http://support.microsoft.com/kb/305144).

Now that we’re armed with this knowledge, we can use it to generate queries that are actually useful, like finding active users whose names, e-mail addresses or usernames start with with john, but e-mail addresses does not equal to ```john.doe@gmail.com``` (a slightly different version of this query is actually used pretty frequently in our application)

```
(&(objectClass=user)(!(userAccountControl:1.2.840.113556.1.4.803:=2))(|(name=john*)(mail=john*)(sAMAccountName=john*))(!(mail=john.doe@gmail.com)))
```

And for simplicity, here it is as a tree;

```
(&
  (objectClass=user)
  (!
    (userAccountControl:1.2.840.113556.1.4.803:=2)
  )
  (|
    (name=john*)
    (mail=john*)
    (sAMAccountName=john*)
  )
  (!
    (mail=john.doe@gmail.com)
  )
)
```

There we go. Not that complex, to be honest, all we ever do is nest filters within other filters.

While LDAP queries already run very fast (hence the word “lightweight”), even the simplest queries may be optimized further. One such example is user queries, where ```(sAMAccountType=805306368)``` is faster than ```(objectClass=user)```. In that sense, the optimized version of the query to run when searching for all active users would be;

```(&(sAMAccountType=805306368)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))```

For a list of some other optimized queries, visit this page: [Famous Filters](http://www.ldapexplorer.com/en/manual/109050000-famous-filters.htm).

Well, that concludes the article, I hope it’s been helpful. As I’ve mentioned before, my next article will cover an LDAP query generator similar to CodeIgniter’s Active Record, implemented in Java, so please stay tuned.

**Related Links**

Below you can find a list of sources and related links, including the ones I’ve mentioned in the article.

- [Detailed information about OID 1.2.840.113556.1.4.803 LDAP_MATCHING_RULE_BIT_AND](http://oid-info.com/get/1.2.840.113556.1.4.803)
- [Microsoft article listing the flags on userAccountControl](http://support.microsoft.com/kb/305144)
- [List of some popular optimized LDAP search filters](http://www.ldapexplorer.com/en/manual/109050000-famous-filters.htm)
- [Microsoft Article explaining how to construct LDAP search filters](http://technet.microsoft.com/en-us/library/cc261947%28v=office.12%29.aspx)
- [List of all attributes on Active Directory](http://msdn.microsoft.com/en-us/library/windows/desktop/ms675090%28v=vs.85%29.aspx)
- [List of common LDAP object classes and attributes](http://www.zytrax.com/books/ldap/ape/#objectclasses)
- [IETF RFC4515 - String Representations LDAP Search Filter](http://www.rfc-editor.org/rfc/rfc4515.txt)
