---
title: Building echoprint/codegen on Mac OS X
date: 22:23 09/13/2014 
taxonomy:
    category: blog
    tag: [mac os x]
---

While investigating how services like [Shazam](http://shazam.com) worked, how they identified music using audio samples, I stumbled upon [Echoprint](http://echoprint.me/), an open-source music identification project, and when I did, I immediately jumped to experiment with it.

The Echoprint project has many sub-projects in itself, but the simplest way to use it is to send the audio sample in a specific format to an Echoprint server. The Echoprint project already has a free-to-use (keep in mind that with “free” comes request limitations) online Echoprint server that identifies the music for you, so you only need to create an appropriate code to call the service.

The echoprint-codegen project is used exactly for that purpose: it takes an audio input and produces a code out of it, ready to use in Echoprint service requests. There are no binary distributions for the project, so you need to manually build it after downloading (or cloning) the project on your PC.

First, clone the project from the Github repository.

```
git clone https://github.com/echonest/echoprint-codegen.git
```

And then build the project by going into the ```src``` directory and typing ```make```… but it can’t be built, right?

The reason it won’t build is that it has two dependencies that must be installed on your PC: [Boost](http://boost.org), a very popular C++ library which will be built-in with the upcoming C++ release, and [TagLib](https://taglib.github.io), a C++ IDv3 tag library. But don’t worry, it’s extremely easy to install both using [Homebrew].

To install Boost, simply type this on your terminal:

```
brew install boost
```

And done! Boost is now installed under the Homebrew cellar directory (```/usr/local/Cellar/boost```), with a symbolic link linking to it from ```/usr/local/include/boost```.

The same thing goes for TagLib as well:

```
brew install taglib
```

There you go, both dependencies are now installed. Only thing left to do now is to slightly modify the echoprint/codegen project’s make file (so that the newly-installed libraries can be detected by gcc) and build the project using make.

Navigate into the ```src``` directory of the project, and open the file ```Makefile``` using ```vi``` (or your favorite text editor), and then change the line where it says

```
BOOST_CFLAGS=-I/usr/local/include/boost-1_35
```

to

```
BOOST_CFLAGS=-I/usr/local/include
```

Save the file, and then run ```make``` again.

And… That’s it! Yes, it’s that simple. Just go up one directory (to the project’s root folder) and you should see the newly-created executable named ```echoprint-codegen```.
