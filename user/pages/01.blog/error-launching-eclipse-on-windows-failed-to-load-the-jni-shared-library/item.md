---
title: Error Launching Eclipse on Windows: “Failed to load the JNI shared library”
date: 18:49 05/17/2014 
taxonomy:
    category: blog
    tag: [tricks, eclipse, windows]
---

My Eclipse recently stopped working gave me this error when I tried to launch it:

> Failed to load the JNI shared library “C:\Windows\system32\..\jre\bin\client\jvm.dll\”

While it doesn’t look like it explains anything at first, looking closely, you can see that it’s looking for the JVM DLLs under the System32 path. That’s because something, somehow, placed the Java executables (```java.exe```, ```javaw.exe```, ```javaws.exe```) under the ```System32``` path, and when Eclipse is launched it tries to use this path as the JRE/JDK path.

To fix this problem, delete the specified executables from the System32 folder (or wherever your error message directs you to) and make sure that your ```JAVA_HOME``` environment variable is set to the JDK path, and your ```PATH``` variable contains ```%JAVA_HOME%\bin```.
