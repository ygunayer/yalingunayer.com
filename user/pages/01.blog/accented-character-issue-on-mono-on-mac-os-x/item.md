---
title: Accented Character Issue on Mono on Mac OS X
date: 20:56 02/24/2015 
taxonomy:
    category: blog
    tag: [c#, mac os x]
---

Developers who are new to a language or a platform are usually greeted by encoding issues. One example is beginner PHP/MySQL developers. They usually create a simple form page, post some data with it, persist that data with on the back-end, refresh the page, and realize that all unicode characters are gone and in their place, there are some weird, incomprehensible characters.

I’ve recently encountered a similar problem in one of my projects, namely, a desktop application written in C# running on Mono. When run on OS X, my app began acting strangely, and when I looked at the logs, I realized that all umlauts/diacritics/accents on my characters were next to the base characters, rather than on top or bottom. Here’s an example:

![Unicode Strings in Composed and Decomposed Forms](images/uninorm-example.jpg)

After some research I found that this was caused by the fact that Windows and OS X handle unicode strings differently. OS X uses a unicode normalization form called NFD which decomposes Unicode characters into its components and arrange them in a specific order, whereas Windows uses NFC which does it in reverse, i.e. it combines the components of an accented character into a single character. When two strings with different normalization forms are compared they don’t match.

To fix this issue, all you have to do is to choose a specific normalization form for your software and stick with it. Use it on any string you think might be sensitive. In my case, all I had to do was to call the Normalize() method on any such string, and since Mono, like .NET Framework, uses NFC by default (i.e. when Normalize() is called without a parameter), I got the result I wanted. It’s worth noting that Normalized() can also be called with a parameter, namely a value from the System.TExt.NormalizationForm enum, to use a specific form of normalization.

Since OSes apply these normalization steps on all strings that are passed to them, your software might not always get the result expects to, especially when it interacts with the OS directly (i.e. doing disk I/O). And while I’m only focusing on the Mono/C# part of the story, it’s safe to expect the same behaviour on all platforms and languages, so don’t forget to normalize your strings when necessary!

I’ve created the following gist to demonstrate the issue. It first creates a folder, then fetches a list of folders, and To run on OS X (assuming you have Mono Runtime installed), simply type ```mcs UnicodeTest.cs && mono UnicodeTest.exe```.

[plugin:embed](https://gist.github.com/ygunayer/935a1f871cba3c213138)

And here’s a sample output:

![Output of the Demo](images/uninorm-output.jpg)
