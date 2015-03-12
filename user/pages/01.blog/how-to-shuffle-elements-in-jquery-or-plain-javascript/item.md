---
title: How to Shuffle Elements in jQuery or Plain Javascript
date: 01:43 06/29/2013 
taxonomy:
    category: blog
    tag: [front-end, javascript]
---

Every now and then you might need to shuffle some elements on your page, like in an online banking page you might have to display a virtual keypad which could be shuffled on demand, or on a tile game you’re most likely to have to shuffle the board. This problem usually boggles the mind of many people, but it has a very simple solution.

Let’s start with the basics. Javascript arrays have a method called sort(), which returns the sorted version of an array (note that it doesn’t actually modify the array itself), and receives a callback function as a parameter (called a predicate function), which in turn receives two parameters, referring to tuples of values from the array. The predicate is expected to compare those two values, and return a numeric value that tells the method which of the two values is supposed to rank first. Given that the two values are represented by ```a``` and ```b```, the predicate would return a negative value when a comes before b, a positive value when b comes before a, and 0 if a and b match. Let’s look at an example.

Given an array of integers such as ```[ 3, 2, 8, 1, 5, 1 ]```, and given that we mean to sort it by ascending order, our predicate can be as follows:

<iframe width="100%" height="250" src="http://jsfiddle.net/9kzSM/embedded/js,html,result" allowfullscreen="allowfullscreen" frameborder="0"></iframe>

While this is a really simple predicate, it makes two comparisons and takes up a space of 3 lines, decreasing performance and readability of the code. By subtracting b from a, however, we can drop this down to a single operation, meaning much better performance and readability. The logic behind is that the subtraction ```(a – b)``` would produce a negative value when a is less than b, 0 when ```a``` equals ```b``` and a positive value when ```a``` is greater than ```b```. For example, when ```a``` is ```5``` and ```b``` is ```2```, ```(a – b)``` would equal ```3```, a positive value, which means a is greater than 2; or when ```a``` is ```1``` and ```b``` is ```8```, ```(a – b)``` would equal ```-7```, a negative value, which means a is less than b.

<iframe width="100%" height="250" src="http://jsfiddle.net/L64HE/1/embedded/js,html,result" allowfullscreen="allowfullscreen" frameborder="0"></iframe>

Now that we know how the sort() method works, let’s go with randomizing it. To do that, we’re going to use the standard Math.random() function which produces a decimal value between 0.0 and 1.0.

The science of statistics is all about approximation, and as a subject of statistics, random number generators (RNG), which are far from perfect, are expected to produce an normal distrubition. Which means that, when a randomize function that produces values between ```0.0``` and ```1.0```, we expect that out of ```N``` randomly generated numbers, ```N/2``` of them would fall between ```0.0``` and ```0.5```, and the other ```N/2``` would fall between ```0.5``` and ```1.0```.

According to this rule, we can go ahead and gamble that even when we’re generating 10 values, at least some of those values would be above or below ```0.5```. Which means that, if we subtract ```0.5``` from any given random number, we’re likely to end up with a negative value in some cases, a positive value in some other cases, and even ```0``` in some other cases.

What if we create a predicate that does just that and put it in a sort function? Let’s find out!

<iframe width="100%" height="250" src="http://jsfiddle.net/zVwqt/1/embedded/js,html,result" allowfullscreen="allowfullscreen" frameborder="0"></iframe>

Of course it’s not perfect (see the previous explanations regarding RNGs), but it really does look like a pretty good shuffling function to me! It doesn’t care about the value types either, so you can use it to sort any type of data! (I used ```JSON.stringify``` in this example, which basically does what its name suggests, converting Javascript objects to strings)

<iframe width="100%" height="250" src="http://jsfiddle.net/nMDvR/1/embedded/js,html,result" allowfullscreen="allowfullscreen" frameborder="0"></iframe>

If we can shuffle an array with any type of value, who’s to say that we can’t shuffle an array of DOM elements, or an array of jQuery objects that wrap those elements?

Fortunately for us, our friends working on jQuery had the insight of mirroring the ```sort()``` method onto jQuery objects, without changing any of its aspects. So, given a jQuery object which contains multiple elements, such as ```$(“#sort-menu > li”)```, we can use the ```sort()``` method to return a sorted version of those elements.

<iframe width="100%" height="250" src="http://jsfiddle.net/PcWyp/1/embedded/js,html,result" allowfullscreen="allowfullscreen" frameborder="0"></iframe>

As you can see in the example, I’ve outputted the text of the list items after I’ve sorted them, only to show that the sorting operation worked. In order to actually change the order those elements, however, we will need to re-attach them to their parents, because, if you remember, sort() doesn’t modify the array itself, or in this case, the jQuery object, rather, it returns a sorted version of the array or jQuery elements. Methods like append, appendTo, prepend and prependTo do not clone or detach/reattach elements as long as their target is the same as their calling elements’ actual parents, so that’s a really big plus on the performance side as well.

<iframe width="100%" height="250" src="http://jsfiddle.net/GasWW/2/embedded/js,html,result" allowfullscreen="allowfullscreen" frameborder="0"></iframe>

Now you can see that the list items are now in order, even thought they were unsorted in the original HTML code. This means that we can now use the same random predicate as we did before to shuffle our elements.

<iframe width="100%" height="250" src="http://jsfiddle.net/drCqG/2/embedded/js,html,result" allowfullscreen="allowfullscreen" frameborder="0"></iframe>

That’s it! Every time you run the script (try clicking on the *Result* button at the top), the elements will be ordered differently.

Now, let’s create a virtual numeric keypad to show off our newly-found skills!

<iframe width="100%" height="350" src="http://jsfiddle.net/VS9kV/embedded/js,html,result" allowfullscreen="allowfullscreen" frameborder="0"></iframe>

And finally, as promised, here’s the plain Javascript version of our shuffler. It’s ```~80%``` faster than the jQuery version (see [http://jsperf.com/plain-js-shuffle-vs-jquery-shuffle](http://jsperf.com/plain-js-shuffle-vs-jquery-shuffle)), however, it doesn’t work on IE<9 (but then again, jsFiddle itself doesn’t work either) because it uses ```getElementsByClassName```, which is not supported by those browsers. You can use polyfills for them, though, but keep in mind that a very important part of jQuery’s low performance is related to these IE<9 polyfills.

<iframe width="100%" height="350" src="http://jsfiddle.net/MUMga/1/embedded/js,html,result" allowfullscreen="allowfullscreen" frameborder="0"></iframe>
